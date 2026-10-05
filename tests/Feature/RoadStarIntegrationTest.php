<?php

namespace Tests\Feature;

use App\Http\Services\RoadStar\RoadStarService;
use App\Http\Services\RoadStar\RoadStarSiteDataParser;
use App\Http\Services\RoadStar\RoadStarSyncService;
use App\Jobs\SyncRoadStarSites;
use App\Models\ApiUsageLog;
use App\Models\RoadStarAudienceData;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Sleep;
use RuntimeException;
use Tests\TestCase;

/**
 * RoadStar Audience API — client, parser, sync service, queue job, command,
 * scheduler rule and admin endpoints.
 *
 * In-memory SQLite with only the tables it needs (the RoadStar migrations
 * themselves are run). Every RoadStar call is faked with Http::fake(); these
 * tests never reach a real server. The credentials below are fake.
 */
class RoadStarIntegrationTest extends TestCase
{
    private const BASE = 'http://roadstar.test';
    private const USER = 'rs-test-user';
    private const PASS = 'rs-test-password-0123456789';

    /** @var array<int, string> every log line + context written during a test */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.roadstar.base_url'               => self::BASE,
            'services.roadstar.username'               => self::USER,
            'services.roadstar.password'               => self::PASS,
            'services.roadstar.batch_size'             => 20,
            'services.roadstar.refresh_days'           => 15,
            'services.roadstar.retry_hours'            => 24,
            'services.roadstar.period_start'           => '2024-03-01',
            'services.roadstar.period_end'             => '2024-03-31',
            'services.roadstar.request_delay_ms'       => 0,
            'services.roadstar.site_prefix'            => 'BI/',
            'services.roadstar.queue'                  => 'roadstar',
            'services.roadstar.bulk_enabled'           => true,
            'cache.default'                            => 'array',
        ]);

        Sleep::fake();
        // An un-faked request would really be sent; make that an error instead.
        Http::preventStrayRequests();
        $this->createSchema();

        Event::listen(MessageLogged::class, function (MessageLogged $e) {
            $this->logged[] = $e->message . ' ' . json_encode($e->context);
        });
    }

    protected function tearDown(): void
    {
        // Whatever happened in the test, no credential may appear in a log line.
        foreach ($this->logged as $line) {
            $this->assertStringNotContainsString(self::PASS, $line);
            $this->assertStringNotContainsString(base64_encode(self::USER . ':' . self::PASS), $line);
        }

        parent::tearDown();
    }

    /* ============================ AUTHENTICATION ============================ */

    public function test_requests_use_basic_auth_and_the_documented_payload(): void
    {
        Http::fake([self::BASE . '/sitedata' => Http::response([['BI/HD000001' => 'Site not available.Please add the site first']])]);

        $r = app(RoadStarService::class)->testConnection('2024-03-01', '2024-03-31');

        $this->assertTrue($r['ok']);
        Http::assertSent(function (HttpRequest $request) {
            return $request->url() === self::BASE . '/sitedata'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Basic ' . base64_encode(self::USER . ':' . self::PASS))
                && $request->hasHeader('Content-Type', 'application/json')
                && $request->data() === [['arr' => 'BI/CONNECTION-TEST', 'st' => '2024-03-01', 'ed' => '2024-03-31']];
        });
    }

    public function test_admin_test_connection_reports_success(): void
    {
        Http::fake([self::BASE . '/sitedata' => Http::response([['BI/CONNECTION-TEST' => 'Site not available.Please add the site first']])]);

        $res = $this->withSession(['user_id' => 1])->getJson('http://localhost/media/roadstar/test-connection');

        $res->assertOk()->assertJson(['ok' => true]);
        $this->assertStringNotContainsString(self::PASS, $res->getContent());
        $this->assertStringNotContainsString(self::USER, $res->getContent());
    }

    public function test_invalid_credentials_return_401_error_without_leaking_them(): void
    {
        Http::fake([self::BASE . '/*' => Http::response(['error' => 'Unauthorized access. Please provide valid credentials.'], 401)]);

        $r = app(RoadStarService::class)->testConnection('2024-03-01', '2024-03-31');

        $this->assertFalse($r['ok']);
        $this->assertSame(401, $r['http_status']);
        $this->assertSame(RoadStarService::ERR_UNAUTHORIZED, $r['error_type']);
        $this->assertStringNotContainsString(self::PASS, $r['error']);

        $log = ApiUsageLog::where('provider', 'roadstar')->first();
        $this->assertSame('failed', $log->status);
        $this->assertStringNotContainsString(self::PASS, (string) $log->error_message);
    }

    public function test_not_configured_sends_nothing(): void
    {
        config(['services.roadstar.password' => '']);
        Http::fake();

        $r = app(RoadStarService::class)->siteData([['arr' => 'X', 'st' => '2024-03-01', 'ed' => '2024-03-31']]);

        $this->assertSame(RoadStarService::ERR_NOT_CONFIGURED, $r['error_type']);
        Http::assertNothingSent();
    }

    /* ============================ SITE / AUDIENCE ============================ */

    public function test_sync_stores_audience_from_the_dev_server_string_format(): void
    {
        $id = $this->media(['roadstar_site_id' => 'CWMS/MUM/WEH-019']);
        Http::fake([self::BASE . '/sitedata' => Http::response([['CWMS/MUM/WEH-019' => json_encode($this->metrics())]])]);

        $r = app(RoadStarSyncService::class)->sync([$id], 'admin');

        $this->assertTrue($r['ok']);
        $this->assertSame('success', $r['results'][$id]['status']);

        $row = RoadStarAudienceData::where('media_id', $id)->firstOrFail();
        $this->assertSame('CWMS/MUM/WEH-019', $row->roadstar_site_id);
        $this->assertSame('2024-03-01', $row->period_start->toDateString());
        $this->assertSame('2024-03-31', $row->period_end->toDateString());
        $this->assertSame(776044, $row->unique_reach);
        $this->assertSame(3138517, $row->impressions);
        $this->assertSame(4.04, (float) $row->frequency);
        $this->assertSame(['label' => 'Mon', 'value' => 318368], $row->day_wise_avg_impressions[0]);
        $this->assertSame(['label' => '0', 'value' => 3853], $row->hourly_avg_impressions[0]);
        $this->assertSame(['label' => '1+', 'value' => 776043], $row->effective_frequency[0]);
        $this->assertSame(['label' => 'Male', 'value' => 65], $row->gender[0]);
        $this->assertSame(['label' => 'Xiaomi', 'value' => 23], $row->mobile_brands[0]);
        $this->assertCount(2, $row->date_wise_impressions);
        $this->assertIsArray($row->raw_response);

        $media = DB::table('media_management')->find($id);
        $this->assertSame('success', $media->roadstar_sync_status);
        $this->assertNotNull($media->roadstar_last_synced_at);
        $this->assertNull($media->roadstar_sync_error);
    }

    public function test_sync_accepts_the_documented_nested_array_format_and_batches_sites(): void
    {
        $a = $this->media(['roadstar_site_id' => 'CWMS/MUM/WEH-019']);
        $b = $this->media(['roadstar_site_id' => 'CWMS/MUM/MCJ-003']);
        Http::fake([self::BASE . '/sitedata' => Http::response([
            ['CWMS/MUM/WEH-019' => $this->metrics()],
            ['CWMS/MUM/MCJ-003' => $this->metrics(985933, 3364089)],
        ])]);

        $r = app(RoadStarSyncService::class)->sync([$a, $b]);

        Http::assertSentCount(1);                       // one request for the batch
        $this->assertSame('success', $r['results'][$a]['status']);
        $this->assertSame(985933, RoadStarAudienceData::where('media_id', $b)->value('unique_reach'));
    }

    public function test_resyncing_the_same_period_updates_instead_of_duplicating(): void
    {
        $id = $this->media(['roadstar_site_id' => 'S-1']);
        Http::fakeSequence(self::BASE . '/sitedata')
            ->push([['S-1' => $this->metrics(100, 200)]])
            ->push([['S-1' => $this->metrics(300, 400)]]);

        app(RoadStarSyncService::class)->sync([$id]);
        app(RoadStarSyncService::class)->sync([$id]);

        $this->assertSame(1, RoadStarAudienceData::where('media_id', $id)->count());
        $this->assertSame(300, RoadStarAudienceData::where('media_id', $id)->value('unique_reach'));
    }

    public function test_unregistered_site_is_recorded_as_not_available(): void
    {
        $id = $this->media(['roadstar_site_id' => 'CWMS/MUM/WEH-009']);
        Http::fake([self::BASE . '/sitedata' => Http::response([['CWMS/MUM/WEH-009' => 'Site not available. Please add the site first']])]);

        $r = app(RoadStarSyncService::class)->sync([$id]);

        $this->assertSame('not_available', $r['results'][$id]['status']);
        $media = DB::table('media_management')->find($id);
        $this->assertSame('not_available', $media->roadstar_sync_status);
        $this->assertNotNull($media->roadstar_last_synced_at);     // definitive: waits 15 days
        $this->assertStringContainsString('add the site', $media->roadstar_sync_error);
        $this->assertSame(0, RoadStarAudienceData::count());
    }

    public function test_null_value_means_no_data_for_the_period(): void
    {
        $id = $this->media(['roadstar_site_id' => 'S-1']);
        Http::fake([self::BASE . '/sitedata' => Http::response([['S-1' => null]])]);

        $r = app(RoadStarSyncService::class)->sync([$id]);

        $this->assertSame('no_data', $r['results'][$id]['status']);
        $this->assertSame(0, RoadStarAudienceData::count());
    }

    public function test_site_missing_from_response_and_rejected_period_are_failures(): void
    {
        $a = $this->media(['roadstar_site_id' => 'S-1']);
        $b = $this->media(['roadstar_site_id' => 'S-2']);
        Http::fake([self::BASE . '/sitedata' => Http::response([
            ['S-2' => 'error : The data is not available for the start date selected. This api is designed to provide you data from 15-02-2024'],
        ])]);

        $r = app(RoadStarSyncService::class)->sync([$a, $b]);

        $this->assertSame('failed', $r['results'][$a]['status']);
        $this->assertStringContainsString('did not include', $r['results'][$a]['message']);
        $this->assertSame('failed', $r['results'][$b]['status']);
        $this->assertStringContainsString('15-02-2024', DB::table('media_management')->where('id', $b)->value('roadstar_sync_error'));
        $this->assertNull(DB::table('media_management')->where('id', $b)->value('roadstar_last_synced_at'));
    }

    public function test_malformed_metrics_are_rejected(): void
    {
        $parsed = RoadStarSiteDataParser::parse(json_encode([['not-a-number'], [], 'x']));
        $this->assertSame(RoadStarSiteDataParser::INVALID, $parsed['state']);

        $parsed = RoadStarSiteDataParser::parse(['site' => 'object, not list']);
        $this->assertSame(RoadStarSiteDataParser::INVALID, $parsed['state']);
    }

    /* ============================ HTTP / NETWORK ERRORS ============================ */

    public function test_http_500_marks_hoardings_failed_and_keeps_previous_audience(): void
    {
        $id = $this->media(['roadstar_site_id' => 'S-1']);
        Http::fakeSequence(self::BASE . '/sitedata')
            ->push([['S-1' => $this->metrics()]])
            ->push(['error' => 'An unexpected error occurred'], 500);

        app(RoadStarSyncService::class)->sync([$id]);
        $r = app(RoadStarSyncService::class)->sync([$id]);

        $this->assertFalse($r['ok']);
        $this->assertTrue($r['retryable']);
        $this->assertSame(RoadStarService::ERR_SERVER, $r['error_type']);
        $this->assertSame('failed', DB::table('media_management')->where('id', $id)->value('roadstar_sync_status'));
        $this->assertSame(1, RoadStarAudienceData::count());        // last good data kept
    }

    public function test_timeout_is_a_retryable_connection_error(): void
    {
        $id = $this->media(['roadstar_site_id' => 'S-1']);
        Http::fake(fn() => throw new ConnectionException('cURL error 28: Operation timed out after 60000 milliseconds'));

        $r = app(RoadStarSyncService::class)->sync([$id]);

        $this->assertFalse($r['ok']);
        $this->assertTrue($r['retryable']);
        $this->assertSame(RoadStarService::ERR_CONNECTION, $r['error_type']);
        $this->assertStringContainsString('Could not reach RoadStar', $r['message']);
    }

    public function test_http_429_is_rate_limited_with_retry_after(): void
    {
        Http::fake([self::BASE . '/sitedata' => Http::response(['error' => 'Too many requests'], 429, ['Retry-After' => '120'])]);

        $r = app(RoadStarService::class)->siteData([['arr' => 'S-1', 'st' => '2024-03-01', 'ed' => '2024-03-31']]);

        $this->assertSame(RoadStarService::ERR_RATE_LIMITED, $r['error_type']);
        $this->assertSame(120, $r['retry_after']);
        $this->assertSame('quota_exceeded', ApiUsageLog::value('status'));
    }

    public function test_400_403_and_404_map_to_their_error_types(): void
    {
        Http::fakeSequence(self::BASE . '/sitedata')
            ->push(['error' => 'Invalid data format; send request as a nested array.'], 400)
            ->push(['error' => 'Forbidden'], 403)
            ->push('Not Found', 404);
        $client = app(RoadStarService::class);
        $site = [['arr' => 'S-1', 'st' => '2024-03-01', 'ed' => '2024-03-31']];

        $bad = $client->siteData($site);
        $this->assertSame(RoadStarService::ERR_BAD_REQUEST, $bad['error_type']);
        $this->assertStringContainsString('Invalid data format', $bad['error']);
        $this->assertSame(RoadStarService::ERR_FORBIDDEN, $client->siteData($site)['error_type']);
        $this->assertSame(RoadStarService::ERR_NOT_FOUND, $client->siteData($site)['error_type']);
    }

    public function test_invalid_json_is_an_invalid_response(): void
    {
        Http::fake([self::BASE . '/sitedata' => Http::response('<html>Bad Gateway page</html>', 200)]);

        $r = app(RoadStarService::class)->siteData([['arr' => 'S-1', 'st' => '2024-03-01', 'ed' => '2024-03-31']]);

        $this->assertFalse($r['ok']);
        $this->assertSame(RoadStarService::ERR_INVALID_RESPONSE, $r['error_type']);
    }

    public function test_invalid_dates_are_refused_before_sending(): void
    {
        Http::fake();

        $r = app(RoadStarService::class)->siteData([['arr' => 'S-1', 'st' => '2024-13-01', 'ed' => '2024-13-31']]);

        $this->assertSame(RoadStarService::ERR_INVALID_INPUT, $r['error_type']);
        Http::assertNothingSent();
    }

    /* ============================ QUEUE JOB ============================ */

    public function test_job_throws_on_retryable_errors_so_the_queue_backs_off(): void
    {
        $id = $this->media(['roadstar_site_id' => 'S-1']);
        Http::fake([self::BASE . '/sitedata' => Http::response(['error' => 'An unexpected error occurred'], 500)]);
        $job = new SyncRoadStarSites([$id]);

        $this->assertSame(5, $job->tries);
        $this->assertSame([60, 300, 900, 1800], $job->backoff);
        $this->assertSame('roadstar', $job->queue);

        $this->expectException(RuntimeException::class);
        $job->handle(app(RoadStarSyncService::class));
    }

    public function test_job_fails_immediately_on_bad_credentials(): void
    {
        $id = $this->media(['roadstar_site_id' => 'S-1']);
        Http::fake([self::BASE . '/sitedata' => Http::response(['error' => 'Unauthorized access. Please provide valid credentials.'], 401)]);

        $job = (new SyncRoadStarSites([$id]))->withFakeQueueInteractions();
        $job->handle(app(RoadStarSyncService::class));

        $job->assertFailed();
    }

    public function test_job_is_released_after_retry_after_on_429(): void
    {
        $id = $this->media(['roadstar_site_id' => 'S-1']);
        Http::fake([self::BASE . '/sitedata' => Http::response(['error' => 'slow down'], 429, ['Retry-After' => '90'])]);

        $job = (new SyncRoadStarSites([$id]))->withFakeQueueInteractions();
        $job->handle(app(RoadStarSyncService::class));

        $job->assertReleased(90);
    }

    public function test_job_succeeds_and_failed_hook_marks_queued_rows(): void
    {
        $id = $this->media(['roadstar_site_id' => 'S-1']);
        app(RoadStarSyncService::class)->markQueued([$id]);

        (new SyncRoadStarSites([$id]))->failed(new RuntimeException('gave up'));

        $media = DB::table('media_management')->find($id);
        $this->assertSame('failed', $media->roadstar_sync_status);
        $this->assertSame('gave up', $media->roadstar_sync_error);
    }

    /* ============================ DUPLICATE PROTECTION ============================ */

    public function test_a_hoarding_already_being_synced_is_skipped(): void
    {
        $id = $this->media(['roadstar_site_id' => 'S-1']);
        Http::fake();
        $lock = Cache::lock('roadstar-sync-media-' . $id, 300);
        $lock->get();

        $r = app(RoadStarSyncService::class)->sync([$id]);

        $this->assertSame('busy', $r['results'][$id]['status']);
        Http::assertNothingSent();
        $lock->release();
    }

    public function test_jobs_for_the_same_batch_are_unique(): void
    {
        $this->assertSame((new SyncRoadStarSites([3, 1, 2]))->uniqueId(), (new SyncRoadStarSites([1, 2, 3, 3]))->uniqueId());
    }

    public function test_command_does_not_queue_a_hoarding_twice(): void
    {
        Queue::fake();
        $this->media(['roadstar_site_id' => 'S-1']);

        $this->artisan('roadstar:sync')->assertSuccessful();
        $this->artisan('roadstar:sync')->assertSuccessful();

        Queue::assertPushed(SyncRoadStarSites::class, 1);
        Queue::assertPushedOn('roadstar', SyncRoadStarSites::class);
    }

    /* ============================ 15-DAY CONDITION ============================ */

    public function test_only_never_synced_or_older_than_15_days_are_due(): void
    {
        $never   = $this->media(['roadstar_site_id' => 'S-1']);
        $old     = $this->media(['roadstar_site_id' => 'S-2', 'roadstar_last_synced_at' => now()->subDays(16)]);
        $edge    = $this->media(['roadstar_site_id' => 'S-3', 'roadstar_last_synced_at' => now()->subDays(15)->subMinute()]);
        $recent  = $this->media(['roadstar_site_id' => 'S-4', 'roadstar_last_synced_at' => now()->subDays(10)]);
        $retried = $this->media(['roadstar_site_id' => 'S-5', 'roadstar_last_attempt_at' => now()->subHours(2)]);
        $retryOk = $this->media(['roadstar_site_id' => 'S-6', 'roadstar_last_attempt_at' => now()->subHours(25)]);
        $this->media(['roadstar_site_id' => null]);                               // unmapped
        $this->media(['roadstar_site_id' => 'S-7', 'is_deleted' => 1]);          // deleted
        $this->media(['roadstar_site_id' => 'S-8', 'is_active' => 0]);           // inactive

        $due = app(RoadStarSyncService::class)->dueQuery()->pluck('id')->map(fn($id) => (int) $id)->sort()->values()->all();

        $this->assertSame([$never, $old, $edge, $retryOk], $due);
        $this->assertNotContains($recent, $due);
        $this->assertNotContains($retried, $due);
    }

    public function test_command_batches_due_hoardings_and_respects_the_limit(): void
    {
        Queue::fake();
        config(['services.roadstar.batch_size' => 2]);
        foreach (range(1, 5) as $n) {
            $this->media(['roadstar_site_id' => 'S-' . $n]);
        }

        $this->artisan('roadstar:sync', ['--limit' => 3])->assertSuccessful();

        Queue::assertPushed(SyncRoadStarSites::class, 2);    // 2 + 1
        $this->assertSame(3, DB::table('media_management')->where('roadstar_sync_status', 'queued')->count());
    }

    public function test_command_sync_option_processes_in_process_and_continues_past_failures(): void
    {
        config(['services.roadstar.batch_size' => 1]);
        $a = $this->media(['roadstar_site_id' => 'S-1']);
        $b = $this->media(['roadstar_site_id' => 'S-2']);
        Http::fake([self::BASE . '/sitedata' => function (HttpRequest $request) {
            return $request->data()[0]['arr'] === 'S-1'
                ? Http::response([['S-1' => 'error : rejected']])
                : Http::response([['S-2' => $this->metrics()]]);
        }]);

        $this->artisan('roadstar:sync', ['--sync' => true])->assertSuccessful();

        $this->assertSame('failed', DB::table('media_management')->where('id', $a)->value('roadstar_sync_status'));
        $this->assertSame('success', DB::table('media_management')->where('id', $b)->value('roadstar_sync_status'));
    }

    public function test_period_uses_rolling_window_when_no_fixed_dates(): void
    {
        config(['services.roadstar.period_start' => null, 'services.roadstar.period_end' => null,
            'services.roadstar.period_days' => 30, 'services.roadstar.period_end_offset_days' => 1]);
        $this->travelTo(now()->setDate(2026, 10, 5));

        $this->assertSame(['start' => '2026-09-05', 'end' => '2026-10-04'], app(RoadStarSyncService::class)->period());
    }

    /* ============================ MAPPING / REGISTER ============================ */

    public function test_register_adds_site_and_stores_mapping(): void
    {
        $id = $this->media(['hoarding_code' => 'HD000034']);
        Http::fake([
            self::BASE . '/sitedata' => Http::response([['BI/HD000034' => 'Site not available.Please add the site first']]),
            self::BASE . '/addsite'  => Http::response(['BI/HD000034' => 'Site added successfully']),
        ]);

        $r = app(RoadStarSyncService::class)->register($id);

        $this->assertTrue($r['ok']);
        $this->assertTrue($r['added']);
        Http::assertSent(fn(HttpRequest $req) => $req->url() === self::BASE . '/addsite'
            && $req->data() === ['arr' => 'BI/HD000034', 'latitude' => 20.0061, 'longitude' => 73.7379]);
        $this->assertSame('BI/HD000034', DB::table('media_management')->where('id', $id)->value('roadstar_site_id'));
    }

    public function test_register_never_adds_a_site_roadstar_already_has(): void
    {
        $id = $this->media(['hoarding_code' => 'HD000036']);
        Http::fake([
            self::BASE . '/sitedata' => Http::response([['BI/HD000036' => null]]),     // known site, no data for period
            self::BASE . '/addsite'  => Http::response(['BI/HD000036' => 'Site added successfully']),
        ]);

        $r = app(RoadStarSyncService::class)->register($id);

        $this->assertTrue($r['ok']);
        $this->assertFalse($r['added']);
        Http::assertNotSent(fn(HttpRequest $req) => str_ends_with($req->url(), '/addsite'));
        $this->assertSame('BI/HD000036', DB::table('media_management')->where('id', $id)->value('roadstar_site_id'));
    }

    public function test_register_does_not_add_when_existence_cannot_be_checked(): void
    {
        $id = $this->media(['hoarding_code' => 'HD000037']);
        Http::fake([self::BASE . '/sitedata' => Http::response(['error' => 'An unexpected error occurred'], 500)]);

        $r = app(RoadStarSyncService::class)->register($id);

        $this->assertFalse($r['ok']);
        Http::assertNotSent(fn(HttpRequest $req) => str_ends_with($req->url(), '/addsite'));
    }

    public function test_register_refuses_missing_coordinates(): void
    {
        $id = $this->media(['latitude' => null, 'longitude' => null]);
        Http::fake();

        $r = app(RoadStarSyncService::class)->register($id);

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('latitude/longitude', $r['message']);
        Http::assertNothingSent();
    }

    public function test_register_outside_boundary_keeps_hoarding_unmapped(): void
    {
        $id = $this->media(['hoarding_code' => 'HD000035']);
        Http::fake([
            self::BASE . '/sitedata' => Http::response([['BI/HD000035' => 'Site not available.Please add the site first']]),
            self::BASE . '/addsite'  => Http::response(['error' => 'Entered latitude, longitude is out of our boundary!']),
        ]);

        $r = app(RoadStarSyncService::class)->register($id);

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('out of our boundary', $r['message']);
        $this->assertNull(DB::table('media_management')->where('id', $id)->value('roadstar_site_id'));
    }

    public function test_one_site_id_cannot_map_two_hoardings(): void
    {
        $this->media(['roadstar_site_id' => 'S-1']);
        $other = $this->media();

        $r = app(RoadStarSyncService::class)->map($other, 'S-1');

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('already mapped', $r['message']);
    }

    /* ============================ ADMIN ENDPOINTS ============================ */

    public function test_admin_endpoints_require_admin_session(): void
    {
        $enc = base64_encode((string) $this->media(['roadstar_site_id' => 'S-1']));
        Http::fake();

        $this->get("http://localhost/media/roadstar/{$enc}/status")->assertRedirect(route('login'));
        $this->get('http://localhost/media/roadstar/test-connection')->assertRedirect(route('login'));
        $this->post("http://localhost/media/roadstar/{$enc}/sync")->assertRedirect(route('login'));
        Http::assertNothingSent();
    }

    public function test_admin_sync_button_and_status(): void
    {
        $id = $this->media(['roadstar_site_id' => 'CWMS/MUM/WEH-019']);
        $enc = base64_encode((string) $id);
        Http::fake([self::BASE . '/sitedata' => Http::response([['CWMS/MUM/WEH-019' => json_encode($this->metrics())]])]);

        $this->withSession(['user_id' => 7])->postJson("http://localhost/media/roadstar/{$enc}/sync")
            ->assertOk()->assertJson(['ok' => true, 'status' => 'success']);

        $status = $this->withSession(['user_id' => 7])->getJson("http://localhost/media/roadstar/{$enc}/status");
        $status->assertOk()
            ->assertJsonPath('roadstar_site_id', 'CWMS/MUM/WEH-019')
            ->assertJsonPath('sync_status', 'success')
            ->assertJsonPath('audience.unique_reach', 776044)
            ->assertJsonMissingPath('audience.raw_response');
        $this->assertSame(7, ApiUsageLog::value('triggered_by'));
    }

    public function test_admin_site_data_preview_does_not_store(): void
    {
        $id = $this->media(['roadstar_site_id' => 'S-1']);
        Http::fake([self::BASE . '/sitedata' => Http::response([['S-1' => json_encode($this->metrics())]])]);

        $this->withSession(['user_id' => 1])->getJson('http://localhost/media/roadstar/' . base64_encode((string) $id) . '/site-data')
            ->assertOk()
            ->assertJsonPath('roadstar_site_id', 'S-1')
            ->assertJsonPath('status', 'data')
            ->assertJsonPath('metrics.impressions', 3138517);

        $this->assertSame(0, RoadStarAudienceData::count());
    }

    public function test_admin_rejects_bad_ids_and_site_ids(): void
    {
        $id = $this->media();

        $this->withSession(['user_id' => 1])->postJson('http://localhost/media/roadstar/' . base64_encode('abc') . '/sync')->assertNotFound();
        $this->withSession(['user_id' => 1])->postJson('http://localhost/media/roadstar/' . base64_encode((string) $id) . '/map', ['roadstar_site_id' => '<script>'])
            ->assertStatus(422);
        $this->withSession(['user_id' => 1])->postJson('http://localhost/media/roadstar/sync-bulk', ['media_ids' => ['x']])->assertStatus(422);
    }

    public function test_admin_bulk_sync_queues_only_mapped_hoardings(): void
    {
        Queue::fake();
        $mapped = $this->media(['roadstar_site_id' => 'S-1']);
        $unmapped = $this->media();

        $this->withSession(['user_id' => 1])->postJson('http://localhost/media/roadstar/sync-bulk', ['media_ids' => [$mapped, $unmapped]])
            ->assertOk()
            ->assertJsonPath('queued', [$mapped])
            ->assertJsonPath('not_mapped', [$unmapped]);

        Queue::assertPushed(SyncRoadStarSites::class, fn($job) => $job->mediaIds === [$mapped]);
    }

    /* ============================ SINGLE-MEDIA FLOW ============================ */

    public function test_single_media_maps_given_site_id_syncs_and_returns_saved_result(): void
    {
        $id = $this->media();
        Http::fake([self::BASE . '/sitedata' => Http::response([['CWMS/MUM/WEH-019' => json_encode($this->metrics())]])]);

        $r = app(RoadStarSyncService::class)->syncOne($id, 'CWMS/MUM/WEH-019');

        $this->assertTrue($r['ok']);
        $this->assertSame('success', $r['status']);
        $this->assertSame('CWMS/MUM/WEH-019', $r['roadstar']['roadstar_site_id']);
        $this->assertSame(776044, $r['roadstar']['audience']['unique_reach']);
        $this->assertGreaterThanOrEqual(4, count($r['steps']));
        Http::assertNotSent(fn(HttpRequest $req) => str_ends_with($req->url(), '/addsite'));
    }

    public function test_single_media_without_site_id_stops_unless_register_is_allowed(): void
    {
        $id = $this->media(['hoarding_code' => 'HD000040']);
        Http::fake();

        $r = app(RoadStarSyncService::class)->syncOne($id);

        $this->assertSame('not_mapped', $r['status']);
        $this->assertStringContainsString('BI/HD000040', $r['message']);
        Http::assertNothingSent();
    }

    public function test_single_media_registers_only_when_roadstar_says_not_available(): void
    {
        $id = $this->media(['hoarding_code' => 'HD000041']);
        Http::fake([
            self::BASE . '/sitedata' => Http::sequence()
                ->push([['BI/HD000041' => 'Site not available.Please add the site first']])
                ->push([['BI/HD000041' => json_encode($this->metrics())]]),
            self::BASE . '/addsite' => Http::response(['BI/HD000041' => 'Site added successfully']),
        ]);

        $r = app(RoadStarSyncService::class)->syncOne($id, null, true);

        $this->assertTrue($r['ok']);
        Http::assertSentCount(3);   // sitedata → addsite → sitedata
        $this->assertSame('success', DB::table('media_management')->where('id', $id)->value('roadstar_sync_status'));
    }

    public function test_single_media_not_registered_without_register_flag_does_not_add(): void
    {
        $id = $this->media(['roadstar_site_id' => 'BI/HD000042']);
        Http::fake([self::BASE . '/sitedata' => Http::response([['BI/HD000042' => 'Site not available.Please add the site first']])]);

        $r = app(RoadStarSyncService::class)->syncOne($id);

        $this->assertSame('not_available', $r['status']);
        Http::assertNotSent(fn(HttpRequest $req) => str_ends_with($req->url(), '/addsite'));
    }

    public function test_single_media_not_found(): void
    {
        Http::fake();

        $this->assertSame('not_found', app(RoadStarSyncService::class)->syncOne(999)['status']);
        Http::assertNothingSent();
    }

    public function test_command_tests_one_media_record(): void
    {
        $id = $this->media();
        Http::fake([self::BASE . '/sitedata' => Http::response([['CWMS/MUM/WEH-019' => json_encode($this->metrics())]])]);

        $this->artisan('roadstar:sync', ['--media' => [$id], '--site-id' => 'CWMS/MUM/WEH-019'])
            ->expectsOutputToContain('Result: success')
            ->assertSuccessful();
    }

    /* ============================ BULK GATE ============================ */

    public function test_bulk_sync_is_off_by_default(): void
    {
        config(['services.roadstar.bulk_enabled' => false]);
        Queue::fake();
        $id = $this->media(['roadstar_site_id' => 'S-1']);

        $this->artisan('roadstar:sync')->expectsOutputToContain('Bulk sync is off')->assertSuccessful();
        $this->withSession(['user_id' => 1])->postJson('http://localhost/media/roadstar/sync-bulk', ['media_ids' => [$id]])
            ->assertStatus(409);

        Queue::assertNothingPushed();
    }

    /* ============================ RELATIONSHIP ============================ */

    public function test_latest_roadstar_audience_relationship_returns_newest_row(): void
    {
        $id = $this->media(['roadstar_site_id' => 'S-1']);
        foreach ([['2024-02-15', '2024-02-29', 5], ['2024-03-01', '2024-03-31', 1], ['2024-01-01', '2024-01-31', 10]] as [$st, $ed, $daysAgo]) {
            RoadStarAudienceData::create([
                'media_id' => $id, 'roadstar_site_id' => 'S-1', 'period_start' => $st, 'period_end' => $ed,
                'unique_reach' => $daysAgo, 'impressions' => 1, 'fetched_at' => now()->subDays($daysAgo),
            ]);
        }

        $media = \App\Models\MediaManagement::find($id);

        $this->assertCount(3, $media->roadStarAudience);
        $this->assertSame('2024-03-01', $media->latestRoadStarAudience->period_start->toDateString());
    }

    /* ============================ MEDIA DETAILS (WEBSITE) ============================ */

    public function test_website_sync_button_is_admin_only(): void
    {
        $id = $this->media(['roadstar_site_id' => 'S-1']);
        Http::fake();

        $this->postJson('http://localhost/media-details/' . base64_encode((string) $id) . '/roadstar/sync')->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_website_sync_button_syncs_for_site_admin(): void
    {
        $id = $this->media(['roadstar_site_id' => 'S-1']);
        Http::fake([self::BASE . '/sitedata' => Http::response([['S-1' => json_encode($this->metrics())]])]);

        $res = $this->withSession(['site_admin_id' => 1])
            ->postJson('http://localhost/media-details/' . base64_encode((string) $id) . '/roadstar/sync');

        $res->assertOk()->assertJson(['ok' => true, 'status' => 'success']);
        $this->assertStringNotContainsString(self::PASS, $res->getContent());
        $this->assertSame(1, RoadStarAudienceData::where('media_id', $id)->count());
    }

    public function test_audience_partial_shows_only_returned_roadstar_fields(): void
    {
        $id = $this->media(['roadstar_site_id' => 'S-1']);
        Http::fake([self::BASE . '/sitedata' => Http::response([['S-1' => json_encode($this->metrics())]])]);
        app(RoadStarSyncService::class)->sync([$id]);

        $html = view('roadstar.audience', ['audience' => app(RoadStarSyncService::class)->forDisplay($id)['audience']])->render();

        foreach (['Unique Reach', '776,044', 'Impressions', '3,138,517', 'Frequency', '4.04', '2024-03-01 → 2024-03-31',
                     'Age Group', 'Gender', 'Mobile Affluence', 'Mobile Phone Brand', 'Effective Frequency (OTS)', 'Average Impressions by Hour'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringNotContainsString(self::PASS, $html);
    }

    /* ============================ HELPERS ============================ */

    /** The 13 documented /sitedata sections (two days of dates for brevity). */
    private function metrics(int $reach = 776044, int $impressions = 3138517): array
    {
        return [
            [(string) $reach],
            [(string) $impressions],
            4.04,
            [['2024-03-01', '2024-03-02'], ['100268', '92020']],
            [['Mon', 'Tues', 'Weds', 'Thurs', 'Fri', 'Sat', 'Sun'], ['318368', '405637', '439755', '424218', '532103', '543101', '475335']],
            [['Mar'], [(string) $impressions]],
            [[0, 1, 2], [3853, 2290, 1089]],
            [['1+', '2+', '3+', '4+', '5+'], ['776043', '282550', '170167', '120473', '89569']],
            [['WeekDays', 'WeekEnds'], ['13677', '16426']],
            [['18-25', '26-35', '36-45', 'Above_45'], [32.0, 35.0, 19.0, 14.0]],
            [['Male', 'Female'], [65, 35]],
            [['10k-20k', '20k-30k', 'Above50k', 'Upto10k', '30k-40k'], [37.0, 26.0, 15.0, 12.0, 10.0]],
            [['Xiaomi', 'Samsung', 'Vivo', 'Oppo', 'OnePlus'], [23.0, 19.0, 15.0, 14.0, 9.0]],
        ];
    }

    private function media(array $overrides = []): int
    {
        return DB::table('media_management')->insertGetId(array_merge([
            'latitude' => 20.0061, 'longitude' => 73.7379, 'hoarding_code' => null,
            'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now(),
        ], $overrides));
    }

    private function createSchema(): void
    {
        Schema::create('media_management', function (Blueprint $t) {
            $t->id();
            $t->string('hoarding_code', 20)->nullable();
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->tinyInteger('is_active')->default(1);
            $t->tinyInteger('is_deleted')->default(0);
            $t->timestamps();
        });

        // The real migrations under test.
        (require database_path('migrations/2026_09_26_100000_create_media_insights_tables.php'))->up();
        (require database_path('migrations/2026_10_05_100000_add_roadstar_columns_to_media_management.php'))->up();
        (require database_path('migrations/2026_10_05_100001_create_roadstar_audience_data_table.php'))->up();
    }
}

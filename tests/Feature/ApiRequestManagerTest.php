<?php

namespace Tests\Feature;

use App\Http\Services\Insights\HoardingTrafficService;
use App\Http\Services\Insights\MediaInsightsService;
use App\Models\ApiUsageLog;
use App\Models\MediaManagement;
use App\Support\ApiControl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Super Admin API Request Manager: switches, daily limits, run now and the log.
 *
 * Runs against the development MySQL database inside a transaction that is
 * rolled back. Every outgoing HTTP call is faked: nothing reaches Geoapify or TomTom.
 */
class ApiRequestManagerTest extends TestCase
{
    private MediaManagement $media;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.port' => '3307',
            'database.connections.mysql.database' => 'new_brand_image',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
        ]);
        DB::purge('mysql');
        try {
            DB::connection('mysql')->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Development MySQL database is not reachable.');
        }

        $template = DB::table('media_management')->where('is_deleted', 0)->first();
        if (!$template) {
            $this->markTestSkipped('Needs at least one media row in the development database.');
        }

        DB::beginTransaction();
        $this->get('/test'); // first request in this test setup never matches a route

        // Start from known settings; keys are fake and HTTP is faked.
        config(['services.tomtom.api_key' => 'test-key', 'services.geoapify.api_key' => 'test-key']);
        ApiControl::save($this->defaults());

        $row = (array) $template;
        unset($row['id'], $row['roadstar_site_id']);
        $this->media = MediaManagement::forceCreate(array_merge($row, [
            'media_title'   => 'API Test Site',
            'hoarding_code' => 'HDAPI' . random_int(10000, 99999),
            'latitude'      => 19.0760 + random_int(1, 999) / 100000,
            'longitude'     => 72.8777,
            'is_active'     => 1,
            'is_deleted'    => 0,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]));
    }

    protected function tearDown(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        parent::tearDown();
    }

    private function defaults(array $over = []): array
    {
        return array_merge([
            'places_provider' => 'geoapify', 'geoapify_enabled' => true, 'tomtom_enabled' => true, 'tomtom_traffic_enabled' => true,
            'geoapify_daily_limit' => 3000, 'tomtom_daily_limit' => 2000, 'tomtom_traffic_daily_limit' => 2000, 'tomtom_plan_daily_limit' => 2500,
            'places_auto' => true, 'traffic_auto' => true, 'fetch_on_view' => true, 'places_cache_days' => 8, 'traffic_refresh_days' => 8,
            'geoapify_places' => 60, 'spread_evenly' => true, 'auto_cycle' => false, // these tests set the days by hand
        ], $over);
    }

    private function asAdmin(): static
    {
        return $this->withSession(['user_id' => DB::table('users')->value('id') ?? 1, 'name' => 'Test Admin']);
    }

    private function fakeTomTom(): void
    {
        Http::fake(['*' => Http::response(['flowSegmentData' => [
            'frc' => 'FRC2', 'currentSpeed' => 30, 'freeFlowSpeed' => 50, 'currentTravelTime' => 120,
            'freeFlowTravelTime' => 72, 'confidence' => 0.9, 'roadClosure' => false,
        ]], 200)]);
    }

    public function test_page_needs_admin_and_renders(): void
    {
        $this->get(route('media.insights.usage'))->assertRedirect();

        $this->asAdmin()->get(route('media.insights.usage'))
            ->assertOk()
            ->assertSee('API Request Manager')
            ->assertSee('Geoapify Places')
            ->assertSee('TomTom Traffic')
            ->assertSee('Request log');

        $this->asAdmin()->get(route('media.insights.usage', ['service' => 'tomtom_traffic', 'outcome' => 'disabled', 'per_page' => 25]))->assertOk();
    }

    public function test_admin_saves_switches_and_limits(): void
    {
        $this->asAdmin()->post(route('media.insights.usage.save'), [
            'places_provider' => 'tomtom', 'geoapify_daily_limit' => 1200, 'tomtom_daily_limit' => 900,
            'tomtom_traffic_daily_limit' => 300, 'tomtom_plan_daily_limit' => 1000, 'places_cache_days' => 30,
            'traffic_refresh_days' => 7, 'geoapify_places' => 40, 'spread_evenly' => '1', 'tomtom_enabled' => '1', 'places_auto' => '1',
            // geoapify_enabled, tomtom_traffic_enabled, traffic_auto, fetch_on_view unticked
        ])->assertRedirect(route('media.insights.usage'))->assertSessionHas('success');

        $s = ApiControl::settings();
        $this->assertSame('tomtom', $s['places_provider']);
        $this->assertSame(300, $s['tomtom_traffic_daily_limit']);
        $this->assertSame(1000, $s['tomtom_plan_daily_limit']);
        $this->assertSame(40, $s['geoapify_places']);
        $this->assertSame(40, config('services.geoapify.limit'));
        $this->assertTrue($s['spread_evenly']);
        $this->assertFalse($s['geoapify_enabled']);
        $this->assertFalse($s['tomtom_traffic_enabled']);
        $this->assertTrue($s['places_auto']);
        $this->assertFalse($s['traffic_auto']);
        $this->assertFalse(ApiControl::enabled('tomtom_traffic'));
        $this->assertFalse(ApiControl::autoRun('traffic'));
        $this->assertSame('tomtom', config('services.insights.places_provider'));

        $this->asAdmin()->post(route('media.insights.usage.save'), ['places_provider' => 'other', 'geoapify_daily_limit' => -1])
            ->assertSessionHasErrors(['places_provider', 'geoapify_daily_limit']);
    }

    public function test_switched_off_traffic_sends_nothing_and_is_logged(): void
    {
        Http::fake();
        ApiControl::save($this->defaults(['tomtom_traffic_enabled' => false]));

        $r = app(HoardingTrafficService::class)->fetchAndStore($this->media->id, 'admin', 1);

        $this->assertSame('disabled', $r['status']);
        Http::assertNothingSent();
        $this->assertDatabaseHas('api_usage_logs', ['provider' => 'tomtom_traffic', 'media_id' => $this->media->id, 'status' => ApiUsageLog::DISABLED, 'credits_consumed' => 0]);
    }

    public function test_switched_off_places_sends_nothing(): void
    {
        Http::fake();
        ApiControl::save($this->defaults(['geoapify_enabled' => false]));

        $r = app(MediaInsightsService::class)->refresh($this->media->id, 1, 'admin');

        $this->assertSame(ApiUsageLog::DISABLED, $r['status']);
        Http::assertNothingSent();
        $this->assertDatabaseHas('api_usage_logs', ['provider' => 'geoapify', 'media_id' => $this->media->id, 'status' => ApiUsageLog::DISABLED]);
    }

    public function test_traffic_request_is_logged_as_one_hit(): void
    {
        $this->fakeTomTom();
        $before = ApiControl::usedToday('tomtom_traffic');

        $r = app(HoardingTrafficService::class)->fetchAndStore($this->media->id, 'admin', 1);

        $this->assertSame('success', $r['status'], $r['message'] ?? '');
        Http::assertSentCount(1);
        $this->assertSame($before + 1, ApiControl::usedToday('tomtom_traffic'));
        $log = ApiUsageLog::where('provider', 'tomtom_traffic')->where('media_id', $this->media->id)->latest('id')->first();
        $this->assertSame(ApiUsageLog::SUCCESS, $log->status);
        $this->assertSame(200, (int) $log->http_status);
        $this->assertStringNotContainsString('test-key', (string) $log->error_message);
    }

    public function test_shared_tomtom_plan_blocks_traffic(): void
    {
        Http::fake();
        $used = ApiControl::usedToday('tomtom') + ApiControl::usedToday('tomtom_traffic');
        ApiUsageLog::create(['provider' => 'tomtom', 'status' => ApiUsageLog::SUCCESS, 'credits_consumed' => 5, 'source' => 'scheduler', 'requested_at' => now()]);
        ApiControl::save($this->defaults(['tomtom_plan_daily_limit' => $used + 5]));

        $this->assertSame(0, ApiControl::tomTomShared()['remaining']);
        $r = app(HoardingTrafficService::class)->fetchAndStore($this->media->id, 'scheduler');

        $this->assertSame('limit', $r['status']);
        Http::assertNothingSent();
        $this->assertDatabaseHas('api_usage_logs', ['provider' => 'tomtom_traffic', 'media_id' => $this->media->id, 'status' => ApiUsageLog::QUOTA_BLOCKED]);
    }

    public function test_run_one_traffic_from_the_page(): void
    {
        $this->fakeTomTom();

        $this->asAdmin()->from(route('media.insights.usage'))
            ->post(route('media.insights.usage.run-one'), ['media' => $this->media->hoarding_code, 'what' => 'traffic'])
            ->assertRedirect(route('media.insights.usage'))
            ->assertSessionHas('success');

        Http::assertSentCount(1);
        $this->assertDatabaseHas('api_usage_logs', ['provider' => 'tomtom_traffic', 'media_id' => $this->media->id, 'source' => 'admin', 'status' => ApiUsageLog::SUCCESS]);

        $this->asAdmin()->from(route('media.insights.usage'))
            ->post(route('media.insights.usage.run-one'), ['media' => 'NO-SUCH-CODE-XYZ', 'what' => 'both'])
            ->assertSessionHas('error');
    }

    public function test_run_traffic_refuses_when_switched_off(): void
    {
        Http::fake();
        ApiControl::save($this->defaults(['tomtom_traffic_enabled' => false]));

        $this->asAdmin()->from(route('media.insights.usage'))
            ->post(route('media.insights.usage.run-traffic'), ['count' => 5])
            ->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_planner_math_for_7000_and_10000_hoardings(): void
    {
        // 7,000 every 8 days: 875 a day. Geoapify 60 places = 3 credits each.
        $p = ApiControl::plan(7000, 8, 60, 'geoapify');
        $this->assertSame(875, $p['per_day']);
        $this->assertSame(2625, $p['geoapify_credits']);
        $this->assertSame(875, $p['tomtom_requests']);
        $this->assertTrue($p['geoapify_ok'] && $p['tomtom_ok']);

        // 10,000 every 8 days at 60 places is over Geoapify's 3,000 free credits...
        $p = ApiControl::plan(10000, 8, 60, 'geoapify');
        $this->assertSame(3750, $p['geoapify_credits']);
        $this->assertFalse($p['geoapify_ok']);
        $this->assertSame(10, $p['min_days']);
        // ...but fits with 40 places (2 credits).
        $this->assertTrue(ApiControl::plan(10000, 8, 40, 'geoapify')['geoapify_ok']);

        // Places from TomTom: places + traffic share 2,500.
        $p = ApiControl::plan(10000, 8, 60, 'tomtom');
        $this->assertSame(2500, $p['tomtom_requests']);
        $this->assertTrue($p['tomtom_ok']);
        $this->assertFalse(ApiControl::plan(10000, 7, 60, 'tomtom')['tomtom_ok']);
    }

    public function test_hourly_runs_spread_the_cycle_evenly(): void
    {
        $per = (int) ceil(ApiControl::activeHoardings() / 8 * 1.1);
        $this->assertSame($per, ApiControl::scheduleRoom('traffic') + ApiUsageLog::where('provider', 'tomtom_traffic')->where('source', 'scheduler')
            ->where('requested_at', '>=', now()->startOfDay())->where('credits_consumed', '>', 0)->count());

        ApiUsageLog::create(['provider' => 'tomtom_traffic', 'media_id' => $this->media->id, 'status' => ApiUsageLog::SUCCESS,
            'credits_consumed' => 1, 'source' => 'scheduler', 'requested_at' => now()]);
        $room = ApiControl::scheduleRoom('traffic');
        ApiUsageLog::create(['provider' => 'tomtom_traffic', 'media_id' => $this->media->id, 'status' => ApiUsageLog::SUCCESS,
            'credits_consumed' => 1, 'source' => 'viewer', 'requested_at' => now()]);
        $this->assertSame($room, ApiControl::scheduleRoom('traffic'), 'page views do not use the hourly share');

        ApiControl::save($this->defaults(['spread_evenly' => false]));
        $this->assertNull(ApiControl::scheduleRoom('traffic'));
    }

    public function test_auto_refresh_days_follow_the_hoarding_count(): void
    {
        $s = ApiControl::settings();
        $s = array_merge($s, ['places_provider' => 'geoapify', 'geoapify_places' => 60, 'geoapify_daily_limit' => 2500,
            'tomtom_traffic_daily_limit' => 2000, 'tomtom_plan_daily_limit' => 2500]);

        // 60 places = 3 credits; 80% of 2,500 = 2,000 credits a day for the hourly runs.
        $a = ApiControl::autoCycle($s, 4596);
        $this->assertSame(8, $a['places_days']);
        $this->assertLessThanOrEqual(2000, $a['places_units']);
        $b = ApiControl::autoCycle($s, 15000);
        $c = ApiControl::autoCycle($s, 20000);
        $this->assertSame(25, $b['places_days']);
        $this->assertSame(34, $c['places_days']);                // more hoardings -> longer cycle
        $this->assertLessThanOrEqual(2000, $c['places_units']);   // still inside the free plan
        $this->assertSame(14, $c['traffic_days']);                // 80% of 2,000 traffic requests
        $this->assertTrue($c['places_fits'] && $c['traffic_fits']);

        // A tiny traffic limit cannot cover 15,000 hoardings even in a year: flagged.
        $this->assertFalse(ApiControl::autoCycle(array_merge($s, ['tomtom_traffic_daily_limit' => 50]), 15000)['traffic_fits']);

        // Saved with Auto on, the hourly runs use the worked-out days, and the page explains them.
        \Illuminate\Support\Facades\Cache::forget('api_control.active_hoardings');
        ApiControl::save($this->defaults(['auto_cycle' => true, 'places_cache_days' => 99, 'traffic_refresh_days' => 99]));
        $now = ApiControl::autoCycle(ApiControl::settings());
        $this->assertSame($now['places_days'], (int) config('services.geoapify.cache_days'));
        $this->assertSame($now['traffic_days'], (int) config('services.tomtom.traffic_refresh_days'));
        $this->asAdmin()->get(route('media.insights.usage'))->assertOk()->assertSee('Auto is on')->assertSee('Auto refresh days');

        // Auto off: the hand-set days apply again.
        ApiControl::save($this->defaults(['auto_cycle' => false, 'places_cache_days' => 12]));
        $this->assertSame(12, (int) config('services.geoapify.cache_days'));
    }

    public function test_auto_refreshes_at_most_once_a_week_on_the_free_plans(): void
    {
        $s = array_merge(ApiControl::settings(), ['places_provider' => 'geoapify', 'geoapify_places' => 20, 'geoapify_daily_limit' => 2500,
            'tomtom_traffic_daily_limit' => 2000, 'tomtom_plan_daily_limit' => 2500, 'auto_min_days' => 7]);

        // 4,601 hoardings would fit in 3 days (places) / 4 days (traffic), but Auto waits a week.
        $now = ApiControl::autoCycle($s, 4601);
        $this->assertSame(7, $now['places_days']);
        $this->assertSame(7, $now['traffic_days']);
        $this->assertSame(658, $now['places_per_day']);                 // 4,601 / 7, one credit each: far under 3,000 free
        $this->assertTrue($now['places_fits'] && $now['traffic_fits']);

        // Many hoardings: the free limits need more than a week, so the cycle grows by itself.
        $big = ApiControl::autoCycle($s, 20000);
        $this->assertSame(11, $big['places_days']);
        $this->assertSame(14, $big['traffic_days']);
        $this->assertLessThanOrEqual(2000, $big['places_units']);

        // Saved from the page: every 10 days instead.
        $this->asAdmin()->post(route('media.insights.usage.save'), $this->defaults(['auto_cycle' => 1, 'auto_min_days' => 10, 'geoapify_places' => 20]))
            ->assertRedirect();
        $this->assertSame(10, ApiControl::settings()['auto_min_days']);
        $this->assertGreaterThanOrEqual(10, (int) config('services.geoapify.cache_days'));
    }

    public function test_run_times_are_set_from_super_admin(): void
    {
        // Saved from the page: places every 3 hours, traffic once a day, nights only (10 PM → 6 AM).
        $this->asAdmin()->post(route('media.insights.usage.save'), $this->defaults([
            'run_places_every' => 'every3', 'run_traffic_every' => 'daily', 'run_from_hour' => 22, 'run_to_hour' => 6,
        ]))->assertRedirect();
        $this->assertSame('0 */3 * * *', ApiControl::runCron('places'));
        $this->assertSame('0 22 * * *', ApiControl::runCron('traffic'));    // once a day, at the start of the window

        $at = fn (int $h) => now()->setTime($h, 0);
        $this->assertTrue(ApiControl::inRunWindow($at(23)));
        $this->assertTrue(ApiControl::inRunWindow($at(3)));
        $this->assertFalse(ApiControl::inRunWindow($at(12)));

        // Today's share is split over the runs still to come today, this one included.
        ApiControl::save($this->defaults(['run_places_every' => 'hourly', 'run_from_hour' => 0, 'run_to_hour' => 24]));
        $this->assertSame(100, ApiControl::runShare('places', 400, $at(20)));   // 20, 21, 22, 23 → 4 runs
        $this->assertSame(400, ApiControl::runShare('places', 400, $at(23)));   // last run of the day takes the rest
        $this->assertSame('0 * * * *', ApiControl::runCron('places'));
    }

    public function test_page_shows_whether_the_server_scheduler_runs(): void
    {
        \Illuminate\Support\Facades\Cache::forget(ApiControl::HEARTBEAT_KEY);
        $this->asAdmin()->get(route('media.insights.usage'))->assertOk()
            ->assertSee('Server scheduler not running')->assertSee('php artisan schedule:run', false)->assertSee('Automatic run times');

        \Illuminate\Support\Facades\Cache::forever(ApiControl::HEARTBEAT_KEY, now()->timestamp);
        $this->asAdmin()->get(route('media.insights.usage'))->assertOk()->assertSee('Server scheduler running');
    }

    public function test_shorter_cycle_makes_older_data_due(): void
    {
        $this->fakeTomTom();
        app(HoardingTrafficService::class)->fetchAndStore($this->media->id, 'admin', 1);
        $traffic = app(HoardingTrafficService::class);
        $this->assertFalse($traffic->dueQuery()->where('m.id', $this->media->id)->exists());

        // Fetched 10 days ago under a 15-day cycle: due now that the cycle is 8 days.
        DB::table('hoarding_traffic_data')->where('media_id', $this->media->id)
            ->update(['traffic_updated_at' => now()->subDays(10), 'next_refresh_at' => now()->addDays(5)]);
        $this->assertTrue($traffic->dueQuery()->where('m.id', $this->media->id)->exists());
    }
}

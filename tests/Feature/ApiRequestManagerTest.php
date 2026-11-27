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
            'geoapify_places' => 60, 'spread_evenly' => true,
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

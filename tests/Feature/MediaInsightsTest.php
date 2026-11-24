<?php

namespace Tests\Feature;

use App\Http\Services\Insights\MediaInsightsService;
use App\Jobs\EnrichMediaInsightsJob;
use App\Models\ApiUsageLog;
use App\Models\MediaInsight;
use App\Models\PlaceResult;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

/**
 * Media Location Insights — Geoapify (default) and SerpApi providers, budget
 * controls, refresh rules, batch/queue processing and the detail-page panel.
 *
 * Runs on the in-memory SQLite test database with only the tables it needs,
 * and every provider call is faked — these tests never spend real credits.
 */
class MediaInsightsTest extends TestCase
{
    private const GEO_KEY  = 'geo-test-key-0123456789abcdef';
    private const SERP_KEY = 'serp-test-key-0123456789abcdef';

    // A hoarding in Nashik and points around it.
    private const LAT = 20.0061;
    private const LNG = 73.7379;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.insights.places_provider'    => 'geoapify',
            'services.geoapify.key'                => self::GEO_KEY,
            'services.geoapify.base_url'           => 'https://api.geoapify.com',
            'services.geoapify.radius'             => 1000,
            'services.geoapify.limit'              => 60,
            'services.geoapify.daily_credit_limit' => 2500,
            'services.serpapi.key'                 => self::SERP_KEY,
            'services.serpapi.monthly_limit'       => 250,
            'cache.default'                        => 'array',
        ]);

        $this->createSchema();
        $this->seedMasters();
    }

    /* ============================ GEOAPIFY ============================ */

    public function test_first_view_fetches_once_then_serves_from_the_database(): void
    {
        $this->fakeGeoapify();
        $id = $this->media();

        $first = $this->getJson($this->url("/media-details/{$this->enc($id)}/insights"));
        $again = $this->getJson($this->url("/media-details/{$this->enc($id)}/insights"));
        $this->getJson($this->url("/media-details/{$this->enc($id)}/insights"));

        $first->assertOk()->assertJson(['ok' => true]);
        Http::assertSentCount(1);                                           // only the first view
        $this->assertSame('viewer', ApiUsageLog::where('status', 'success')->value('source'));
        $html = $again->json('html');
        $this->assertStringContainsString('KTHM College', $html);
        $this->assertStringContainsString('Estimated Daily Traffic', $html);
        $this->assertStringContainsString('AI Recommendations', $html);
        $this->assertStringContainsString('ROI / Value Score', $html);
        $this->assertStringNotContainsString('Refresh Insights', $html);     // guests: no button
        $this->assertStringNotContainsString(self::GEO_KEY, $html);
    }

    public function test_view_does_not_fetch_when_disabled_or_unaffordable(): void
    {
        Http::fake();
        $id = $this->media();

        config(['services.insights.fetch_on_view' => false]);
        $html = $this->getJson($this->url("/media-details/{$this->enc($id)}/insights"))->json('html');
        $this->assertStringContainsString('Nearby places have not been fetched', $html);

        config(['services.insights.fetch_on_view' => true]);
        $this->spend('geoapify', 2500, now());                             // budget used up
        $this->getJson($this->url("/media-details/{$this->enc($id)}/insights"))->assertOk();

        config(['services.geoapify.key' => '']);                           // key missing
        $this->getJson($this->url("/media-details/{$this->enc($id)}/insights"))->assertOk();

        Http::assertNothingSent();
    }

    public function test_each_hoarding_shows_only_its_own_places(): void
    {
        // Two hoardings ~3 km apart; the fake answers each request with places
        // around the coordinates it was actually asked about.
        Http::fake(function (HttpRequest $r) {
            [, $lng, $lat] = array_map('floatval', explode(',', str_replace('circle:', ',', $r['filter'])));
            $tag = $lat > 20.0 ? 'North' : 'South';

            return Http::response(['type' => 'FeatureCollection', 'features' => [
                $this->feature("$tag College", ['education.college'], $lat + 0.001, $lng),
                $this->feature("$tag Mall", ['commercial.shopping_mall'], $lat, $lng + 0.001),
                $this->feature("$tag Hospital", ['healthcare.hospital'], $lat - 0.001, $lng),
            ]]);
        });
        $north = $this->media(['latitude' => 20.0061, 'longitude' => 73.7379]);
        $south = $this->media(['latitude' => 19.9790, 'longitude' => 73.7379]);

        $htmlNorth = $this->getJson($this->url("/media-details/{$this->enc($north)}/insights"))->json('html');
        $htmlSouth = $this->getJson($this->url("/media-details/{$this->enc($south)}/insights"))->json('html');

        $this->assertStringContainsString('North College', $htmlNorth);
        $this->assertStringNotContainsString('South', $htmlNorth);
        $this->assertStringContainsString('South College', $htmlSouth);
        $this->assertStringNotContainsString('North', $htmlSouth);

        Http::assertSent(fn(HttpRequest $r) => $r['filter'] === 'circle:73.738,20.006,1000');
        Http::assertSent(fn(HttpRequest $r) => $r['filter'] === 'circle:73.738,19.979,1000');
        $this->assertSame([$north, $south], MediaInsight::orderBy('media_id')->pluck('media_id')->all());
    }

    public function test_geoapify_request_uses_documented_parameters(): void
    {
        $this->fakeGeoapify();
        $id = $this->media();

        $this->assertSame('success', app(MediaInsightsService::class)->refresh($id)['status']);

        Http::assertSent(function (HttpRequest $r) {
            return str_starts_with($r->url(), 'https://api.geoapify.com/v2/places')
                && $r['filter'] === 'circle:73.738,20.006,1000'           // longitude first, rounded
                && $r['bias'] === 'proximity:73.738,20.006'
                && $r['limit'] == 60
                && str_contains($r['categories'], 'education.school')
                && str_contains($r['categories'], 'healthcare.hospital')
                && $r['apiKey'] === self::GEO_KEY;
        });
    }

    public function test_successful_fetch_stores_places_per_hoarding_with_distances(): void
    {
        $this->fakeGeoapify();
        $id = $this->media();

        $out = app(MediaInsightsService::class)->refresh($id);

        $this->assertSame('success', $out['status']);
        $log = ApiUsageLog::where('status', 'success')->first();
        $this->assertSame('geoapify', $log->provider);
        $this->assertSame(1, (int) $log->credits_consumed);                // 6 features → 1 credit
        $this->assertSame(1, PlaceResult::count());

        $places = MediaInsight::where('media_id', $id)->first()->nearby_places;
        // Duplicate dropped; the ~1.5 km feature is outside this hoarding's 1 km.
        $this->assertCount(4, $places);
        $this->assertSame('KTHM College', $places[0]['title']);            // nearest first
        $this->assertSame('College', $places[0]['category']);
        $this->assertArrayHasKey('distance_m', $places[0]);
        $this->assertLessThanOrEqual(1000, max(array_column($places, 'distance_m')));
        // An unnamed park is kept, with no invented name.
        $park = collect($places)->firstWhere('category', 'Park');
        $this->assertNotNull($park);
        $this->assertArrayNotHasKey('title', $park);
        $this->assertStringNotContainsString(self::GEO_KEY, json_encode(PlaceResult::first()->toArray()));

        $view = app(MediaInsightsService::class)->forDisplay($id);
        $this->assertNotNull($view['points']['premium']['value']);
        $this->assertSame('inferred', $view['points']['audience']['status']);
        $this->assertNotEmpty($view['points']['recommendation']['value']);
        $this->assertSame('Powered by Geoapify', $view['attribution']['text']);
    }

    public function test_credits_follow_the_20_places_per_credit_rule(): void
    {
        $features = [];
        for ($i = 0; $i < 45; $i++) {
            $features[] = $this->feature("Place $i", ['office.company'], self::LAT + $i * 0.00005, self::LNG);
        }
        Http::fake(['api.geoapify.com/*' => Http::response(['type' => 'FeatureCollection', 'features' => $features])]);

        app(MediaInsightsService::class)->refresh($this->media());

        $this->assertSame(3, (int) ApiUsageLog::first()->credits_consumed);   // ceil(45 / 20)
    }

    public function test_no_places_found_is_shown_clearly(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response(['type' => 'FeatureCollection', 'features' => []])]);
        $id = $this->media();

        app(MediaInsightsService::class)->refresh($id);
        $html = $this->getJson($this->url("/media-details/{$this->enc($id)}/insights"))->json('html');

        $this->assertStringContainsString('No nearby places found within 1000 m', $html);
        $this->assertSame(1, (int) ApiUsageLog::first()->credits_consumed);
    }

    public function test_invalid_key_fails_cleanly_and_costs_nothing(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response(['statusCode' => 401, 'error' => 'Unauthorized', 'message' => 'Invalid apiKey'], 401)]);
        $id = $this->media();

        $out = app(MediaInsightsService::class)->refresh($id);

        $this->assertSame('failed', $out['status']);
        $this->assertStringContainsString('rejected the API key', $out['message']);
        $log = ApiUsageLog::first();
        $this->assertSame(401, (int) $log->http_status);
        $this->assertSame(0, (int) $log->credits_consumed);
        $this->assertSame(1, (int) MediaInsight::where('media_id', $id)->value('failed_attempts'));
        $this->assertNull(MediaInsight::where('media_id', $id)->first()->nearby_places);   // → "Not Available"
    }

    public function test_provider_429_pauses_further_calls(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response(['message' => 'Too Many Requests'], 429)]);
        $svc = app(MediaInsightsService::class);

        $first = $svc->refresh($this->media());
        $second = $svc->refresh($this->media(['latitude' => 19.99, 'longitude' => 73.77]));

        $this->assertSame('quota_exceeded', $first['status']);
        $this->assertSame('quota_blocked', $second['status']);
        Http::assertSentCount(1);
    }

    public function test_timeout_is_handled_and_key_is_scrubbed(): void
    {
        Http::fake(['api.geoapify.com/*' => fn() => throw new ConnectionException(
            'cURL error 28: timed out for https://api.geoapify.com/v2/places?apiKey=' . self::GEO_KEY)]);

        $out = app(MediaInsightsService::class)->refresh($this->media());

        $this->assertSame('failed', $out['status']);
        $this->assertStringNotContainsString(self::GEO_KEY, $out['message']);
        $this->assertStringNotContainsString(self::GEO_KEY, (string) ApiUsageLog::first()->error_message);
    }

    public function test_api_failure_keeps_showing_saved_places(): void
    {
        // First call succeeds, the next one fails.
        Http::fake(['api.geoapify.com/*' => Http::sequence()
            ->push($this->geoapifyBody())
            ->push([], 500)]);
        $id = $this->media();
        $svc = app(MediaInsightsService::class);
        $this->assertSame('success', $svc->refresh($id)['status']);

        // The saved result expires, so the next refresh calls the (now failing) API.
        PlaceResult::query()->update(['expires_at' => now()->subDay()]);

        $out = $svc->refresh($id);

        $this->assertSame('failed', $out['status']);
        $this->assertStringContainsString('Showing previously saved data', $out['message']);
        $this->assertCount(4, $svc->forDisplay($id)['places']);
    }

    public function test_hoardings_at_the_same_spot_share_one_call(): void
    {
        $this->fakeGeoapify();
        $a = $this->media();
        $b = $this->media(['latitude' => 20.0063, 'longitude' => 73.7382]);   // same ~110 m cell

        $svc = app(MediaInsightsService::class);
        $this->assertSame('success', $svc->refresh($a)['status']);
        $this->assertSame('cache_hit', $svc->refresh($b)['status']);

        Http::assertSentCount(1);
        // Each hoarding still has its own distances.
        $da = MediaInsight::where('media_id', $a)->first()->nearby_places[0]['distance_m'];
        $db = MediaInsight::where('media_id', $b)->first()->nearby_places[0]['distance_m'];
        $this->assertNotSame($da, $db);
    }

    public function test_daily_budget_blocks_before_spending(): void
    {
        $this->fakeGeoapify();
        $this->spend('geoapify', 2498, now());                 // 2 left, a request may cost 3

        $out = app(MediaInsightsService::class)->refresh($this->media());

        $this->assertSame('quota_blocked', $out['status']);
        Http::assertNothingSent();
    }

    public function test_yesterdays_credits_do_not_count_today(): void
    {
        $this->fakeGeoapify();
        $this->spend('geoapify', 2500, now()->subDay());

        $this->assertSame('success', app(MediaInsightsService::class)->refresh($this->media())['status']);
    }

    public function test_missing_coordinates_skip_the_api(): void
    {
        Http::fake();
        $id = $this->media(['latitude' => null, 'longitude' => null, 'address' => null]);

        $out = app(MediaInsightsService::class)->refresh($id);

        Http::assertNothingSent();
        $this->assertSame('missing_location', $out['status']);
        $this->assertFalse(app(MediaInsightsService::class)->forDisplay($id)['has_location']);
        $this->assertNotContains($id, app(MediaInsightsService::class)->dueForRefresh(100));
    }

    public function test_unconfigured_key_does_not_call_the_api(): void
    {
        config(['services.geoapify.key' => '']);
        Http::fake();

        $out = app(MediaInsightsService::class)->refresh($this->media());

        $this->assertSame('not_configured', $out['status']);
        Http::assertNothingSent();
    }

    /* ============================ REFRESH RULES ============================ */

    public function test_moved_or_expired_hoardings_become_due_again(): void
    {
        $this->fakeGeoapify();
        $svc = app(MediaInsightsService::class);
        $moved = $this->media();
        $expired = $this->media(['latitude' => 19.99, 'longitude' => 73.77]);
        $fresh = $this->media(['latitude' => 19.95, 'longitude' => 73.80]);
        foreach ([$moved, $expired, $fresh] as $id) {
            $svc->refresh($id);
        }
        $this->assertSame([], $svc->dueForRefresh(100));

        DB::table('media_management')->where('id', $moved)->update(['latitude' => 20.0200]);
        MediaInsight::where('media_id', $expired)->update(['places_expires_at' => now()->subMinute()]);

        $this->assertEqualsCanonicalizing([$moved, $expired], $svc->dueForRefresh(100));

        // The moved hoarding gets a new lookup for its new location.
        $this->assertSame('success', $svc->refresh($moved)['status']);
        $this->assertSame(2, PlaceResult::count() - 2);
    }

    public function test_repeatedly_failing_hoarding_waits_a_day(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response([], 500)]);
        $svc = app(MediaInsightsService::class);
        $id = $this->media();

        for ($i = 0; $i < MediaInsightsService::MAX_FAILED_ATTEMPTS; $i++) {
            $svc->refresh($id);
        }

        $this->assertNotContains($id, $svc->dueForRefresh(100));

        MediaInsight::where('media_id', $id)->update(['last_attempt_at' => now()->subDays(2)]);
        $this->assertContains($id, $svc->dueForRefresh(100));
    }

    /* ============================ BATCH / QUEUE ============================ */

    public function test_enrich_command_queues_due_hoardings_within_budget(): void
    {
        Queue::fake();
        foreach (range(1, 5) as $i) {
            $this->media(['latitude' => 19.9 + $i / 100, 'longitude' => 73.7]);
        }
        // 9 credits left → at most 3 requests of up to 3 credits.
        $this->spend('geoapify', 2491, now());

        $this->artisan('insights:enrich')->assertSuccessful();

        Queue::assertPushed(EnrichMediaInsightsJob::class, 3);
        Queue::assertPushedOn('insights', EnrichMediaInsightsJob::class);
    }

    public function test_enrich_command_dry_run_queues_nothing(): void
    {
        Queue::fake();
        $this->media();

        $this->artisan('insights:enrich', ['--dry-run' => true])
            ->expectsOutputToContain('1 hoarding(s) due')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_job_retries_temporary_failures_but_not_budget_blocks(): void
    {
        $svc = app(MediaInsightsService::class);

        Http::fake(['api.geoapify.com/*' => Http::response([], 503)]);
        $threw = false;
        try {
            (new EnrichMediaInsightsJob($this->media()))->handle($svc);
        } catch (RuntimeException $e) {
            $threw = true;
        }
        $this->assertTrue($threw, 'A 503 should be retried by the queue.');

        $this->spend('geoapify', 2500, now());
        (new EnrichMediaInsightsJob($this->media(['latitude' => 19.99, 'longitude' => 73.77])))->handle($svc);
        $this->assertSame(1, ApiUsageLog::where('status', 'quota_blocked')->where('source', 'scheduler')->count());
    }

    /* ============================ ACCESS ============================ */

    public function test_guests_cannot_refresh(): void
    {
        Http::fake();
        $id = $this->media();

        $this->postJson($this->url("/media-details/{$this->enc($id)}/insights/refresh"))->assertStatus(403);

        Http::assertNothingSent();
        $this->assertSame(0, ApiUsageLog::count());
    }

    public function test_site_admin_refresh_response_has_no_key(): void
    {
        $this->fakeGeoapify();
        $id = $this->media();

        $res = $this->withSession(['site_admin_id' => 1])
            ->postJson($this->url("/media-details/{$this->enc($id)}/insights/refresh"));

        $res->assertOk()->assertJson(['ok' => true, 'status' => 'success']);
        $this->assertStringNotContainsString(self::GEO_KEY, $res->getContent());

        $html = $this->withSession(['site_admin_id' => 1])
            ->getJson($this->url("/media-details/{$this->enc($id)}/insights"))->json('html');
        $this->assertStringContainsString('Refresh Insights', $html);
        $this->assertStringContainsString('Powered by Geoapify', $html);
        $this->assertStringContainsString('KTHM College', $html);
        $this->assertStringNotContainsString(self::GEO_KEY, $html);
    }

    public function test_panel_admin_can_refresh(): void
    {
        $this->fakeGeoapify();

        $this->withSession(['user_id' => 1])
            ->postJson($this->url("/media-details/{$this->enc($this->media())}/insights/refresh"))
            ->assertOk()
            ->assertJsonPath('status', 'success');
    }

    /* ============================ SERPAPI PROVIDER ============================ */

    public function test_serpapi_provider_still_works_with_its_monthly_budget(): void
    {
        config(['services.insights.places_provider' => 'serpapi']);
        Http::fake([
            'serpapi.com/account.json*' => Http::response(['total_searches_left' => 200, 'api_key' => self::SERP_KEY]),
            'serpapi.com/search.json*'  => Http::response([
                'search_metadata' => ['id' => 'abc', 'status' => 'Success'],
                'local_results'   => [
                    ['title' => 'City Centre Mall', 'place_id' => 'p1', 'type' => 'Shopping mall', 'rating' => 4.4, 'reviews' => 12000,
                        'gps_coordinates' => ['latitude' => 20.0070, 'longitude' => 73.7390]],
                    ['title' => 'KTHM College', 'place_id' => 'p2', 'type' => 'College', 'rating' => 4.1, 'reviews' => 900,
                        'gps_coordinates' => ['latitude' => 20.0050, 'longitude' => 73.7370]],
                    ['title' => 'Hotel Express Inn', 'place_id' => 'p3', 'type' => 'Hotel', 'rating' => 4.3, 'reviews' => 6000,
                        'gps_coordinates' => ['latitude' => 20.0080, 'longitude' => 73.7400]],
                ],
            ]),
        ]);
        $svc = app(MediaInsightsService::class);

        $this->assertSame('success', $svc->refresh($id = $this->media())['status']);
        $this->assertSame('serpapi', ApiUsageLog::where('status', 'success')->value('provider'));
        $this->assertSame('estimated', $svc->forDisplay($id)['points']['premium']['status']);

        $this->spend('serpapi', 250, now());
        $this->assertSame('quota_blocked', $svc->refresh($this->media(['latitude' => 19.99, 'longitude' => 73.77]))['status']);
    }

    /* ============================ HELPERS ============================ */

    /** Absolute URL on a bare host: APP_URL has a sub-folder the test client would prepend. */
    private function url(string $path): string
    {
        return 'http://localhost' . $path;
    }

    private function enc(int $id): string
    {
        return base64_encode((string) $id);
    }

    private function spend(string $provider, int $credits, $at): void
    {
        ApiUsageLog::create([
            'provider' => $provider, 'status' => 'success', 'credits_consumed' => $credits,
            'source' => 'scheduler', 'requested_at' => $at,
        ]);
    }

    private function feature(?string $name, array $categories, float $lat, float $lng, ?string $id = null): array
    {
        return [
            'type'       => 'Feature',
            'properties' => array_filter([
                'name'          => $name,
                'categories'    => $categories,
                'formatted'     => ($name ?? 'Unnamed') . ', College Road, Nashik, Maharashtra, India',
                'address_line1' => $name,
                'address_line2' => 'College Road, Nashik',
                'lat'           => $lat,
                'lon'           => $lng,
                'place_id'      => $id ?? md5(($name ?? '') . $lat . $lng),
            ], fn($v) => $v !== null),
            'geometry' => ['type' => 'Point', 'coordinates' => [$lng, $lat]],
        ];
    }

    private function fakeGeoapify(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response($this->geoapifyBody())]);
    }

    /** A Places API FeatureCollection shaped like the documented response. */
    private function geoapifyBody(): array
    {
        return [
            'type'     => 'FeatureCollection',
            'features' => [
                $this->feature('City Centre Mall', ['commercial', 'commercial.shopping_mall'], 20.0080, 73.7400, 'p1'),
                $this->feature('KTHM College', ['education', 'education.college'], 20.0063, 73.7381, 'p2'),
                $this->feature('Apollo Hospital', ['healthcare', 'healthcare.hospital'], 20.0040, 73.7360, 'p3'),
                $this->feature(null, ['leisure', 'leisure.park'], 20.0070, 73.7370, 'p4'),
                $this->feature('Far Temple', ['tourism', 'tourism.sights'], 20.0200, 73.7379, 'p5'),     // ~1.5 km
                $this->feature('KTHM College', ['education', 'education.college'], 20.0063, 73.7381, 'p2'), // duplicate
            ],
        ];
    }

    private function media(array $overrides = []): int
    {
        return DB::table('media_management')->insertGetId(array_merge([
            'category_id' => 1, 'city_id' => 1, 'width' => 20, 'height' => 10, 'area_auto' => null,
            'facing' => 'West Side', 'price' => 30000, 'latitude' => self::LAT, 'longitude' => self::LNG,
            'address' => 'Nashik', 'illumination_id' => 2, 'areatype_id' => 2, 'highway_id' => null,
            'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now(),
        ], $overrides));
    }

    private function createSchema(): void
    {
        Schema::create('media_management', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('category_id')->nullable();
            $t->unsignedBigInteger('city_id')->nullable();
            $t->decimal('width', 8, 2)->nullable();
            $t->decimal('height', 8, 2)->nullable();
            $t->string('area_auto')->nullable();
            $t->text('facing')->nullable();
            $t->float('price')->nullable();
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->text('address')->nullable();
            $t->unsignedBigInteger('illumination_id')->nullable();
            $t->unsignedBigInteger('areatype_id')->nullable();
            $t->unsignedBigInteger('highway_id')->nullable();
            $t->tinyInteger('is_active')->default(1);
            $t->tinyInteger('is_deleted')->default(0);
            $t->timestamps();
        });
        Schema::create('category', fn(Blueprint $t) => [$t->id(), $t->string('category_name')]);
        Schema::create('illuminations', fn(Blueprint $t) => [$t->id(), $t->string('illumination_name')]);
        Schema::create('areatype', fn(Blueprint $t) => [$t->id(), $t->string('areatype_name')]);
        Schema::create('landmark', fn(Blueprint $t) => [$t->id(), $t->string('landmark_name'), $t->tinyInteger('is_deleted')->default(0)]);
        Schema::create('media_landmark', fn(Blueprint $t) => [$t->id(), $t->unsignedBigInteger('media_id'), $t->unsignedBigInteger('landmark_id')]);
        // Read by the site's global view composers (portal access); unrelated to insights.
        Schema::create('settings', fn(Blueprint $t) => [$t->id(), $t->string('key')->unique(), $t->text('value')->nullable(), $t->string('group')->nullable(), $t->timestamps()]);

        (require database_path('migrations/2026_09_26_100000_create_media_insights_tables.php'))->up();
    }

    private function seedMasters(): void
    {
        DB::table('category')->insert(['id' => 1, 'category_name' => 'Hoardings/Billboards']);
        DB::table('illuminations')->insert([['id' => 1, 'illumination_name' => 'Non-Lit'], ['id' => 2, 'illumination_name' => 'Front Lit']]);
        DB::table('areatype')->insert([['id' => 1, 'areatype_name' => 'Rural'], ['id' => 2, 'areatype_name' => 'Urbun'], ['id' => 3, 'areatype_name' => 'Highway']]);
    }
}

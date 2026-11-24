<?php

namespace App\Http\Services\Insights;

use App\Models\ApiUsageLog;
use App\Models\MediaInsight;
use App\Models\PlaceResult;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Media Location Insights — nearby places + the PRD's nine points.
 *
 * Cost control (10,000 → 10,00,000+ hoardings):
 *  - Viewing a page NEVER calls an API. forDisplay() reads MySQL only.
 *  - Only refresh() spends credits: an admin's "Refresh Insights", or the
 *    queued EnrichMediaInsightsJob fed by the scheduled insights:enrich batch.
 *  - Results are saved per provider + rounded LOCATION (≈110 m at 3 dp) and
 *    reused for the provider's cache days, so neighbouring hoardings share
 *    one call. Each hoarding still gets its own distances, measured from its
 *    exact coordinates and cut to the true radius.
 *  - Budget: SUM(credits_consumed) in api_usage_logs for the provider's period
 *    (Geoapify: calendar day, SerpApi: calendar month). A request is only sent
 *    if its worst-case cost still fits. The check and the call run under one
 *    cache lock so parallel workers/admins cannot overshoot.
 *  - After a provider answers 429 the provider is paused for PAUSE_MINUTES.
 *  - A hoarding whose coordinates change is due again immediately.
 */
class MediaInsightsService
{
    private const PAUSE_MINUTES = 15;
    /* After this many failed attempts a hoarding waits a day before the batch retries it. */
    public const MAX_FAILED_ATTEMPTS = 3;

    public function __construct(
        private MediaScoringService $scoring,
        private HoardingTrafficService $traffic,
    ) {}

    /* ============================ PROVIDER ============================ */

    public function provider(): PlacesProvider
    {
        return match (config('services.insights.places_provider', 'geoapify')) {
            'serpapi' => app(SerpApiService::class),
            'tomtom'  => app(TomTomPlacesService::class),
            default   => app(GeoapifyPlacesService::class),
        };
    }

    /* ============================ BUDGET ============================ */

    public function periodStart(PlacesProvider $p): Carbon
    {
        return $p->budget()['period'] === 'day' ? now()->startOfDay() : now()->startOfMonth();
    }

    public function usedThisPeriod(?PlacesProvider $p = null): int
    {
        $p ??= $this->provider();

        return (int) ApiUsageLog::where('provider', $p->name())
            ->where('requested_at', '>=', $this->periodStart($p))
            ->sum('credits_consumed');
    }

    public function quota(?PlacesProvider $p = null): array
    {
        $p ??= $this->provider();
        $budget = $p->budget();
        $used = $this->usedThisPeriod($p);

        return [
            'provider'   => $p->name(),
            'period'     => $budget['period'],
            'used'       => $used,
            'limit'      => $budget['limit'],
            'remaining'  => max(0, $budget['limit'] - $used),
            'max_cost'   => $budget['max_cost'],
            'configured' => $p->isConfigured(),
            'paused'     => $this->isPaused($p),
        ];
    }

    public function canAffordRequest(?PlacesProvider $p = null): bool
    {
        $q = $this->quota($p);

        return $q['configured'] && !$q['paused'] && $q['remaining'] >= $q['max_cost'];
    }

    private function pauseKey(PlacesProvider $p): string
    {
        return 'insights:' . $p->name() . ':paused';
    }

    public function isPaused(PlacesProvider $p): bool
    {
        return (bool) Cache::get($this->pauseKey($p), false);
    }

    /* ============================ READ (no API) ============================ */

    /**
     * Everything the detail page shows. Reads MySQL only — never an API.
     * Returns null if the media does not exist.
     */
    public function forDisplay(int $mediaId): ?array
    {
        $media = $this->loadMedia($mediaId);
        if (!$media) {
            return null;
        }

        $provider = $this->provider();
        $insight = MediaInsight::where('media_id', $mediaId)->first();
        $location = $this->savedLocation($provider, $media, allowStale: true);

        // Recalculate (DB only) when there is no snapshot yet, the media was
        // edited since, or a newer/other saved location result now applies.
        $stale = !$insight
            || ($media['updated_at'] && $insight->calculated_at && $insight->calculated_at->lt($media['updated_at']))
            || ($location && (int) $insight->place_result_id !== (int) $location->id)
            || (!$location && $insight->place_result_id);

        if ($stale) {
            $insight = $this->calculateAndStore($media, $location, $provider);
        }

        return $this->present($media, $insight, $insight->placeResult, $provider);
    }

    /* ============================ REFRESH (may call API) ============================ */

    /**
     * Uses the saved location result when it is still fresh; otherwise spends
     * at most one request, and only if the budget allows.
     *
     * @param string $source admin | scheduler
     * @return array{status: string, message: string}
     */
    public function refresh(int $mediaId, ?int $adminId = null, string $source = 'admin'): array
    {
        $media = $this->loadMedia($mediaId);
        if (!$media) {
            return ['status' => 'not_found', 'message' => 'Media not found.'];
        }

        $provider = $this->provider();

        if (!$this->hasCoordinates($media)) {
            $this->log($provider, $media, null, ApiUsageLog::MISSING_LOCATION, $adminId, $source);
            $this->calculateAndStore($media, null, $provider);

            return ['status' => ApiUsageLog::MISSING_LOCATION,
                'message' => 'This media has no valid latitude/longitude, so nearby places cannot be looked up.'];
        }

        $key = $this->locationKey($provider, $media);

        // 1. Fresh saved result for this location → no API call.
        if ($fresh = $this->savedLocation($provider, $media)) {
            $this->log($provider, $media, $key, ApiUsageLog::CACHE_HIT, $adminId, $source);
            $this->calculateAndStore($media, $fresh, $provider);

            return ['status' => ApiUsageLog::CACHE_HIT,
                'message' => 'Used saved nearby places from ' . $fresh->fetched_at?->format('d M Y') . ' (no API credits used).'];
        }

        if (!$provider->isConfigured()) {
            $this->log($provider, $media, $key, ApiUsageLog::NOT_CONFIGURED, $adminId, $source);
            $this->calculateAndStore($media, $this->savedLocation($provider, $media, allowStale: true), $provider);

            return ['status' => ApiUsageLog::NOT_CONFIGURED,
                'message' => ucfirst($provider->name()) . ' API key is not configured on the server.'];
        }

        try {
            return Cache::lock('insights:' . $provider->name() . ':budget-lock', 60)
                ->block(15, fn() => $this->fetchUnderLock($provider, $media, $key, $adminId, $source));
        } catch (LockTimeoutException $e) {
            return ['status' => 'busy', 'message' => 'Another refresh is in progress. Please try again in a moment.'];
        }
    }

    /** Runs while holding the provider's budget lock. */
    private function fetchUnderLock(PlacesProvider $provider, array $media, string $key, ?int $adminId, string $source): array
    {
        // Another worker may have fetched this location while we waited.
        if ($fresh = $this->savedLocation($provider, $media)) {
            $this->log($provider, $media, $key, ApiUsageLog::CACHE_HIT, $adminId, $source);
            $this->calculateAndStore($media, $fresh, $provider);

            return ['status' => ApiUsageLog::CACHE_HIT, 'message' => 'Nearby places were just refreshed (no API credits used).'];
        }

        $stale = $this->savedLocation($provider, $media, allowStale: true);
        $quota = $this->quota($provider);

        if ($quota['paused']) {
            return $this->blocked($provider, $media, $key, $adminId, $source, $stale,
                ucfirst($provider->name()) . ' recently refused a request for exceeding its limit; paused for a few minutes. Showing saved data.');
        }

        if ($quota['remaining'] < $quota['max_cost']) {
            $period = $quota['period'] === 'day' ? 'daily' : 'monthly';

            return $this->blocked($provider, $media, $key, $adminId, $source, $stale,
                "The {$period} " . ucfirst($provider->name()) . " limit of {$quota['limit']} credits has been reached. Showing saved data.");
        }

        if ($provider->remoteQuotaExhausted()) {
            return $this->blocked($provider, $media, $key, $adminId, $source, $stale,
                ucfirst($provider->name()) . ' reports no credits left on the plan. Showing saved data.');
        }

        [$lat, $lng] = $this->roundedCoordinates($provider, $media);
        $res = $provider->fetch($lat, $lng);

        $status = $res['ok'] ? ApiUsageLog::SUCCESS
            : ($res['quota_exceeded'] ? ApiUsageLog::QUOTA_EXCEEDED : ApiUsageLog::FAILED);
        $this->log($provider, $media, $key, $status, $adminId, $source, $res);

        if (!$res['ok']) {
            if ($res['quota_exceeded']) {
                Cache::put($this->pauseKey($provider), true, now()->addMinutes(self::PAUSE_MINUTES));
            }
            $this->calculateAndStore($media, $stale, $provider);   // ensures the row exists
            $this->recordFailedAttempt($media['id']);

            return ['status' => $status,
                'message' => 'Could not fetch nearby places: ' . $res['error'] . ($stale ? ' Showing previously saved data.' : '')];
        }

        $location = PlaceResult::updateOrCreate(
            ['location_key' => $key],
            [
                'provider'    => $provider->name(),
                'query'       => mb_substr($provider->signature(), 0, 500),
                'latitude'    => $lat,
                'longitude'   => $lng,
                'radius_m'    => $provider->radiusMeters(),
                'places'      => $res['places'],
                'place_count' => count($res['places']),
                'external_id' => $res['external_id'],
                'fetched_at'  => now(),
                'expires_at'  => now()->addDays($provider->cacheDays()),
            ]
        );

        if ($provider instanceof SerpApiService) {
            Cache::forget('serpapi:account-usage');
        }

        $insight = $this->calculateAndStore($media, $location, $provider);
        $found = count($insight->nearby_places ?? []);

        return ['status' => ApiUsageLog::SUCCESS,
            'message' => "Nearby places updated: {$found} found" . ($provider->radiusMeters() ? ' within ' . $provider->radiusMeters() . ' m' : '')
                . " ({$res['credits']} credit" . ($res['credits'] === 1 ? '' : 's') . ' used).'];
    }

    private function blocked(PlacesProvider $provider, array $media, string $key, ?int $adminId, string $source, ?PlaceResult $stale, string $message): array
    {
        $this->log($provider, $media, $key, ApiUsageLog::QUOTA_BLOCKED, $adminId, $source, ['error' => $message]);
        $this->calculateAndStore($media, $stale, $provider);

        return ['status' => ApiUsageLog::QUOTA_BLOCKED, 'message' => $message];
    }

    private function recordFailedAttempt(int $mediaId): void
    {
        MediaInsight::where('media_id', $mediaId)
            ->where('failed_attempts', '<', 255)
            ->increment('failed_attempts', 1, ['last_attempt_at' => now()]);
    }

    /* ============================ BATCH SELECTION ============================ */

    /**
     * Hoardings due a places refresh, most in need first: never fetched, then
     * moved (coordinates changed), provider switched or expired — oldest
     * first. Hoardings that failed MAX_FAILED_ATTEMPTS times wait a day.
     * Uses the indexed media_insights.places_expires_at / media_id columns, so
     * it stays cheap at 10 lakh+ rows.
     *
     * @return int[] media ids
     */
    public function dueForRefresh(int $limit): array
    {
        return $this->dueQuery()
            ->orderByRaw('CASE WHEN mi.places_fetched_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('mi.places_fetched_at')
            ->orderBy('m.id')
            ->limit($limit)
            ->pluck('m.id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    /** Is this one hoarding due a places fetch (never fetched, moved, expired)? */
    public function isDue(int $mediaId): bool
    {
        return $this->dueQuery()->where('m.id', $mediaId)->exists();
    }

    /** How many hoardings are due (for the admin page) — a COUNT, no ids loaded. */
    public function dueCount(): int
    {
        return $this->dueQuery()->count('m.id');
    }

    private function dueQuery()
    {
        $provider = $this->provider()->name();
        $now = now();

        return DB::table('media_management as m')
            ->leftJoin('media_insights as mi', 'mi.media_id', '=', 'm.id')
            ->where('m.is_deleted', 0)
            ->where('m.is_active', 1)
            ->whereNotNull('m.latitude')->whereNotNull('m.longitude')
            ->where('m.latitude', '!=', 0)->where('m.longitude', '!=', 0)
            ->where(function ($q) use ($provider, $now) {
                $q->whereNull('mi.id')
                    ->orWhereNull('mi.places_fetched_at')
                    ->orWhere('mi.places_provider', '!=', $provider)
                    ->orWhere('mi.places_expires_at', '<', $now)
                    ->orWhereRaw('ABS(mi.places_latitude - m.latitude) > 0.0000005')
                    ->orWhereRaw('ABS(mi.places_longitude - m.longitude) > 0.0000005');
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('mi.id')
                    ->orWhere('mi.failed_attempts', '<', self::MAX_FAILED_ATTEMPTS)
                    ->orWhere('mi.last_attempt_at', '<', $now->copy()->subDay());
            });
    }

    /* ============================ CALCULATION ============================ */

    public function calculateAndStore(array $media, ?PlaceResult $location, ?PlacesProvider $provider = null): MediaInsight
    {
        $provider ??= $this->provider();
        $places = $this->placesForMedia($media, $location);

        $cap = match ($location?->provider) {
            'geoapify' => max(1, (int) config('services.geoapify.limit', 60)),
            'tomtom'   => max(1, (int) config('services.tomtom.limit', 60)),
            default    => 20,
        };

        $visibility = $this->scoring->visibility($media);
        $premium    = $location ? $this->scoring->premiumLocation($places, $cap)
            : ['status' => 'unavailable', 'missing' => ['nearby places'], 'reason' => 'Nearby places have not been fetched for this location yet.'];
        $audience   = $this->scoring->audience($places, $media);
        $recommend  = $this->scoring->recommendations($audience, $visibility, $premium, $media);
        $value      = $this->scoring->value($media, $visibility, $premium, $this->peerPrices($media));

        $fields = [
            'place_result_id'   => $location?->id,
            'visibility_score'  => $visibility['score'] ?? null,
            'visibility_status' => $visibility['status'],
            'premium_score'     => $premium['score'] ?? null,
            'premium_status'    => $premium['status'],
            'value_score'       => $value['score'] ?? null,
            'value_status'      => $value['status'],
            'audience'          => $audience,
            'recommendations'   => $recommend,
            'nearby_places'     => $location ? $places : null,
            'details'           => compact('visibility', 'premium', 'value'),
            'calculated_at'     => now(),
        ];

        // Refresh bookkeeping follows the saved result actually applied.
        if ($location) {
            $fields += [
                'places_provider'   => $location->provider,
                'places_latitude'   => $media['latitude'],
                'places_longitude'  => $media['longitude'],
                'places_fetched_at' => $location->fetched_at,
                'places_expires_at' => $location->expires_at,
            ];
            if ($location->isFresh()) {
                $fields['failed_attempts'] = 0;
            }
        }

        return MediaInsight::updateOrCreate(['media_id' => $media['id']], $fields)->load('placeResult');
    }

    /**
     * The saved places as seen from THIS hoarding: distance from its exact
     * coordinates, anything outside the provider radius dropped (the shared
     * search is centred on the rounded point), nearest first.
     */
    private function placesForMedia(array $media, ?PlaceResult $location): array
    {
        $places = $location?->places ?? [];
        if (!$places || !$this->hasCoordinates($media)) {
            return $places;
        }

        $radius = $location->radius_m;
        $out = [];

        foreach ($places as $p) {
            if (isset($p['lat'], $p['lng'])) {
                $p['distance_m'] = (int) round(1000 * $this->scoring->distanceKm(
                    (float) $media['latitude'], (float) $media['longitude'], $p['lat'], $p['lng']
                ));
                if ($radius && $p['distance_m'] > $radius) {
                    continue;
                }
            }
            $out[] = $p;
        }

        usort($out, fn($a, $b) => ($a['distance_m'] ?? PHP_INT_MAX) <=> ($b['distance_m'] ?? PHP_INT_MAX));

        return $out;
    }

    /* ============================ SEARCH: NEARBY LANDMARKS ============================ */

    /**
     * Every nearby place already saved for the active hoardings of one town —
     * the "Nearby Landmark" options on the search form. Reads place_results
     * only; never calls the provider. Cached for an hour per town.
     *
     * @return array<int, array{value: string, name: string, category: ?string}>
     */
    public function nearbyPlacesForCity(int $cityId): array
    {
        return Cache::remember('nearby-places:city:' . $cityId, now()->addHour(), function () use ($cityId) {
            $results = DB::table('media_insights as mi')
                ->join('media_management as m', 'm.id', '=', 'mi.media_id')
                ->join('place_results as pr', 'pr.id', '=', 'mi.place_result_id')
                ->where('m.city_id', $cityId)
                ->where('m.is_deleted', 0)
                ->where('m.is_active', 1)
                ->distinct()
                ->pluck('pr.places');

            $out = [];
            foreach ($results as $json) {
                foreach ((array) json_decode((string) $json, true) as $p) {
                    $name = trim((string) ($p['title'] ?? ''));
                    if ($name === '' || !isset($p['lat'], $p['lng'])) {
                        continue;
                    }
                    // Same place seen from several hoardings: keep it once.
                    $key = mb_strtolower($name) . '|' . round((float) $p['lat'], 4) . '|' . round((float) $p['lng'], 4);
                    $out[$key] ??= [
                        'value'    => round((float) $p['lat'], 6) . ',' . round((float) $p['lng'], 6),
                        'name'     => $name,
                        'category' => $p['category'] ?? null,
                    ];
                }
            }

            usort($out, fn($a, $b) => strcasecmp($a['name'], $b['name']));

            return array_values($out);
        });
    }

    /* ============================ PRESENTATION ============================ */

    /**
     * The nine PRD points, each with a value, a status and a note.
     * Status: estimated | incomplete | inferred | rule_based | available | unavailable.
     */
    private function present(array $media, MediaInsight $insight, ?PlaceResult $location, PlacesProvider $provider): array
    {
        $d = $insight->details ?? [];
        $vis = $d['visibility'] ?? [];
        $pre = $d['premium'] ?? [];
        $val = $d['value'] ?? [];
        $places = $insight->nearby_places;   // null = never fetched, [] = none found
        $tagged = $this->taggedLandmarks($media['id']);
        $source = $location ? $this->sourceLabel($location->provider) : null;

        $notAvailable = fn(string $why) => ['value' => null, 'status' => 'unavailable', 'note' => $why];

        $points = [
            'traffic' => $this->trafficPoint((int) $media['id']),
            'footfall' => ['label' => 'Estimated Daily Footfall'] + $notAvailable(
                'No verified pedestrian-count source is connected.'),
            'visibility' => [
                'label'  => 'Visibility Score',
                'value'  => $insight->visibility_score !== null ? (float) $insight->visibility_score : null,
                'max'    => 10,
                'status' => $insight->visibility_status,
                'note'   => $this->scoreNote($vis, 'Based on size, illumination, road exposure (area type / highway) and facing.'),
            ],
            'premium' => [
                'label'  => 'Premium Location Rating',
                'value'  => $insight->premium_score !== null ? (float) $insight->premium_score : null,
                'max'    => 10,
                'status' => $insight->premium_status,
                'note'   => $this->scoreNote($pre, 'Based on how many places are nearby, how varied they are, and premium venues'
                    . (!empty($pre['inputs']['ratings_used']) ? ', plus their Google ratings and reviews.' : '.')),
            ],
            'audience' => [
                'label'  => 'Audience Type',
                'value'  => $insight->audience['types'] ?? [],
                'status' => $insight->audience['status'] ?? 'unavailable',
                'note'   => ($insight->audience['status'] ?? '') === 'inferred'
                    ? 'Inferred from the kinds of places nearby — not verified demographic data.'
                    : ($insight->audience['reason'] ?? 'No nearby-place data yet.'),
            ],
            'landmarks' => [
                'label'  => 'Nearby Landmarks',
                'value'  => array_values(array_filter($places ?? [], fn($p) => !empty($p['title']))),
                'tagged' => $tagged,
                'status' => (($places && array_filter($places, fn($p) => !empty($p['title']))) || $tagged) ? 'available' : 'unavailable',
                'note'   => $source ?? 'Nearby places not fetched yet.',
            ],
            'impressions' => ['label' => 'Estimated Monthly Impressions'] + $notAvailable(
                'Needs verified daily traffic, which is not available, so impressions are not calculated.'),
            'recommendation' => [
                'label'  => 'AI-Based Recommendations',
                'value'  => array_map(fn($r) => $r + ['label' => MediaScoringService::SECTOR_LABELS[$r['sector']] ?? $r['sector']],
                    $insight->recommendations['items'] ?? []),
                'status' => $insight->recommendations['status'] ?? 'unavailable',
                'note'   => ($insight->recommendations['status'] ?? '') === 'rule_based'
                    ? 'Suggested from nearby places and media details by documented rules — not a trained AI model.'
                    : ($insight->recommendations['reason'] ?? 'Not enough location data.'),
            ],
            'value' => [
                'label'  => 'ROI / Value Score',
                'value'  => $insight->value_score !== null ? (float) $insight->value_score : null,
                'max'    => 10,
                'status' => $insight->value_status,
                'note'   => $this->scoreNote($val, 'Location quality against price, compared with similar media in the same city. Not a measured ROI — that needs campaign results.'),
            ],
        ];

        return [
            'points'        => $points,
            'places'        => $places,
            'radius_m'      => $location?->radius_m,
            'calculated_at' => $insight->calculated_at,
            'fetched_at'    => $location?->fetched_at,
            'expired'       => $location && !$location->isFresh(),
            'source'        => $source,
            'attribution'   => $location ? $this->providerByName($location->provider)?->attribution() : null,
            'has_location'  => $this->hasCoordinates($media),
        ];
    }

    /* TomTom road classes (Functional Road Class), most important first. */
    private const ROAD_CLASSES = [
        'FRC0' => 'Motorway / freeway',
        'FRC1' => 'Major road',
        'FRC2' => 'Other major road',
        'FRC3' => 'Secondary road',
        'FRC4' => 'Local connecting road',
        'FRC5' => 'Local road (high importance)',
        'FRC6' => 'Local road',
    ];

    /**
     * Estimated Daily Traffic tile, from the stored TomTom Traffic Flow
     * (hoarding_traffic_data — MySQL only, refreshed by refresh:hoarding-traffic).
     * TomTom gives road speeds and road class, not vehicles per day, so the
     * value is a traffic LEVEL (current speed against free-flow speed) and
     * no vehicle count is shown.
     */
    private function trafficPoint(int $mediaId): array
    {
        $label = 'Estimated Daily Traffic';
        $flow = $this->traffic->forDisplay($mediaId);

        if (!$flow['traffic_data_available']) {
            return ['label' => $label, 'value' => null, 'status' => 'unavailable',
                'note' => 'Traffic flow for this location has not been fetched from TomTom yet.'];
        }

        $t = $flow['traffic'];
        $current = $t['current_speed'];
        $free = $t['free_flow_speed'];

        if ($t['road_closure']) {
            $level = 'Road closed';
        } elseif ($current === null || !$free) {
            $level = null;
        } else {
            $ratio = $current / $free;
            $level = match (true) {
                $ratio >= 0.85 => 'Free-flowing traffic',
                $ratio >= 0.65 => 'Moderate traffic',
                $ratio >= 0.40 => 'Heavy traffic',
                default        => 'Very heavy traffic',
            };
        }

        $fmt = fn($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
        $details = array_filter([
            'Road type' => self::ROAD_CLASSES[$t['frc']] ?? $t['frc'],
            'Speed now' => $current !== null ? $fmt($current) . ' km/h' : null,
            'Free-flow speed' => $free !== null ? $fmt($free) . ' km/h' : null,
        ], fn($v) => $v !== null);

        return [
            'label'   => $label,
            'value'   => $level,
            'details' => $details,
            'status'  => $level ? 'tomtom' : 'unavailable',
            'note'    => 'TomTom Traffic Flow on the nearest road, updated '
                . Carbon::parse($t['traffic_updated_at'])->format('d M Y')
                . '. Shows traffic conditions, not a vehicle count.',
        ];
    }

    private function sourceLabel(string $provider): string
    {
        return match ($provider) {
            'serpapi' => 'Google Maps (via SerpApi)',
            'tomtom'  => 'TomTom Search',
            default   => 'Geoapify Places (OpenStreetMap data)',
        };
    }

    private function providerByName(string $name): ?PlacesProvider
    {
        return match ($name) {
            'serpapi'  => app(SerpApiService::class),
            'geoapify' => app(GeoapifyPlacesService::class),
            'tomtom'   => app(TomTomPlacesService::class),
            default    => null,
        };
    }

    private function scoreNote(array $score, string $basis): string
    {
        if (($score['status'] ?? '') === 'unavailable') {
            return $score['reason'] ?? 'Required data is missing.';
        }
        if (!empty($score['missing'])) {
            return $basis . ' Missing: ' . implode(', ', $score['missing']) . '.';
        }

        return $basis;
    }

    /* ============================ DATA ACCESS ============================ */

    public function loadMedia(int $mediaId): ?array
    {
        $row = DB::table('media_management as m')
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
            ->leftJoin('illuminations as i', 'i.id', '=', 'm.illumination_id')
            ->leftJoin('areatype as at', 'at.id', '=', 'm.areatype_id')
            ->where('m.id', $mediaId)
            ->where('m.is_deleted', 0)
            ->select(
                'm.id', 'm.category_id', 'm.city_id', 'm.width', 'm.height', 'm.area_auto',
                'm.facing', 'm.price', 'm.latitude', 'm.longitude', 'm.address',
                'm.highway_id', 'm.updated_at',
                'c.category_name', 'i.illumination_name', 'at.areatype_name'
            )
            ->first();

        if (!$row) {
            return null;
        }

        $media = (array) $row;
        $media['updated_at'] = $media['updated_at'] ? Carbon::parse($media['updated_at']) : null;

        return $media;
    }

    private function taggedLandmarks(int $mediaId): array
    {
        try {
            return DB::table('media_landmark as ml')
                ->join('landmark as l', 'l.id', '=', 'ml.landmark_id')
                ->where('ml.media_id', $mediaId)
                ->where('l.is_deleted', 0)
                ->orderBy('l.landmark_name')
                ->pluck('l.landmark_name')
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }

    /** Monthly prices of other active media in the same category and city. */
    private function peerPrices(array $media): array
    {
        if (empty($media['category_id']) || empty($media['city_id'])) {
            return [];
        }

        return DB::table('media_management')
            ->where('category_id', $media['category_id'])
            ->where('city_id', $media['city_id'])
            ->where('id', '!=', $media['id'])
            ->where('is_deleted', 0)
            ->where('is_active', 1)
            ->where('price', '>', 0)
            ->pluck('price')
            ->map(fn($p) => (float) $p)
            ->all();
    }

    public function hasCoordinates(array $media): bool
    {
        $lat = $media['latitude'] ?? null;
        $lng = $media['longitude'] ?? null;

        return is_numeric($lat) && is_numeric($lng)
            && (float) $lat != 0.0 && (float) $lng != 0.0
            && abs((float) $lat) <= 90 && abs((float) $lng) <= 180;
    }

    private function roundedCoordinates(PlacesProvider $p, array $media): array
    {
        $dp = $p->coordPrecision();

        return [round((float) $media['latitude'], $dp), round((float) $media['longitude'], $dp)];
    }

    public function locationKey(PlacesProvider $p, array $media): string
    {
        [$lat, $lng] = $this->roundedCoordinates($p, $media);

        return sha1(implode('|', [$p->name(), $p->signature(), $lat, $lng]));
    }

    private function savedLocation(PlacesProvider $p, array $media, bool $allowStale = false): ?PlaceResult
    {
        if (!$this->hasCoordinates($media)) {
            return null;
        }

        $location = PlaceResult::where('location_key', $this->locationKey($p, $media))->first();

        if (!$location) {
            return null;
        }

        return ($allowStale || $location->isFresh()) ? $location : null;
    }

    private function log(PlacesProvider $p, array $media, ?string $key, string $status, ?int $adminId, string $source, array $res = []): void
    {
        try {
            ApiUsageLog::create([
                'provider'         => $p->name(),
                'media_id'         => $media['id'],
                'query'            => mb_substr($p->signature(), 0, 500),
                'location_key'     => $key,
                'status'           => $status,
                'http_status'      => $res['http_status'] ?? null,
                'credits_consumed' => (int) ($res['credits'] ?? 0),
                'result_count'     => isset($res['places']) ? count($res['places']) : null,
                'external_id'      => isset($res['external_id']) ? mb_substr((string) $res['external_id'], 0, 100) : null,
                'error_message'    => isset($res['error']) ? mb_substr($p->scrub((string) $res['error']), 0, 500) : null,
                'duration_ms'      => $res['duration_ms'] ?? null,
                'source'           => $source,
                'triggered_by'     => $adminId,
                'requested_at'     => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Could not write places API usage log: ' . $e->getMessage());
        }
    }
}

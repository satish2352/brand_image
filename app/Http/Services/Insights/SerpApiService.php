<?php

namespace App\Http\Services\Insights;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The only class that talks to SerpApi. One of the PlacesProvider
 * implementations (INSIGHTS_PLACES_PROVIDER=serpapi).
 *
 * Engine: google_maps (type=search) — https://serpapi.com/google-maps-api
 * Maps only documented local_results fields: title, place_id, type, types,
 * rating, reviews, price, address, gps_coordinates. A field missing from a
 * result is left out, never filled in.
 *
 * SerpApi returns nearby PLACES. It does not return vehicle traffic,
 * pedestrian footfall or advertising impressions, and nothing here claims it.
 *
 * The api_key is added to the outgoing request only. It is scrubbed from
 * every error message this class returns or logs, because a cURL/Guzzle
 * exception message contains the full request URL.
 */
class SerpApiService implements PlacesProvider
{
    public const ENGINE = 'google_maps';

    public function name(): string
    {
        return 'serpapi';
    }

    public function signature(): string
    {
        return self::ENGINE . '|' . mb_strtolower($this->query()) . '|' . $this->zoom();
    }

    public function coordPrecision(): int
    {
        return (int) config('services.serpapi.coord_precision', 3);
    }

    public function radiusMeters(): ?int
    {
        return null;   // Google Maps search is a map viewport, not a radius
    }

    public function cacheDays(): int
    {
        return max(1, (int) config('services.serpapi.cache_days', 90));
    }

    public function budget(): array
    {
        return [
            'period'   => 'month',
            'limit'    => max(0, (int) config('services.serpapi.monthly_limit', 250)),
            'max_cost' => 1,
        ];
    }

    /** PlacesProvider::fetch — one Google Maps search in the shared result shape. */
    public function fetch(float $lat, float $lng): array
    {
        $r = $this->searchNearbyPlaces($lat, $lng);

        return [
            'ok'             => $r['ok'],
            'http_status'    => $r['http_status'],
            'places'         => $r['places'],
            'external_id'    => $r['search_id'],
            'error'          => $r['error'],
            'credits'        => $r['consumed'],
            'duration_ms'    => $r['duration_ms'],
            'quota_exceeded' => !$r['ok'] && ($r['http_status'] === 429
                || str_contains(mb_strtolower((string) $r['error']), 'run out of searches')),
        ];
    }

    /** SerpApi's free Account API says the plan has no searches left. */
    public function remoteQuotaExhausted(): bool
    {
        $account = $this->accountUsage();

        return is_array($account) && isset($account['total_searches_left'])
            && (int) $account['total_searches_left'] <= 0;
    }

    public function attribution(): ?array
    {
        return null;
    }

    public function isConfigured(): bool
    {
        return trim((string) config('services.serpapi.key')) !== '';
    }

    public function query(): string
    {
        return (string) config('services.serpapi.maps_query', 'popular places');
    }

    public function zoom(): int
    {
        return (int) config('services.serpapi.maps_zoom', 16);
    }

    /**
     * One Google Maps search around a point.
     *
     * @return array{ok: bool, http_status: ?int, places: array, search_id: ?string,
     *               error: ?string, consumed: int, duration_ms: int}
     */
    public function searchNearbyPlaces(float $lat, float $lng): array
    {
        $started = microtime(true);
        $result = [
            'ok'          => false,
            'http_status' => null,
            'places'      => [],
            'search_id'   => null,
            'error'       => null,
            'consumed'    => 0,
            'duration_ms' => 0,
        ];

        try {
            $response = Http::timeout((int) config('services.serpapi.timeout', 20))
                ->acceptJson()
                ->get($this->url('/search.json'), [
                    'engine'  => self::ENGINE,
                    'type'    => 'search',
                    'q'       => $this->query(),
                    'll'      => sprintf('@%s,%s,%dz', $lat, $lng, $this->zoom()),
                    'hl'      => config('services.serpapi.hl', 'en'),
                    'gl'      => config('services.serpapi.gl', 'in'),
                    'api_key' => config('services.serpapi.key'),
                ]);

            $result['http_status'] = $response->status();
            $json = $response->json();

            if (!is_array($json)) {
                $result['error'] = 'SerpApi returned a response that is not JSON.';
            } elseif (!empty($json['error'])) {
                // SerpApi reports problems (bad key, no results, rate limit) in
                // an "error" field, sometimes with HTTP 200.
                $result['error'] = (string) $json['error'];
            } elseif (!$response->successful()) {
                $result['error'] = 'SerpApi returned HTTP ' . $response->status() . '.';
            } else {
                $status = data_get($json, 'search_metadata.status');
                if ($status !== null && $status !== 'Success') {
                    $result['error'] = 'SerpApi search status: ' . $status . '.';
                } else {
                    $result['ok']        = true;
                    $result['search_id'] = data_get($json, 'search_metadata.id');
                    $result['places']    = $this->mapPlaces($json['local_results'] ?? []);
                    // Counted as one search. SerpApi does not bill a result it
                    // served from its own 1-hour cache, so this can over-count
                    // (never under-count); the admin usage page shows SerpApi's
                    // own this_month_usage alongside for reconciliation.
                    $result['consumed']  = 1;
                }
            }
        } catch (ConnectionException $e) {
            $result['error'] = 'Could not reach SerpApi (timeout or network error).';
            Log::warning('SerpApi connection failed: ' . $this->scrub($e->getMessage()));
        } catch (Throwable $e) {
            $result['error'] = 'Unexpected error while calling SerpApi.';
            Log::error('SerpApi call failed: ' . $this->scrub($e->getMessage()));
        }

        $result['error']       = $result['error'] !== null ? $this->scrub($result['error']) : null;
        $result['duration_ms'] = (int) round((microtime(true) - $started) * 1000);

        return $result;
    }

    /**
     * SerpApi Account API — free, does not count toward the monthly quota
     * (https://serpapi.com/account-api). Cached for 10 minutes. Returns only
     * the documented usage fields, never api_key or account_email.
     */
    public function accountUsage(): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        return Cache::remember('serpapi:account-usage', now()->addMinutes(10), function () {
            try {
                $response = Http::timeout(10)->acceptJson()
                    ->get($this->url('/account.json'), ['api_key' => config('services.serpapi.key')]);

                $json = $response->json();
                if (!$response->successful() || !is_array($json) || !empty($json['error'])) {
                    return null;
                }

                return array_intersect_key($json, array_flip([
                    'plan_name',
                    'searches_per_month',
                    'plan_searches_left',
                    'extra_credits',
                    'total_searches_left',
                    'this_month_usage',
                    'plan_renewal_date',
                    'account_rate_limit_per_hour',
                    'last_hour_searches',
                ]));
            } catch (Throwable $e) {
                Log::warning('SerpApi account check failed: ' . $this->scrub($e->getMessage()));
                return null;
            }
        });
    }

    /**
     * Keep only documented fields that are actually present.
     */
    public function mapPlaces($items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $places = [];
        $seen = [];

        foreach ($items as $item) {
            if (!is_array($item) || empty($item['title'])) {
                continue;
            }

            $lat = data_get($item, 'gps_coordinates.latitude');
            $lng = data_get($item, 'gps_coordinates.longitude');

            $place = array_filter([
                'title'      => (string) $item['title'],
                'place_id'   => $item['place_id'] ?? null,
                'category'   => isset($item['type']) ? (string) $item['type'] : null,
                'categories' => isset($item['types']) && is_array($item['types']) ? array_values($item['types']) : null,
                'rating'     => isset($item['rating']) && is_numeric($item['rating']) ? (float) $item['rating'] : null,
                'reviews'    => isset($item['reviews']) && is_numeric($item['reviews']) ? (int) $item['reviews'] : null,
                'price'      => isset($item['price']) ? (string) $item['price'] : null,
                'address'    => isset($item['address']) ? (string) $item['address'] : null,
                'lat'        => is_numeric($lat) ? (float) $lat : null,
                'lng'        => is_numeric($lng) ? (float) $lng : null,
            ], fn($v) => $v !== null);

            // Same place twice in one result: keep the first.
            $dedupeKey = $place['place_id'] ?? mb_strtolower($place['title']);
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            $places[] = $place;
        }

        return $places;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.serpapi.base_url', 'https://serpapi.com'), '/') . $path;
    }

    /** Remove the API key from any text before it is logged, stored or shown. */
    public function scrub(string $text): string
    {
        $key = (string) config('services.serpapi.key');

        $text = $key !== '' ? str_replace($key, '[redacted]', $text) : $text;

        return preg_replace('/api_key=[^&\s"]+/i', 'api_key=[redacted]', $text);
    }
}

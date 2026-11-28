<?php

namespace App\Http\Services\Insights;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * TomTom Search API — Nearby Search
 * https://developer.tomtom.com/search-api/documentation/search-service/nearby-search
 *
 *   GET {base}/search/2/nearbySearch/.json
 *     lat, lon      centre point
 *     radius        metres
 *     limit         1–100
 *     categorySet   comma-separated POI category ids (see config/services.php)
 *     language      e.g. en-GB
 *     key           from TOMTOM_API_KEY
 *
 * Response: results[] — this reads id, dist, poi.name, poi.categories,
 * poi.classifications[].code, address.freeformAddress, position.lat/lon.
 * A missing field is left out, never filled in.
 *
 * Billing: one request is one transaction, whatever the number of results
 * (free plan: 2,500 non-tile requests a day). Failed requests are recorded
 * as 0. 403 is reported as a key problem, 429 as a quota/rate limit.
 */
class TomTomPlacesService implements PlacesProvider
{
    /* Readable labels for TomTom classification codes, most specific first. */
    private const LABELS = [
        'COLLEGE_UNIVERSITY'           => ['College / University', 'Education'],
        'SCHOOL'                       => ['School', 'Education'],
        'HOSPITAL_POLYCLINIC'          => ['Hospital', 'Healthcare'],
        'HEALTH_CARE_SERVICE'          => ['Clinic', 'Healthcare'],
        'DOCTOR'                       => ['Clinic', 'Healthcare'],
        'SHOPPING_CENTER'              => ['Shopping Mall', 'Commercial'],
        'MARKET'                       => ['Marketplace', 'Commercial'],
        'PARK_RECREATION_AREA'         => ['Park', 'Leisure'],
        'IMPORTANT_TOURIST_ATTRACTION' => ['Tourist Attraction', 'Tourism'],
        'TOURIST_ATTRACTION'           => ['Tourist Attraction', 'Tourism'],
        'GOVERNMENT_OFFICE'            => ['Government Office', 'Office'],
        'COMPANY'                      => ['Company Office', 'Office'],
        'BANK'                         => ['Bank', 'Office'],
    ];

    public function name(): string
    {
        return 'tomtom';
    }

    public function isConfigured(): bool
    {
        return trim((string) config('services.tomtom.key')) !== '';
    }

    public function categorySet(): string
    {
        return (string) config('services.tomtom.category_set');
    }

    public function signature(): string
    {
        return implode('|', [$this->categorySet(), $this->limit(), $this->radiusMeters()]);
    }

    public function coordPrecision(): int
    {
        return (int) config('services.tomtom.coord_precision', 3);
    }

    public function radiusMeters(): ?int
    {
        return max(1, (int) config('services.tomtom.radius', 1000));
    }

    public function cacheDays(): int
    {
        return max(1, (int) config('services.tomtom.cache_days', 90));
    }

    public function limit(): int
    {
        return max(1, min(100, (int) config('services.tomtom.limit', 60)));
    }

    public function budget(): array
    {
        return [
            'period'   => 'day',
            'limit'    => max(0, (int) config('services.tomtom.daily_request_limit', 2000)),
            'max_cost' => 1,
        ];
    }

    public function fetch(float $lat, float $lng): array
    {
        $started = microtime(true);
        $result = [
            'ok'             => false,
            'http_status'    => null,
            'places'         => [],
            'external_id'    => null,
            'error'          => null,
            'credits'        => 0,
            'duration_ms'    => 0,
            'quota_exceeded' => false,
        ];

        try {
            $response = Http::timeout((int) config('services.tomtom.timeout', 15))
                ->acceptJson()
                ->get($this->url('/search/2/nearbySearch/.json'), array_filter([
                    'lat'         => $lat,
                    'lon'         => $lng,
                    'radius'      => $this->radiusMeters(),
                    'limit'       => $this->limit(),
                    'categorySet' => $this->categorySet() ?: null,
                    'language'    => config('services.tomtom.language', 'en-GB'),
                    'key'         => config('services.tomtom.key'),
                ], fn($v) => $v !== null));

            $result['http_status'] = $response->status();
            $json = $response->json();

            if ($response->status() === 429) {
                $result['quota_exceeded'] = true;
                $result['error'] = 'TomTom request limit or daily quota exceeded (HTTP 429).';
            } elseif (in_array($response->status(), [401, 403], true)) {
                $result['error'] = 'TomTom rejected the API key (HTTP ' . $response->status() . ').';
            } elseif (!$response->successful()) {
                $detail = is_array($json) ? ($json['errorText'] ?? $json['error']['description'] ?? null) : null;
                $result['error'] = 'TomTom returned HTTP ' . $response->status() . ($detail ? ': ' . $detail : '.');
            } elseif (!is_array($json) || !isset($json['results']) || !is_array($json['results'])) {
                $result['error'] = 'TomTom returned an unexpected response (no results list).';
            } else {
                $result['ok']          = true;
                $result['places']      = $this->mapResults($json['results']);
                // One transaction per request, however many results.
                $result['credits']     = 1;
            }
        } catch (ConnectionException $e) {
            $result['error'] = 'Could not reach TomTom (timeout or network error).';
            Log::warning('TomTom connection failed: ' . $this->scrub($e->getMessage()));
        } catch (Throwable $e) {
            $result['error'] = 'Unexpected error while calling TomTom.';
            Log::error('TomTom call failed: ' . $this->scrub($e->getMessage()));
        }

        $result['error']       = $result['error'] !== null ? $this->scrub($result['error']) : null;
        $result['duration_ms'] = (int) round((microtime(true) - $started) * 1000);

        return $result;
    }

    /** Keep only fields that are present; drop exact duplicates by id. */
    public function mapResults(array $results): array
    {
        $places = [];
        $seen = [];

        foreach ($results as $r) {
            if (!is_array($r)) {
                continue;
            }

            $poi = is_array($r['poi'] ?? null) ? $r['poi'] : [];
            $codes = array_values(array_filter(array_map(
                fn($c) => is_array($c) ? ($c['code'] ?? null) : null,
                is_array($poi['classifications'] ?? null) ? $poi['classifications'] : []
            )));
            $cats = is_array($poi['categories'] ?? null) ? array_values($poi['categories']) : [];
            [$label, $group] = $this->label($codes, $cats);

            $name = isset($poi['name']) && trim((string) $poi['name']) !== '' ? trim((string) $poi['name']) : null;
            $lat = data_get($r, 'position.lat');
            $lng = data_get($r, 'position.lon');

            // Raw keys: TomTom's category names plus the classification codes,
            // so the scoring keywords (college, hospital, mall …) can match.
            $raw = array_values(array_unique(array_merge($cats, array_map('strtolower', $codes))));

            $place = array_filter([
                'title'      => $name,
                'place_id'   => isset($r['id']) ? (string) $r['id'] : null,
                'category'   => $label,
                'group'      => $group,
                'categories' => $raw ?: null,
                'address'    => data_get($r, 'address.freeformAddress'),
                'lat'        => is_numeric($lat) ? (float) $lat : null,
                'lng'        => is_numeric($lng) ? (float) $lng : null,
            ], fn($v) => $v !== null);

            $key = $place['place_id'] ?? (($name ?? '') . '|' . ($place['lat'] ?? '') . '|' . ($place['lng'] ?? ''));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $places[] = $place;
        }

        return $places;
    }

    /** Most specific label we know for a place's classification codes. */
    private function label(array $codes, array $categories): array
    {
        foreach (self::LABELS as $code => $label) {
            if (in_array($code, $codes, true)) {
                return $label;
            }
        }

        $first = $categories[0] ?? ($codes[0] ?? null);

        return $first
            ? [ucwords(str_replace(['_', '/'], [' ', ' / '], mb_strtolower($first))), null]
            : [null, null];
    }

    public function remoteQuotaExhausted(): bool
    {
        // TomTom has no free usage endpoint; our daily budget and 429
        // handling are the controls.
        return false;
    }

    public function accountUsage(): ?array
    {
        return null;
    }

    public function attribution(): ?array
    {
        return ['text' => '© TomTom', 'url' => 'https://www.tomtom.com/'];
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.tomtom.base_url', 'https://api.tomtom.com'), '/') . $path;
    }

    public function scrub(string $text): string
    {
        $key = (string) config('services.tomtom.key');

        $text = $key !== '' ? str_replace($key, '[redacted]', $text) : $text;

        return preg_replace('/([?&])key=[^&\s"]+/i', '$1key=[redacted]', $text);
    }
}

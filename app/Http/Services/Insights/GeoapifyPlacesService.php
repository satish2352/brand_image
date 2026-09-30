<?php

namespace App\Http\Services\Insights;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Geoapify Places API — https://apidocs.geoapify.com/docs/places/
 *
 *   GET {base}/v2/places
 *     categories  comma-separated category keys (parents include children)
 *     filter      circle:{lon},{lat},{radiusMeters}   ← longitude first
 *     bias        proximity:{lon},{lat}   so the nearest places fill `limit`
 *     limit       1–500
 *     lang        response language
 *     apiKey      from GEOAPIFY_API_KEY
 *
 * Response: GeoJSON FeatureCollection; this reads features[].properties
 * name, categories, formatted, address_line1, address_line2, lat, lon,
 * place_id. A missing field is left out, never filled in.
 *
 * Billing (Geoapify docs): every 20 places returned costs 1 credit. A request
 * returning 0 places is recorded as 1 credit — an assumption, so the budget
 * errs on the side of over-counting. Failed requests are recorded as 0.
 *
 * Geoapify does not document its error status codes; any non-2xx is a
 * failure, 401/403 is reported as a key problem and 429 as a quota/rate limit.
 */
class GeoapifyPlacesService implements PlacesProvider
{
    /* Readable labels for the categories we request, most specific first. */
    private const LABELS = [
        'education.university'        => ['University', 'Education'],
        'education.college'           => ['College', 'Education'],
        'education.school'            => ['School', 'Education'],
        'commercial.shopping_mall'    => ['Shopping Mall', 'Commercial'],
        'commercial.marketplace'      => ['Marketplace', 'Commercial'],
        'building.commercial'         => ['Commercial Building', 'Commercial'],
        'healthcare.hospital'         => ['Hospital', 'Healthcare'],
        'healthcare.clinic_or_praxis' => ['Clinic', 'Healthcare'],
        'leisure.park'                => ['Park', 'Leisure'],
        'tourism.attraction'          => ['Tourist Attraction', 'Tourism'],
        'tourism.sights'              => ['Sight / Landmark', 'Tourism'],
        'office.government'           => ['Government Office', 'Office'],
        'office.it'                   => ['IT Office', 'Office'],
        'office.company'              => ['Company Office', 'Office'],
        'office.financial'            => ['Financial Office', 'Office'],
        'office.coworking'            => ['Coworking', 'Office'],
        'office'                      => ['Office', 'Office'],
    ];

    public function name(): string
    {
        return 'geoapify';
    }

    public function isConfigured(): bool
    {
        return trim((string) config('services.geoapify.key')) !== '';
    }

    public function categories(): string
    {
        return (string) config('services.geoapify.categories');
    }

    public function signature(): string
    {
        return implode('|', [$this->categories(), $this->limit(), $this->radiusMeters()]);
    }

    public function coordPrecision(): int
    {
        return (int) config('services.geoapify.coord_precision', 3);
    }

    public function radiusMeters(): ?int
    {
        return max(1, (int) config('services.geoapify.radius', 1000));
    }

    public function cacheDays(): int
    {
        return max(1, (int) config('services.geoapify.cache_days', 90));
    }

    public function limit(): int
    {
        return max(1, min(500, (int) config('services.geoapify.limit', 60)));
    }

    public function budget(): array
    {
        return [
            'period'   => 'day',
            'limit'    => max(0, (int) config('services.geoapify.daily_credit_limit', 2500)),
            'max_cost' => (int) ceil($this->limit() / 20),
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
            $response = Http::timeout((int) config('services.geoapify.timeout', 15))
                ->acceptJson()
                ->get($this->url('/v2/places'), [
                    'categories' => $this->categories(),
                    'filter'     => sprintf('circle:%s,%s,%d', $lng, $lat, $this->radiusMeters()),
                    'bias'       => sprintf('proximity:%s,%s', $lng, $lat),
                    'limit'      => $this->limit(),
                    'lang'       => config('services.geoapify.lang', 'en'),
                    'apiKey'     => config('services.geoapify.key'),
                ]);

            $result['http_status'] = $response->status();
            $json = $response->json();

            if ($response->status() === 429) {
                $result['quota_exceeded'] = true;
                $result['error'] = 'Geoapify request limit or daily quota exceeded (HTTP 429).';
            } elseif (in_array($response->status(), [401, 403], true)) {
                $result['error'] = 'Geoapify rejected the API key (HTTP ' . $response->status() . ').';
            } elseif (!$response->successful()) {
                $detail = is_array($json) ? ($json['message'] ?? $json['error'] ?? null) : null;
                $result['error'] = 'Geoapify returned HTTP ' . $response->status() . ($detail ? ': ' . $detail : '.');
            } elseif (!is_array($json) || !isset($json['features']) || !is_array($json['features'])) {
                $result['error'] = 'Geoapify returned an unexpected response (no features list).';
            } else {
                $result['ok']      = true;
                $result['places']  = $this->mapFeatures($json['features']);
                // 1 credit per 20 places returned; an empty result recorded as 1.
                $result['credits'] = max(1, (int) ceil(count($json['features']) / 20));
            }
        } catch (ConnectionException $e) {
            $result['error'] = 'Could not reach Geoapify (timeout or network error).';
            Log::warning('Geoapify connection failed: ' . $this->scrub($e->getMessage()));
        } catch (Throwable $e) {
            $result['error'] = 'Unexpected error while calling Geoapify.';
            Log::error('Geoapify call failed: ' . $this->scrub($e->getMessage()));
        }

        $result['error']       = $result['error'] !== null ? $this->scrub($result['error']) : null;
        $result['duration_ms'] = (int) round((microtime(true) - $started) * 1000);

        return $result;
    }

    /** Keep only fields that are present; drop exact duplicates by place_id. */
    public function mapFeatures(array $features): array
    {
        $places = [];
        $seen = [];

        foreach ($features as $feature) {
            $p = is_array($feature) ? ($feature['properties'] ?? null) : null;
            if (!is_array($p)) {
                continue;
            }

            $lat = $p['lat'] ?? data_get($feature, 'geometry.coordinates.1');
            $lng = $p['lon'] ?? data_get($feature, 'geometry.coordinates.0');
            $cats = isset($p['categories']) && is_array($p['categories']) ? array_values($p['categories']) : [];
            [$label, $group] = $this->label($cats);

            $name = isset($p['name']) && trim((string) $p['name']) !== '' ? trim((string) $p['name']) : null;

            $place = array_filter([
                'title'      => $name,
                'place_id'   => isset($p['place_id']) ? (string) $p['place_id'] : null,
                'category'   => $label,
                'group'      => $group,
                'categories' => $cats ?: null,
                'address'    => $p['formatted'] ?? (isset($p['address_line2']) ? $p['address_line2'] : null),
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

    /** Most specific label we know for a place's category keys. */
    private function label(array $categories): array
    {
        foreach (self::LABELS as $key => $label) {
            foreach ($categories as $c) {
                if ($c === $key || str_starts_with($c, $key . '.')) {
                    return $label;
                }
            }
        }

        $first = $categories[0] ?? null;

        return $first
            ? [ucwords(str_replace('_', ' ', substr(strrchr('.' . $first, '.'), 1))), ucfirst(strtok($first, '.'))]
            : [null, null];
    }

    public function remoteQuotaExhausted(): bool
    {
        // Geoapify has no free usage endpoint; our daily budget and 429
        // handling are the controls.
        return false;
    }

    public function accountUsage(): ?array
    {
        return null;
    }

    public function attribution(): ?array
    {
        // Required on the free plan near map/places information.
        return ['text' => 'Powered by Geoapify', 'url' => 'https://www.geoapify.com/'];
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.geoapify.base_url', 'https://api.geoapify.com'), '/') . $path;
    }

    public function scrub(string $text): string
    {
        $key = (string) config('services.geoapify.key');

        $text = $key !== '' ? str_replace($key, '[redacted]', $text) : $text;

        return preg_replace('/apiKey=[^&\s"]+/i', 'apiKey=[redacted]', $text);
    }
}

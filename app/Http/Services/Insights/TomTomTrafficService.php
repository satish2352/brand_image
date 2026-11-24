<?php

namespace App\Http\Services\Insights;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * TomTom Traffic API — Flow Segment Data
 * https://developer.tomtom.com/traffic-api/documentation/traffic-flow/flow-segment-data
 *
 *   GET {base}/traffic/services/4/flowSegmentData/absolute/10/json
 *     point    "lat,lon"
 *     unit     KMPH
 *     openLr   false
 *     key      from TOMTOM_API_KEY
 *
 * Response: flowSegmentData — this reads frc, currentSpeed, freeFlowSpeed,
 * currentTravelTime, freeFlowTravelTime, confidence, roadClosure.
 * A missing field is returned as null, never filled in.
 *
 * These are live speeds and travel times on the road segment nearest the
 * point. They are NOT a daily vehicle count, footfall or impressions, and
 * nothing here claims they are.
 *
 * The key is added to the outgoing request only. It is scrubbed from every
 * error message this class returns or logs, because a cURL/Guzzle exception
 * message contains the full request URL.
 */
class TomTomTrafficService
{
    private const PATH = '/traffic/services/4/flowSegmentData/absolute/10/json';

    private const FIELDS = [
        'frc', 'currentSpeed', 'freeFlowSpeed', 'currentTravelTime',
        'freeFlowTravelTime', 'confidence', 'roadClosure',
    ];

    public function isConfigured(): bool
    {
        return trim((string) config('services.tomtom.api_key')) !== '';
    }

    /**
     * Traffic flow on the road segment nearest one coordinate.
     *
     * 'raw' is TomTom's flowSegmentData object as returned (no key in it), for
     * auditing; callers that only need the values use 'data'.
     *
     * @return array{ok: bool, http_status: ?int, data: ?array, raw: ?array, error: ?string, duration_ms: int}
     */
    public function getTrafficFlow(float $latitude, float $longitude): array
    {
        $started = microtime(true);
        $result = [
            'ok'          => false,
            'http_status' => null,
            'data'        => null,
            'raw'         => null,
            'error'       => null,
            'duration_ms' => 0,
        ];

        if (!is_finite($latitude) || $latitude < -90 || $latitude > 90) {
            $result['error'] = 'Latitude must be between -90 and 90.';
            return $result;
        }
        if (!is_finite($longitude) || $longitude < -180 || $longitude > 180) {
            $result['error'] = 'Longitude must be between -180 and 180.';
            return $result;
        }
        if (!$this->isConfigured()) {
            $result['error'] = 'TomTom API key is not configured (TOMTOM_API_KEY).';
            return $result;
        }

        try {
            $response = Http::timeout((int) config('services.tomtom.timeout', 15))
                ->acceptJson()
                ->get($this->url(self::PATH), [
                    'point'  => $latitude . ',' . $longitude,
                    'unit'   => 'KMPH',
                    'openLr' => 'false',
                    'key'    => config('services.tomtom.api_key'),
                ]);

            $result['http_status'] = $response->status();
            $json = $response->body() !== '' ? $response->json() : null;
            $segment = is_array($json) ? ($json['flowSegmentData'] ?? null) : null;

            if ($response->status() === 429) {
                $result['error'] = 'TomTom request limit or daily quota exceeded (HTTP 429).';
            } elseif (in_array($response->status(), [401, 403], true)) {
                $result['error'] = 'TomTom rejected the API key (HTTP ' . $response->status() . ').';
            } elseif ($response->failed()) {
                $detail = $this->errorDetail($json);
                $result['error'] = 'TomTom returned HTTP ' . $response->status() . ($detail ? ': ' . $detail : '.');
            } elseif (!is_array($segment)) {
                $result['error'] = 'TomTom returned an unexpected response (no flowSegmentData).';
            } else {
                $result['data'] = $this->map($segment);
                $result['raw'] = $segment;
                $result['ok'] = true;
            }

            if (!$result['ok']) {
                Log::warning('TomTom traffic flow failed: ' . $result['error']);
            }
        } catch (ConnectionException $e) {
            $result['error'] = 'Could not reach TomTom (timeout or network error).';
            Log::warning('TomTom traffic connection failed: ' . $this->scrub($e->getMessage()));
        } catch (Throwable $e) {
            $result['error'] = 'Unexpected error while calling TomTom.';
            Log::error('TomTom traffic call failed: ' . $this->scrub($e->getMessage()));
        }

        $result['duration_ms'] = (int) round((microtime(true) - $started) * 1000);

        return $result;
    }

    private function map(array $segment): array
    {
        $data = [];
        foreach (self::FIELDS as $field) {
            $data[$field] = $segment[$field] ?? null;
        }

        $data['frc']                = $data['frc'] !== null ? (string) $data['frc'] : null;
        $data['currentSpeed']       = is_numeric($data['currentSpeed']) ? (float) $data['currentSpeed'] : null;
        $data['freeFlowSpeed']      = is_numeric($data['freeFlowSpeed']) ? (float) $data['freeFlowSpeed'] : null;
        $data['currentTravelTime']  = is_numeric($data['currentTravelTime']) ? (int) $data['currentTravelTime'] : null;
        $data['freeFlowTravelTime'] = is_numeric($data['freeFlowTravelTime']) ? (int) $data['freeFlowTravelTime'] : null;
        $data['confidence']         = is_numeric($data['confidence']) ? (float) $data['confidence'] : null;
        $data['roadClosure']        = is_bool($data['roadClosure']) ? $data['roadClosure'] : null;
        $data['unit']               = 'KMPH';

        return $data;
    }

    /* TomTom errors come as detailedError.message or a plain error string. */
    private function errorDetail($json): ?string
    {
        if (!is_array($json)) {
            return null;
        }

        $detail = $json['detailedError']['message'] ?? $json['error'] ?? $json['errorText'] ?? null;

        return is_string($detail) ? $this->scrub(mb_substr($detail, 0, 200)) : null;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.tomtom.base_url', 'https://api.tomtom.com'), '/') . $path;
    }

    public function scrub(string $text): string
    {
        $key = (string) config('services.tomtom.api_key');

        $text = $key !== '' ? str_replace($key, '[redacted]', $text) : $text;

        return preg_replace('/([?&])key=[^&\s"]+/i', '$1key=[redacted]', $text);
    }
}

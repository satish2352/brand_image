<?php

namespace App\Http\Services\Insights;

use App\Models\HoardingTrafficData;
use App\Models\MediaManagement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TomTom Traffic Flow → hoarding_traffic_data.
 *
 *   fetchAndStore()  the ONLY method that calls TomTom (via
 *                    TomTomTrafficService). Used by the scheduled
 *                    `refresh:hoarding-traffic` command.
 *   forDisplay()     reads MySQL only — what pages and APIs use.
 *
 * A hoarding is due when it has no row yet, its next_refresh_at has passed,
 * or it was moved (coordinates differ from the ones last requested).
 * Success: values replaced, error cleared, next refresh in
 * traffic_refresh_days. Failure: previous values are kept, the error is
 * recorded and the hoarding is tried again after traffic_retry_hours
 * (or the full refresh interval for a 4xx such as "no road segment nearby").
 *
 * Nothing here turns speeds into a vehicle count.
 */
class HoardingTrafficService
{
    public function __construct(private TomTomTrafficService $tomTom) {}

    public function isConfigured(): bool
    {
        return $this->tomTom->isConfigured();
    }

    /* ============================ FETCH (spends a request) ============================ */

    /**
     * Fetch and store traffic flow for one hoarding.
     *
     * @param MediaManagement|int $hoarding
     * @return array{status: string, message: string, http_status: ?int, record: ?HoardingTrafficData}
     *         status: success | failed | invalid_location | not_found
     */
    public function fetchAndStore($hoarding): array
    {
        $media = $hoarding instanceof MediaManagement
            ? $hoarding
            : MediaManagement::query()->where('is_deleted', 0)->find((int) $hoarding);

        if (!$media) {
            return $this->outcome('not_found', 'Hoarding not found.');
        }

        $lat = $media->latitude;
        $lng = $media->longitude;
        if (!$this->validCoordinates($lat, $lng)) {
            return $this->outcome('invalid_location', 'Hoarding has no valid latitude/longitude.');
        }

        $lat = (float) $lat;
        $lng = (float) $lng;
        $result = $this->tomTom->getTrafficFlow($lat, $lng);
        $now = now();

        $values = [
            'latitude'         => $lat,
            'longitude'        => $lng,
            'last_http_status' => $result['http_status'],
            'last_attempt_at'  => $now,
        ];

        if ($result['ok']) {
            $d = $result['data'];
            $values += [
                'frc'                   => $d['frc'],
                'current_speed'         => $d['currentSpeed'],
                'free_flow_speed'       => $d['freeFlowSpeed'],
                'current_travel_time'   => $d['currentTravelTime'],
                'free_flow_travel_time' => $d['freeFlowTravelTime'],
                'confidence'            => $d['confidence'],
                'road_closure'          => $d['roadClosure'],
                'raw_response'          => $result['raw'],
                'traffic_updated_at'    => $now,
                'next_refresh_at'       => $now->copy()->addDays($this->refreshDays()),
                'last_api_status'       => HoardingTrafficData::SUCCESS,
                'last_api_error'        => null,
            ];
        } else {
            // Keep the last good values; only the bookkeeping changes.
            // A 4xx about the request itself (e.g. 400 "Point too far from
            // nearest existing segment") will not change by tomorrow, so it
            // waits the full refresh interval; key/quota/5xx/timeouts retry sooner.
            $status = (int) $result['http_status'];
            $permanent = $status >= 400 && $status < 500 && !in_array($status, [401, 403, 429], true);
            $values += [
                'next_refresh_at' => $permanent
                    ? $now->copy()->addDays($this->refreshDays())
                    : $now->copy()->addHours($this->retryHours()),
                'last_api_status' => HoardingTrafficData::FAILED,
                'last_api_error'  => $result['error'],
            ];

            Log::warning('TomTom traffic refresh failed', [
                'media_id'    => $media->id,
                'http_status' => $result['http_status'],
                'message'     => $result['error'],
            ]);
        }

        $record = HoardingTrafficData::updateOrCreate(['media_id' => $media->id], $values);

        return $this->outcome(
            $result['ok'] ? HoardingTrafficData::SUCCESS : HoardingTrafficData::FAILED,
            $result['ok'] ? 'Traffic flow updated.' : (string) $result['error'],
            $record,
            $result['http_status']
        );
    }

    /* ============================ SCHEDULING ============================ */

    /**
     * Hoardings due a fetch: active, not deleted, valid coordinates, and
     * (no row yet | next_refresh_at passed | moved). Select m.id, latitude,
     * longitude — page through it with chunkById('m.id', 'id').
     */
    public function dueQuery()
    {
        $now = now();

        return DB::table('media_management as m')
            ->leftJoin('hoarding_traffic_data as t', 't.media_id', '=', 'm.id')
            ->where('m.is_deleted', 0)
            ->where('m.is_active', 1)
            ->whereNotNull('m.latitude')->whereNotNull('m.longitude')
            ->where('m.latitude', '!=', 0)->where('m.longitude', '!=', 0)
            ->whereBetween('m.latitude', [-90, 90])
            ->whereBetween('m.longitude', [-180, 180])
            ->where(function ($q) use ($now) {
                $q->whereNull('t.id')
                    ->orWhereNull('t.next_refresh_at')
                    ->orWhere('t.next_refresh_at', '<=', $now)
                    ->orWhereRaw('ABS(t.latitude - m.latitude) > 0.0000005')
                    ->orWhereRaw('ABS(t.longitude - m.longitude) > 0.0000005');
            })
            ->select('m.id', 'm.latitude', 'm.longitude');
    }

    public function dueCount(): int
    {
        return $this->dueQuery()->count('m.id');
    }

    /** Traffic requests already made today (success or not). */
    public function usedToday(): int
    {
        return HoardingTrafficData::where('last_attempt_at', '>=', now()->startOfDay())->count();
    }

    public function dailyLimit(): int
    {
        return max(0, (int) config('services.tomtom.traffic_daily_limit', 2000));
    }

    public function remainingToday(): int
    {
        return max(0, $this->dailyLimit() - $this->usedToday());
    }

    public function batchSize(): int
    {
        return max(1, (int) config('services.tomtom.traffic_batch_size', 100));
    }

    public function requestDelayMs(): int
    {
        return max(0, (int) config('services.tomtom.traffic_request_delay_ms', 250));
    }

    /* ============================ READ (database only) ============================ */

    /**
     * Stored traffic flow for one hoarding, for pages and APIs. Never calls
     * TomTom. Leaves out raw_response and error text.
     */
    public function forDisplay(int $mediaId): array
    {
        $t = HoardingTrafficData::where('media_id', $mediaId)->first();

        if (!$t || !$t->traffic_updated_at) {
            return ['traffic_data_available' => false, 'traffic' => null];
        }

        return [
            'traffic_data_available' => true,
            'traffic' => [
                'frc'                   => $t->frc,
                'current_speed'         => $t->current_speed !== null ? (float) $t->current_speed : null,
                'free_flow_speed'       => $t->free_flow_speed !== null ? (float) $t->free_flow_speed : null,
                'speed_unit'            => 'KMPH',
                'current_travel_time'   => $t->current_travel_time,
                'free_flow_travel_time' => $t->free_flow_travel_time,
                'travel_time_unit'      => 'seconds',
                'confidence'            => $t->confidence !== null ? (float) $t->confidence : null,
                'road_closure'          => $t->road_closure,
                'traffic_updated_at'    => $t->traffic_updated_at->toDateTimeString(),
                'next_refresh_at'       => $t->next_refresh_at?->toDateTimeString(),
                'source'                => 'TomTom Traffic Flow',
            ],
        ];
    }

    /* ============================ HELPERS ============================ */

    private function validCoordinates($lat, $lng): bool
    {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return false;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        return !($lat == 0 && $lng == 0)
            && $lat >= -90 && $lat <= 90
            && $lng >= -180 && $lng <= 180;
    }

    private function refreshDays(): int
    {
        return max(1, (int) config('services.tomtom.traffic_refresh_days', 15));
    }

    private function retryHours(): int
    {
        return max(1, (int) config('services.tomtom.traffic_retry_hours', 24));
    }

    private function outcome(string $status, string $message, ?HoardingTrafficData $record = null, ?int $http = null): array
    {
        return ['status' => $status, 'message' => $message, 'http_status' => $http, 'record' => $record];
    }
}

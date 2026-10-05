<?php

namespace App\Http\Services\RoadStar;

use App\Models\MediaManagement;
use App\Models\RoadStarAudienceData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * RoadStar audience → MySQL, for hoardings in media_management.
 *
 *   map()        link a hoarding to an existing RoadStar site id
 *   register()   POST /addsite with our own site id + the hoarding's lat/lng
 *   sync()       POST /sitedata for up to ROADSTAR_BATCH_SIZE hoardings and
 *                store each one's audience (roadstar_audience_data)
 *   preview()    POST /sitedata for one hoarding, parsed, nothing stored
 *   dueQuery()   mapped hoardings due a refresh (15 days)
 *   forDisplay() MySQL only — what the admin page shows
 *
 * A hoarding's RoadStar site id is stored in media_management.roadstar_site_id
 * and is never derived from the media id at sync time.
 *
 * Duplicate protection: each hoarding is locked (cache lock) for the length of
 * its request, so two jobs, a job and a button press, or two button presses
 * never fetch the same hoarding at once; a locked hoarding is reported "busy".
 */
class RoadStarSyncService
{
    public const PENDING       = 'pending';
    public const QUEUED        = 'queued';
    public const SUCCESS       = 'success';
    public const NO_DATA       = 'no_data';
    public const NOT_AVAILABLE = 'not_available';
    public const FAILED        = 'failed';

    /** Outcomes that never reached RoadStar for this hoarding. */
    public const SKIPPED = ['not_found', 'not_mapped', 'busy'];

    /** RoadStar ids look like CWMS/MUM/WEH-000009. */
    public const SITE_ID_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9\/_.\-]{0,99}$/';

    private const LOCK_SECONDS = 300;

    public function __construct(private RoadStarService $client) {}

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    /* ============================ MAPPING ============================ */

    /**
     * Link a hoarding to a RoadStar site id that already exists in RoadStar,
     * or unlink it (null). A changed id makes the hoarding due immediately.
     *
     * @return array{ok: bool, message: string}
     */
    public function map(int $mediaId, ?string $siteId): array
    {
        $media = $this->media($mediaId);
        if (!$media) {
            return ['ok' => false, 'message' => 'Hoarding not found.'];
        }

        $siteId = $siteId !== null ? trim($siteId) : null;
        if ($siteId === '' || $siteId === null) {
            $this->update($mediaId, [
                'roadstar_site_id' => null, 'roadstar_sync_status' => null, 'roadstar_last_synced_at' => null,
                'roadstar_last_attempt_at' => null, 'roadstar_sync_error' => null,
            ]);
            return ['ok' => true, 'message' => 'RoadStar mapping removed.'];
        }

        if ($error = $this->siteIdError($siteId, $mediaId)) {
            return ['ok' => false, 'message' => $error];
        }
        if ($siteId === $media->roadstar_site_id) {
            return ['ok' => true, 'message' => 'Hoarding is already mapped to ' . $siteId . '.'];
        }

        $this->update($mediaId, [
            'roadstar_site_id' => $siteId, 'roadstar_sync_status' => self::PENDING, 'roadstar_last_synced_at' => null,
            'roadstar_last_attempt_at' => null, 'roadstar_sync_error' => null,
        ]);

        return ['ok' => true, 'message' => 'Hoarding mapped to RoadStar site ' . $siteId . '.'];
    }

    /**
     * Register the hoarding in RoadStar under $siteId, or the default id
     * (ROADSTAR_SITE_PREFIX + hoarding code), and store the mapping.
     *
     * Never creates a duplicate: RoadStar has no "list sites" endpoint, so the
     * id is first asked for with /sitedata. "Site not available" is the only
     * answer that leads to POST /addsite; any other answer means RoadStar
     * already has the site, and it is only mapped. The mapping is stored once
     * RoadStar confirms "Site added successfully".
     *
     * @return array{ok: bool, message: string, roadstar_site_id: ?string, http_status: ?int, added: bool}
     */
    public function register(int $mediaId, ?string $siteId = null, string $source = 'admin', ?int $triggeredBy = null): array
    {
        $media = $this->media($mediaId);
        if (!$media) {
            return $this->registerOutcome(false, 'Hoarding not found.');
        }
        if (!$this->validCoordinates($media->latitude, $media->longitude)) {
            return $this->registerOutcome(false, 'Hoarding has no valid latitude/longitude, so it cannot be registered in RoadStar.');
        }

        $siteId = trim((string) ($siteId ?: $this->defaultSiteId($media)));
        if ($error = $this->siteIdError($siteId, $mediaId)) {
            return $this->registerOutcome(false, $error, $siteId);
        }

        $exists = $this->siteExists($siteId, $source, $mediaId, $triggeredBy);
        if ($exists['exists'] === null) {
            return $this->registerOutcome(false, 'Could not check RoadStar for ' . $siteId . ': ' . $exists['message'], $siteId, $exists['http_status']);
        }
        if ($exists['exists'] === true) {
            $this->update($mediaId, [
                'roadstar_site_id' => $siteId, 'roadstar_sync_status' => self::PENDING, 'roadstar_last_synced_at' => null,
                'roadstar_last_attempt_at' => null, 'roadstar_sync_error' => null,
            ]);
            return $this->registerOutcome(true, 'RoadStar already has site ' . $siteId . '; mapped without calling /addsite.', $siteId, $exists['http_status']);
        }

        $result = $this->client->using($source, $mediaId, $triggeredBy)
            ->addSite($siteId, (float) $media->latitude, (float) $media->longitude);

        if (!$result['ok']) {
            $this->update($mediaId, ['roadstar_last_attempt_at' => now(), 'roadstar_sync_error' => mb_substr((string) $result['error'], 0, 500)]);
            return $this->registerOutcome(false, (string) $result['error'], $siteId, $result['http_status']);
        }

        $this->update($mediaId, [
            'roadstar_site_id' => $siteId, 'roadstar_sync_status' => self::PENDING, 'roadstar_last_synced_at' => null,
            'roadstar_last_attempt_at' => null, 'roadstar_sync_error' => null,
        ]);

        return $this->registerOutcome(true, 'Registered in RoadStar as ' . $siteId . ' (POST /addsite).', $siteId, $result['http_status'], true);
    }

    /**
     * Does RoadStar know this site id? Asked with /sitedata (no side effects).
     *
     * @return array{exists: ?bool, message: ?string, http_status: ?int}
     *         exists null = could not tell (network, auth, 5xx, malformed)
     */
    public function siteExists(string $siteId, string $source = 'admin', ?int $mediaId = null, ?int $triggeredBy = null): array
    {
        $period = $this->period();
        $r = $this->client->using($source, $mediaId, $triggeredBy)
            ->siteData([['arr' => $siteId, 'st' => $period['start'], 'ed' => $period['end']]]);

        if (!$r['ok']) {
            return ['exists' => null, 'message' => $r['error'], 'http_status' => $r['http_status']];
        }
        if (!array_key_exists($siteId, $r['data'])) {
            return ['exists' => null, 'message' => 'RoadStar response did not include this site.', 'http_status' => $r['http_status']];
        }

        $state = RoadStarSiteDataParser::parse($r['data'][$siteId])['state'];

        return [
            'exists'      => match ($state) {
                RoadStarSiteDataParser::NOT_AVAILABLE => false,
                RoadStarSiteDataParser::INVALID       => null,
                default                               => true,   // data, no data for the period, or a period error
            },
            'message'     => null,
            'http_status' => $r['http_status'],
        ];
    }

    /**
     * ONE hoarding, end to end — the manual test and the "Sync RoadStar Data"
     * button:
     *   1. load the media record          4. register with /addsite only if
     *   2. check its latitude/longitude      RoadStar says "Site not available"
     *   3. resolve its RoadStar site id       and $register is true
     *      ($siteId, else the stored one,  5. POST /sitedata, parse, save
     *      else none → stop)               6. return the saved result
     *
     * @return array{ok: bool, status: string, message: ?string, steps: string[], roadstar: ?array}
     */
    public function syncOne(int $mediaId, ?string $siteId = null, bool $register = false, string $source = 'admin', ?int $triggeredBy = null): array
    {
        $steps = [];
        $media = $this->media($mediaId);
        if (!$media) {
            return $this->oneOutcome(false, 'not_found', 'Hoarding not found (or deleted).', $steps);
        }
        $steps[] = 'Loaded media #' . $mediaId . ($media->hoarding_code ? ' (' . $media->hoarding_code . ')' : '') . '.';

        $hasCoords = $this->validCoordinates($media->latitude, $media->longitude);
        $steps[] = $hasCoords
            ? 'Latitude/longitude ' . $media->latitude . ', ' . $media->longitude . ' are valid.'
            : 'Latitude/longitude missing or invalid.';

        $siteId = trim((string) ($siteId ?? '')) ?: (string) $media->roadstar_site_id;
        if ($siteId === '') {
            if (!$register) {
                return $this->oneOutcome(false, 'not_mapped',
                    'No RoadStar site id. Give an existing RoadStar site id, or allow registering it as ' . $this->defaultSiteId($media) . '.', $steps);
            }
            $siteId = $this->defaultSiteId($media);
        }

        if ($siteId !== $media->roadstar_site_id) {
            $mapped = $this->map($mediaId, $siteId);
            if (!$mapped['ok']) {
                return $this->oneOutcome(false, 'invalid_site_id', $mapped['message'], $steps);
            }
            $steps[] = $mapped['message'];
        } else {
            $steps[] = 'Already mapped to RoadStar site ' . $siteId . '.';
        }

        $result = $this->sync([$mediaId], $source, $triggeredBy);
        $outcome = $result['results'][$mediaId] ?? ['status' => self::FAILED, 'message' => $result['message']];
        $steps[] = 'POST /sitedata ' . $result['period']['start'] . ' → ' . $result['period']['end'] . ': ' . $outcome['status'] . '.';

        if ($outcome['status'] === self::NOT_AVAILABLE) {
            if (!$register) {
                return $this->oneOutcome(false, self::NOT_AVAILABLE,
                    'RoadStar does not have site ' . $siteId . '. Allow registering to add it with /addsite.', $steps, $this->forDisplay($mediaId));
            }
            if (!$hasCoords) {
                return $this->oneOutcome(false, 'missing_location', 'Site is not registered in RoadStar and this hoarding has no valid latitude/longitude to register it with.', $steps, $this->forDisplay($mediaId));
            }

            $added = $this->client->using($source, $mediaId, $triggeredBy)
                ->addSite($siteId, (float) $media->latitude, (float) $media->longitude);
            if (!$added['ok']) {
                $this->update($mediaId, ['roadstar_sync_error' => mb_substr((string) $added['error'], 0, 500)]);
                return $this->oneOutcome(false, 'register_failed', (string) $added['error'], $steps, $this->forDisplay($mediaId));
            }
            $steps[] = 'POST /addsite: ' . ($added['data']['message'] ?? 'site added') . '.';

            $result = $this->sync([$mediaId], $source, $triggeredBy);
            $outcome = $result['results'][$mediaId] ?? ['status' => self::FAILED, 'message' => $result['message']];
            $steps[] = 'POST /sitedata again: ' . $outcome['status'] . '.';
        }

        return $this->oneOutcome($outcome['status'] === self::SUCCESS, $outcome['status'], $outcome['message'], $steps, $this->forDisplay($mediaId));
    }

    /** ROADSTAR_SITE_PREFIX + hoarding code (HD000034), or + "M<id>" without one. */
    public function defaultSiteId(object $media): string
    {
        $code = trim((string) ($media->hoarding_code ?? ''));

        return (string) config('services.roadstar.site_prefix', 'BI/') . ($code !== '' ? $code : 'M' . $media->id);
    }

    /* ============================ SYNC (calls RoadStar) ============================ */

    /**
     * Fetch and store audience for these hoardings in ONE /sitedata request.
     * Callers keep batches at ROADSTAR_BATCH_SIZE.
     *
     * @param int[] $mediaIds
     * @return array{
     *   ok: bool, retryable: bool, retry_after: ?int, error_type: ?string, message: ?string,
     *   http_status: ?int, period: array{start: string, end: string},
     *   results: array<int, array{status: string, message: ?string}>
     * }
     *   ok=false means the request as a whole failed (network, auth, 5xx, 429…);
     *   per-hoarding outcomes are in results either way.
     */
    public function sync(array $mediaIds, string $source = 'admin', ?int $triggeredBy = null): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $mediaIds), fn($id) => $id > 0)));
        $period = $this->period();
        $summary = [
            'ok' => true, 'retryable' => false, 'retry_after' => null, 'error_type' => null,
            'message' => null, 'http_status' => null, 'period' => $period, 'results' => [],
        ];

        $locks = [];
        foreach ($ids as $id) {
            $lock = Cache::lock('roadstar-sync-media-' . $id, self::LOCK_SECONDS);
            if ($lock->get()) {
                $locks[$id] = $lock;
            } else {
                $summary['results'][$id] = ['status' => 'busy', 'message' => 'A RoadStar sync for this hoarding is already running.'];
            }
        }

        try {
            $rows = $locks
                ? DB::table('media_management')->whereIn('id', array_keys($locks))->where('is_deleted', 0)
                    ->get(['id', 'roadstar_site_id'])->keyBy('id')
                : collect();

            $batch = [];
            foreach (array_keys($locks) as $id) {
                $row = $rows->get($id);
                if (!$row) {
                    $summary['results'][$id] = ['status' => 'not_found', 'message' => 'Hoarding not found.'];
                } elseif (trim((string) $row->roadstar_site_id) === '') {
                    $summary['results'][$id] = ['status' => 'not_mapped', 'message' => 'Hoarding is not mapped to a RoadStar site.'];
                } else {
                    $batch[$id] = trim((string) $row->roadstar_site_id);
                }
            }

            if (!$batch) {
                return $summary;
            }

            $response = $this->client->using($source, count($batch) === 1 ? array_key_first($batch) : null, $triggeredBy)
                ->siteData(array_map(fn($site) => ['arr' => $site, 'st' => $period['start'], 'ed' => $period['end']], array_values($batch)));

            $summary['http_status'] = $response['http_status'];
            $now = now();

            if (!$response['ok']) {
                $summary['ok'] = false;
                $summary['retryable'] = in_array($response['error_type'], RoadStarService::RETRYABLE, true);
                $summary['retry_after'] = $response['retry_after'];
                $summary['error_type'] = $response['error_type'];
                $summary['message'] = $response['error'];

                DB::table('media_management')->whereIn('id', array_keys($batch))->update([
                    'roadstar_sync_status'     => self::FAILED,
                    'roadstar_last_attempt_at' => $now,
                    'roadstar_sync_error'      => mb_substr((string) $response['error'], 0, 500),
                ]);
                foreach (array_keys($batch) as $id) {
                    $summary['results'][$id] = ['status' => self::FAILED, 'message' => $response['error']];
                }

                return $summary;
            }

            foreach ($batch as $id => $siteId) {
                try {
                    $summary['results'][$id] = $this->store($id, $siteId, $response['data'], $period, $now);
                } catch (Throwable $e) {
                    // A database error for one hoarding; record it and carry on.
                    report($e);
                    $this->update($id, [
                        'roadstar_sync_status' => self::FAILED, 'roadstar_last_attempt_at' => $now,
                        'roadstar_sync_error' => 'Could not save RoadStar data.',
                    ]);
                    $summary['results'][$id] = ['status' => self::FAILED, 'message' => 'Could not save RoadStar data.'];
                }
            }

            return $summary;
        } finally {
            foreach ($locks as $lock) {
                $lock->release();
            }
        }
    }

    /**
     * One hoarding's /sitedata answer, parsed, without storing anything —
     * the admin "test" endpoint.
     */
    public function preview(int $mediaId, ?int $triggeredBy = null): array
    {
        $media = $this->media($mediaId);
        $period = $this->period();

        if (!$media) {
            return ['ok' => false, 'status' => 'not_found', 'message' => 'Hoarding not found.', 'period' => $period];
        }
        $siteId = trim((string) $media->roadstar_site_id);
        if ($siteId === '') {
            return ['ok' => false, 'status' => 'not_mapped', 'message' => 'Hoarding is not mapped to a RoadStar site.',
                'media_id' => $mediaId, 'period' => $period];
        }

        $response = $this->client->using('admin', $mediaId, $triggeredBy)
            ->siteData([['arr' => $siteId, 'st' => $period['start'], 'ed' => $period['end']]]);

        $base = ['media_id' => $mediaId, 'roadstar_site_id' => $siteId, 'period' => $period, 'http_status' => $response['http_status']];

        if (!$response['ok']) {
            return $base + ['ok' => false, 'status' => self::FAILED, 'error_type' => $response['error_type'], 'message' => $response['error']];
        }
        if (!array_key_exists($siteId, $response['data'])) {
            return $base + ['ok' => false, 'status' => self::FAILED, 'message' => 'RoadStar response did not include this site.'];
        }

        $parsed = RoadStarSiteDataParser::parse($response['data'][$siteId]);

        return $base + [
            'ok'      => $parsed['state'] === RoadStarSiteDataParser::DATA,
            'status'  => $parsed['state'],
            'message' => $parsed['message'],
            'metrics' => $parsed['metrics'],
        ];
    }

    /* ============================ SCHEDULING ============================ */

    /**
     * Mapped, active hoardings due a sync: never synced, or last synced more
     * than ROADSTAR_REFRESH_DAYS ago — and not attempted/queued within the
     * last ROADSTAR_RETRY_HOURS. Page through it with chunkById('id').
     */
    public function dueQuery()
    {
        $now = now();

        return DB::table('media_management')
            ->where('is_deleted', 0)
            ->where('is_active', 1)
            ->whereNotNull('roadstar_site_id')
            ->where('roadstar_site_id', '!=', '')
            ->where(function ($q) use ($now) {
                $q->whereNull('roadstar_last_synced_at')
                    ->orWhere('roadstar_last_synced_at', '<=', $now->copy()->subDays($this->refreshDays()));
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('roadstar_last_attempt_at')
                    ->orWhere('roadstar_last_attempt_at', '<=', $now->copy()->subHours($this->retryHours()));
            })
            ->select('id');
    }

    public function dueCount(): int
    {
        return $this->dueQuery()->count('id');
    }

    /** Mark hoardings handed to the queue, so the next run does not queue them again. */
    public function markQueued(array $mediaIds): void
    {
        if ($mediaIds) {
            DB::table('media_management')->whereIn('id', $mediaIds)->update([
                'roadstar_sync_status'     => self::QUEUED,
                'roadstar_last_attempt_at' => now(),
            ]);
        }
    }

    /** A job that gave up: anything it left "queued" is marked failed. */
    public function markFailed(array $mediaIds, string $message): void
    {
        if ($mediaIds) {
            DB::table('media_management')->whereIn('id', $mediaIds)
                ->where('roadstar_sync_status', self::QUEUED)
                ->update([
                    'roadstar_sync_status'     => self::FAILED,
                    'roadstar_last_attempt_at' => now(),
                    'roadstar_sync_error'      => mb_substr($message, 0, 500),
                ]);
        }
    }

    /**
     * The audience period sent as st/ed: ROADSTAR_PERIOD_START/END when both
     * are set, otherwise the last ROADSTAR_PERIOD_DAYS days ending
     * ROADSTAR_PERIOD_END_OFFSET_DAYS before today.
     *
     * @return array{start: string, end: string}
     */
    public function period(): array
    {
        $start = $this->date(config('services.roadstar.period_start'));
        $end = $this->date(config('services.roadstar.period_end'));
        if ($start && $end && $start <= $end) {
            return ['start' => $start, 'end' => $end];
        }

        $endDate = Carbon::today()->subDays(max(0, (int) config('services.roadstar.period_end_offset_days', 1)));
        $days = max(1, (int) config('services.roadstar.period_days', 30));

        return ['start' => $endDate->copy()->subDays($days - 1)->toDateString(), 'end' => $endDate->toDateString()];
    }

    /** ROADSTAR_BULK_SYNC_ENABLED — off until single-media sync is confirmed. */
    public function bulkEnabled(): bool
    {
        return (bool) config('services.roadstar.bulk_enabled', false);
    }

    public function batchSize(): int
    {
        return max(1, (int) config('services.roadstar.batch_size', 20));
    }

    public function requestDelayMs(): int
    {
        return max(0, (int) config('services.roadstar.request_delay_ms', 1000));
    }

    public function maxPerRun(): int
    {
        return max(1, (int) config('services.roadstar.max_per_run', 2000));
    }

    /* ============================ READ (database only) ============================ */

    /**
     * RoadStar mapping, sync status and latest audience for one hoarding.
     * Never calls RoadStar; leaves out raw_response.
     */
    public function forDisplay(int $mediaId): array
    {
        $media = DB::table('media_management')->where('id', $mediaId)
            ->first(['id', 'hoarding_code', 'latitude', 'longitude', 'roadstar_site_id', 'roadstar_sync_status',
                'roadstar_last_synced_at', 'roadstar_last_attempt_at', 'roadstar_sync_error']);

        if (!$media) {
            return ['found' => false];
        }

        $latest = MediaManagement::find($mediaId)?->latestRoadStarAudience;

        return [
            'found'            => true,
            'configured'       => $this->isConfigured(),
            'media_id'         => (int) $media->id,
            'roadstar_site_id' => $media->roadstar_site_id,
            'suggested_site_id'=> $this->defaultSiteId($media),
            'can_register'     => $this->validCoordinates($media->latitude, $media->longitude),
            'sync_status'      => $media->roadstar_sync_status,
            'last_synced_at'   => $media->roadstar_last_synced_at ? Carbon::parse($media->roadstar_last_synced_at)->toDateTimeString() : null,
            'last_attempt_at'  => $media->roadstar_last_attempt_at ? Carbon::parse($media->roadstar_last_attempt_at)->toDateTimeString() : null,
            'next_sync_due_at' => $media->roadstar_last_synced_at
                ? Carbon::parse($media->roadstar_last_synced_at)->addDays($this->refreshDays())->toDateTimeString() : null,
            'sync_error'       => $media->roadstar_sync_error,
            'period'           => $this->period(),
            'audience'         => $latest ? [
                'roadstar_site_id' => $latest->roadstar_site_id,
                'period_start'     => $latest->period_start?->toDateString(),
                'period_end'       => $latest->period_end?->toDateString(),
                'unique_reach'     => $latest->unique_reach,
                'impressions'      => $latest->impressions,
                'frequency'        => $latest->frequency !== null ? (float) $latest->frequency : null,
                'fetched_at'       => $latest->fetched_at?->toDateTimeString(),
            ] + array_combine(
                array_values(RoadStarSiteDataParser::BREAKDOWNS),
                array_map(fn($column) => $latest->{$column}, array_values(RoadStarSiteDataParser::BREAKDOWNS))
            ) : null,
        ];
    }

    /* ============================ HELPERS ============================ */

    /** Store one site's answer. @return array{status: string, message: ?string} */
    private function store(int $mediaId, string $siteId, array $bySite, array $period, Carbon $now): array
    {
        if (!array_key_exists($siteId, $bySite)) {
            $message = 'RoadStar response did not include this site.';
            $this->update($mediaId, ['roadstar_sync_status' => self::FAILED, 'roadstar_last_attempt_at' => $now, 'roadstar_sync_error' => $message]);
            Log::warning('RoadStar site missing from response', ['media_id' => $mediaId, 'roadstar_site_id' => $siteId]);
            return ['status' => self::FAILED, 'message' => $message];
        }

        $parsed = RoadStarSiteDataParser::parse($bySite[$siteId]);

        switch ($parsed['state']) {
            case RoadStarSiteDataParser::DATA:
                $values = $parsed['metrics'] + [
                    'roadstar_site_id' => $siteId,
                    'raw_response'     => $parsed['raw'],
                    'fetched_at'       => $now,
                ];
                DB::transaction(function () use ($mediaId, $period, $values, $now) {
                    $existing = RoadStarAudienceData::where('media_id', $mediaId)
                        ->whereDate('period_start', $period['start'])
                        ->whereDate('period_end', $period['end'])
                        ->lockForUpdate()
                        ->first();

                    if ($existing) {
                        $existing->fill($values)->save();
                    } else {
                        RoadStarAudienceData::create($values + [
                            'media_id' => $mediaId, 'period_start' => $period['start'], 'period_end' => $period['end'],
                        ]);
                    }

                    $this->update($mediaId, [
                        'roadstar_sync_status' => self::SUCCESS, 'roadstar_last_synced_at' => $now,
                        'roadstar_last_attempt_at' => $now, 'roadstar_sync_error' => null,
                    ]);
                });
                return ['status' => self::SUCCESS, 'message' => 'Audience data updated.'];

            case RoadStarSiteDataParser::NO_DATA:
            case RoadStarSiteDataParser::NOT_AVAILABLE:
                // A definitive answer: counts as synced, asked again in 15 days
                // (registering or re-mapping the site makes it due at once).
                $status = $parsed['state'] === RoadStarSiteDataParser::NO_DATA ? self::NO_DATA : self::NOT_AVAILABLE;
                $this->update($mediaId, [
                    'roadstar_sync_status' => $status, 'roadstar_last_synced_at' => $now,
                    'roadstar_last_attempt_at' => $now, 'roadstar_sync_error' => mb_substr((string) $parsed['message'], 0, 500),
                ]);
                return ['status' => $status, 'message' => $parsed['message']];

            default:
                // Rejected (e.g. period outside RoadStar's data range) or malformed.
                $this->update($mediaId, [
                    'roadstar_sync_status' => self::FAILED, 'roadstar_last_attempt_at' => $now,
                    'roadstar_sync_error' => mb_substr((string) $parsed['message'], 0, 500),
                ]);
                Log::warning('RoadStar site data not usable', [
                    'media_id' => $mediaId, 'roadstar_site_id' => $siteId, 'state' => $parsed['state'], 'message' => $parsed['message'],
                ]);
                return ['status' => self::FAILED, 'message' => $parsed['message']];
        }
    }

    private function siteIdError(string $siteId, int $mediaId): ?string
    {
        if (!preg_match(self::SITE_ID_PATTERN, $siteId)) {
            return 'RoadStar site id may contain letters, digits, / _ . - only (max 100 characters).';
        }

        $taken = DB::table('media_management')->where('roadstar_site_id', $siteId)->where('id', '!=', $mediaId)->value('id');

        return $taken ? 'RoadStar site ' . $siteId . ' is already mapped to another hoarding (media #' . $taken . ').' : null;
    }

    private function media(int $mediaId): ?object
    {
        return DB::table('media_management')->where('id', $mediaId)->where('is_deleted', 0)
            ->first(['id', 'hoarding_code', 'latitude', 'longitude', 'roadstar_site_id']);
    }

    /** Query-builder update: RoadStar bookkeeping must not touch updated_at. */
    private function update(int $mediaId, array $values): void
    {
        DB::table('media_management')->where('id', $mediaId)->update($values);
    }

    private function validCoordinates($lat, $lng): bool
    {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return false;
        }
        $lat = (float) $lat;
        $lng = (float) $lng;

        return !($lat == 0 && $lng == 0) && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }

    private function date($value): ?string
    {
        $value = trim((string) $value);
        $parsed = $value !== '' ? \DateTime::createFromFormat('!Y-m-d', $value) : false;

        return $parsed && $parsed->format('Y-m-d') === $value ? $value : null;
    }

    private function refreshDays(): int
    {
        return max(1, (int) config('services.roadstar.refresh_days', 15));
    }

    private function retryHours(): int
    {
        return max(1, (int) config('services.roadstar.retry_hours', 24));
    }

    private function registerOutcome(bool $ok, string $message, ?string $siteId = null, ?int $http = null, bool $added = false): array
    {
        return ['ok' => $ok, 'message' => $message, 'roadstar_site_id' => $siteId, 'http_status' => $http, 'added' => $added];
    }

    private function oneOutcome(bool $ok, string $status, ?string $message, array $steps, ?array $roadstar = null): array
    {
        return ['ok' => $ok, 'status' => $status, 'message' => $message, 'steps' => $steps, 'roadstar' => $roadstar];
    }
}

<?php

namespace App\Console\Commands;

use App\Http\Services\RoadStar\RoadStarService;
use App\Http\Services\RoadStar\RoadStarSyncService;
use App\Jobs\SyncRoadStarSites;
use Illuminate\Console\Command;
use Throwable;

/**
 * Queue RoadStar audience syncs for mapped hoardings that are due.
 *
 * "Due" = has a roadstar_site_id, and roadstar_last_synced_at is empty or
 * older than ROADSTAR_REFRESH_DAYS (15), and nothing was attempted/queued for
 * it in the last ROADSTAR_RETRY_HOURS — see RoadStarSyncService::dueQuery().
 * Hoardings that are not due cost nothing.
 *
 * Hoardings are read ROADSTAR_BATCH_SIZE at a time and each batch becomes one
 * SyncRoadStarSites job (= one POST /sitedata). At most ROADSTAR_MAX_PER_RUN
 * are queued per run, so a first load of ~10,000 spreads over several runs.
 * Runs from the scheduler (routes/console.php); safe to run by hand.
 */
class SyncRoadStarData extends Command
{
    protected $signature = 'roadstar:sync
                            {--media=* : Sync this media id now, in this process, due or not (single-media test)}
                            {--site-id= : With one --media: the RoadStar site id to map it to first}
                            {--register : With --media: if RoadStar says "Site not available", add it with /addsite}
                            {--limit= : Most hoardings to queue this run (default ROADSTAR_MAX_PER_RUN)}
                            {--sync : Process in this process instead of queueing (no worker needed)}
                            {--dry-run : Only report how many are due}';

    protected $description = 'Fetch RoadStar audience data for mapped hoardings due a refresh (every 15 days)';

    public function handle(RoadStarSyncService $roadStar): int
    {
        if (!$roadStar->isConfigured()) {
            $this->warn('RoadStar is not configured (ROADSTAR_BASE_URL / ROADSTAR_USERNAME / ROADSTAR_PASSWORD); nothing synced.');
            return self::SUCCESS;
        }

        $period = $roadStar->period();

        if ($this->option('media')) {
            return $this->syncNow($roadStar, array_map('intval', (array) $this->option('media')));
        }

        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : $roadStar->maxPerRun();
        $due = $roadStar->dueCount();

        $this->line(sprintf('RoadStar: %d mapped hoarding(s) due, period %s → %s, taking up to %d in batches of %d.',
            $due, $period['start'], $period['end'], $limit, $roadStar->batchSize()));

        if ($this->option('dry-run') || $due === 0 || $limit === 0) {
            return self::SUCCESS;
        }
        if (!$roadStar->bulkEnabled()) {
            $this->warn('Bulk sync is off (ROADSTAR_BULK_SYNC_ENABLED=false); nothing queued. '
                . 'Test one hoarding with: php artisan roadstar:sync --media=<id>');
            return self::SUCCESS;
        }

        return $this->option('sync')
            ? $this->runInProcess($roadStar, $limit)
            : $this->queue($roadStar, $limit);
    }

    private function queue(RoadStarSyncService $roadStar, int $limit): int
    {
        $taken = 0;
        $jobs = 0;

        $roadStar->dueQuery()->chunkById($roadStar->batchSize(), function ($rows) use ($roadStar, $limit, &$taken, &$jobs) {
            $ids = $rows->pluck('id')->map(fn($id) => (int) $id)->take($limit - $taken)->all();
            if (!$ids) {
                return false;
            }

            $roadStar->markQueued($ids);
            SyncRoadStarSites::dispatch($ids, 'scheduler');

            $taken += count($ids);
            $jobs++;

            return $taken < $limit;
        }, 'id');

        $this->info("Queued {$taken} hoarding(s) in {$jobs} job(s) on the \"" . config('services.roadstar.queue', 'roadstar') . '" queue.');

        return self::SUCCESS;
    }

    private function runInProcess(RoadStarSyncService $roadStar, int $limit): int
    {
        $delayUs = $roadStar->requestDelayMs() * 1000;
        $done = 0;
        $counts = [];
        $stopped = null;

        $roadStar->dueQuery()->chunkById($roadStar->batchSize(), function ($rows) use ($roadStar, $limit, $delayUs, &$done, &$counts, &$stopped) {
            $ids = $rows->pluck('id')->map(fn($id) => (int) $id)->take($limit - $done)->all();
            if (!$ids) {
                return false;
            }
            if ($done > 0 && $delayUs > 0) {
                usleep($delayUs);
            }

            try {
                $r = $roadStar->sync($ids, 'console');
            } catch (Throwable $e) {
                // sync() handles API errors itself; this is a code/DB error.
                // Record it for this batch and carry on with the next one.
                report($e);
                $counts['failed'] = ($counts['failed'] ?? 0) + count($ids);
                $done += count($ids);
                return $done < $limit;
            }

            foreach ($r['results'] as $id => $outcome) {
                $counts[$outcome['status']] = ($counts[$outcome['status']] ?? 0) + 1;
                if ($this->output->isVerbose()) {
                    $this->line("  #{$id}: {$outcome['status']}" . ($outcome['status'] !== RoadStarSyncService::SUCCESS && $outcome['message'] ? " — {$outcome['message']}" : ''));
                }
            }
            $done += count($ids);

            // Nothing else will get through either: stop rather than hammer.
            if (!$r['ok'] && in_array($r['error_type'], [RoadStarService::ERR_UNAUTHORIZED, RoadStarService::ERR_FORBIDDEN, RoadStarService::ERR_RATE_LIMITED, RoadStarService::ERR_CONNECTION, RoadStarService::ERR_NOT_FOUND], true)) {
                $stopped = $r['message'];
                return false;
            }

            return $done < $limit;
        }, 'id');

        $this->info('Done: ' . ($counts ? collect($counts)->map(fn($n, $s) => "{$n} {$s}")->implode(', ') : 'nothing processed') . '.');
        if ($stopped) {
            $this->warn('Stopped early: ' . $stopped);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * --media: the single-media flow (RoadStarSyncService::syncOne) for each
     * id given, one at a time, printing every step and the saved result.
     */
    private function syncNow(RoadStarSyncService $roadStar, array $ids): int
    {
        $ids = array_values(array_unique(array_filter($ids, fn($id) => $id > 0)));
        if (!$ids) {
            $this->error('Give a numeric media id: --media=101');
            return self::INVALID;
        }
        if ($this->option('site-id') !== null && count($ids) > 1) {
            $this->error('--site-id can only be used with one --media id.');
            return self::INVALID;
        }

        $failed = false;
        foreach ($ids as $id) {
            $r = $roadStar->syncOne($id, $this->option('site-id'), (bool) $this->option('register'), 'console');

            $this->line("Media #{$id}");
            foreach ($r['steps'] as $step) {
                $this->line('  • ' . $step);
            }
            $r['ok'] ? $this->info("  Result: {$r['status']}") : $this->warn("  Result: {$r['status']} — {$r['message']}");

            if ($audience = $r['roadstar']['audience'] ?? null) {
                $this->table(['site', 'period', 'unique reach', 'impressions', 'frequency', 'fetched'], [[
                    $audience['roadstar_site_id'], $audience['period_start'] . ' → ' . $audience['period_end'],
                    number_format($audience['unique_reach']), number_format($audience['impressions']),
                    $audience['frequency'], $audience['fetched_at'],
                ]]);
            }
            $failed = $failed || !$r['ok'];
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}

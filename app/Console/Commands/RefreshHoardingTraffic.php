<?php

namespace App\Console\Commands;

use App\Http\Services\Insights\HoardingTrafficService;
use App\Models\HoardingTrafficData;
use Illuminate\Console\Command;
use Throwable;

/**
 * Fetch TomTom Traffic Flow for hoardings that are due, and store it.
 *
 * "Due" = no traffic row yet (initial load), next_refresh_at passed (15-day
 * refresh / failed-attempt retry) or moved — see
 * HoardingTrafficService::dueQuery(). Hoardings that are not due cost
 * nothing. Runs hourly from the scheduler (routes/console.php); safe to run
 * by hand.
 *
 * One request at a time with TOMTOM_REQUEST_DELAY_MS between them, hoardings
 * read TOMTOM_BATCH_SIZE at a time, and never more than the day's remaining
 * TOMTOM_TRAFFIC_DAILY_LIMIT. One hoarding failing does not stop the run;
 * a 429 (quota / rate limit) does.
 */
class RefreshHoardingTraffic extends Command
{
    protected $signature = 'refresh:hoarding-traffic
                            {--limit= : Most hoardings to fetch this run (default: what is left of today\'s limit)}
                            {--media= : Fetch this one media id now, due or not}
                            {--dry-run : Only report how many are due}';

    protected $description = 'Fetch TomTom Traffic Flow for hoardings due a refresh (initial load + every 15 days)';

    public function handle(HoardingTrafficService $traffic): int
    {
        if (!$traffic->isConfigured()) {
            $this->warn('TomTom API key is not configured (TOMTOM_API_KEY); nothing fetched.');
            return self::SUCCESS;
        }

        if ($this->option('media')) {
            return $this->single($traffic, (int) $this->option('media'));
        }

        $remaining = $traffic->remainingToday();
        $take = $this->option('limit') !== null ? min((int) $this->option('limit'), $remaining) : $remaining;
        $due = $traffic->dueCount();

        $this->line(sprintf('TomTom traffic: %d/%d requests used today, %d due, fetching up to %d.',
            $traffic->usedToday(), $traffic->dailyLimit(), $due, max(0, $take)));

        if ($this->option('dry-run') || $due === 0) {
            return self::SUCCESS;
        }
        if ($take <= 0) {
            $this->warn('Daily request limit reached; the rest will be fetched on a later run.');
            return self::SUCCESS;
        }

        $delayUs = $traffic->requestDelayMs() * 1000;
        $done = $ok = $failed = 0;
        $stopped = false;

        $traffic->dueQuery()->chunkById($traffic->batchSize(), function ($rows) use ($traffic, $take, $delayUs, &$done, &$ok, &$failed, &$stopped) {
            foreach ($rows as $row) {
                if ($done >= $take) {
                    return false;
                }
                if ($done > 0 && $delayUs > 0) {
                    usleep($delayUs);
                }

                try {
                    $r = $traffic->fetchAndStore((int) $row->id);
                } catch (Throwable $e) {
                    // fetchAndStore handles API errors itself; this is a DB/code
                    // error. Record it against this hoarding and move on.
                    $r = ['status' => HoardingTrafficData::FAILED, 'message' => 'Unexpected error.', 'http_status' => null];
                    report($e);
                }

                if (in_array($r['status'], ['not_found', 'invalid_location'], true)) {
                    continue;
                }

                $done++;
                $r['status'] === HoardingTrafficData::SUCCESS ? $ok++ : $failed++;

                if ($this->output->isVerbose()) {
                    $this->line("  #{$row->id}: {$r['status']}" . ($r['status'] !== HoardingTrafficData::SUCCESS ? " — {$r['message']}" : ''));
                }

                if ($r['http_status'] === 429) {
                    $this->warn('TomTom returned 429 (quota or rate limit); stopping this run.');
                    $stopped = true;
                    return false;
                }
            }
        }, 'm.id', 'id');

        $this->info("Done: {$ok} updated, {$failed} failed" . ($stopped ? ' (stopped early).' : '.'));

        return self::SUCCESS;
    }

    private function single(HoardingTrafficService $traffic, int $mediaId): int
    {
        $r = $traffic->fetchAndStore($mediaId);
        $this->line("#{$mediaId}: {$r['status']} — {$r['message']}");

        if ($r['record'] && $r['status'] === HoardingTrafficData::SUCCESS) {
            $t = $r['record'];
            $this->table(['frc', 'current km/h', 'free-flow km/h', 'current s', 'free-flow s', 'confidence', 'closed', 'next refresh'], [[
                $t->frc, $t->current_speed, $t->free_flow_speed, $t->current_travel_time,
                $t->free_flow_travel_time, $t->confidence, $t->road_closure ? 'yes' : 'no', $t->next_refresh_at,
            ]]);
        }

        return $r['status'] === HoardingTrafficData::SUCCESS ? self::SUCCESS : self::FAILURE;
    }
}

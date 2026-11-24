<?php

namespace App\Console\Commands;

use App\Http\Services\Insights\MediaInsightsService;
use App\Jobs\EnrichMediaInsightsJob;
use Illuminate\Console\Command;

/**
 * Queue hoardings that are due a nearby-places refresh.
 *
 * "Due" = never fetched, moved (coordinates changed), provider switched, or
 * past its cache expiry — see MediaInsightsService::dueForRefresh(). Runs
 * from the scheduler (routes/console.php); safe to run by hand.
 *
 * It never queues more than the remaining credit budget can pay for, even
 * assuming every hoarding needs a fresh request (in practice neighbours share
 * a saved location and cost nothing). Each job re-checks the budget under a
 * lock before spending, so the budget holds even if this over-estimates.
 */
class EnrichMediaInsights extends Command
{
    protected $signature = 'insights:enrich
                            {--limit= : Most hoardings to queue this run (default GEOAPIFY_BATCH_SIZE)}
                            {--sync : Process in this process instead of queueing (small runs / no worker)}
                            {--dry-run : Only report what would be queued}';

    protected $description = 'Queue nearby-places refresh for hoardings that are due, within the API credit budget';

    public function handle(MediaInsightsService $insights): int
    {
        $quota = $insights->quota();

        if (!$quota['configured']) {
            $this->warn(ucfirst($quota['provider']) . ' API key is not configured; nothing queued.');
            return self::SUCCESS;
        }
        if ($quota['paused']) {
            $this->warn(ucfirst($quota['provider']) . ' is paused after a 429 from the provider; try again later.');
            return self::SUCCESS;
        }

        $batch = (int) ($this->option('limit') ?: config('services.geoapify.batch_size', 500));
        // Worst case every one of them costs max_cost credits.
        $affordable = intdiv($quota['remaining'], max(1, $quota['max_cost']));
        $take = max(0, min($batch, $affordable));

        $this->line(sprintf('%s: %d/%d credits used this %s, %d remaining (≤%d per request).',
            ucfirst($quota['provider']), $quota['used'], $quota['limit'], $quota['period'],
            $quota['remaining'], $quota['max_cost']));

        if ($take === 0) {
            $this->warn('Credit budget exhausted for this period; nothing queued.');
            return self::SUCCESS;
        }

        $ids = $insights->dueForRefresh($take);
        $this->info(count($ids) . ' hoarding(s) due; ' . ($this->option('dry-run') ? 'dry run, nothing queued.' : 'processing.'));

        if ($this->option('dry-run') || !$ids) {
            return self::SUCCESS;
        }

        foreach ($ids as $id) {
            if ($this->option('sync')) {
                $r = $insights->refresh($id, null, 'scheduler');
                $this->line("  #{$id}: {$r['status']}");
                if ($r['status'] === 'quota_blocked') {
                    $this->warn('Budget reached; stopping.');
                    break;
                }
            } else {
                EnrichMediaInsightsJob::dispatch($id);
            }
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Jobs;

use App\Http\Services\RoadStar\RoadStarService;
use App\Http\Services\RoadStar\RoadStarSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Fetch RoadStar audience for one batch of hoardings (one POST /sitedata).
 *
 * Queued by `roadstar:sync` on the ROADSTAR_QUEUE queue ("roadstar"), at most
 * ROADSTAR_BATCH_SIZE hoardings per job.
 *  - Unique per batch while queued; RoadStarSyncService also locks each
 *    hoarding while it is fetched, so overlapping batches cannot double-fetch.
 *  - RateLimited('roadstar-api') holds workers to ROADSTAR_REQUESTS_PER_MINUTE
 *    (see AppServiceProvider); an over-rate job is released, not failed.
 *  - Network errors, 5xx, malformed responses: retried with growing back-off.
 *  - 429: released after RoadStar's Retry-After when it sends one.
 *  - 401/403/404/400: retrying cannot help — the job fails straight away.
 *  - Per-site answers (no data, site not registered) are results, not errors.
 */
class SyncRoadStarSites implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 180;
    /** Seconds before attempts 2–5. */
    public array $backoff = [60, 300, 900, 1800];
    /** Uniqueness lock lifetime, in case a worker dies mid-job. */
    public int $uniqueFor = 3600;

    /** @param int[] $mediaIds */
    public function __construct(public array $mediaIds, public string $source = 'queue')
    {
        $this->mediaIds = array_values(array_unique(array_map('intval', $mediaIds)));
        sort($this->mediaIds);
        $this->onQueue((string) config('services.roadstar.queue', 'roadstar'));
    }

    public function uniqueId(): string
    {
        return 'roadstar-sync-' . md5(implode(',', $this->mediaIds));
    }

    public function middleware(): array
    {
        return [new RateLimited('roadstar-api')];
    }

    public function handle(RoadStarSyncService $roadStar): void
    {
        $result = $roadStar->sync($this->mediaIds, $this->source);

        if ($result['ok']) {
            return;
        }

        if ($result['error_type'] === RoadStarService::ERR_RATE_LIMITED && $result['retry_after']) {
            $this->release($result['retry_after']);
            return;
        }

        $message = 'RoadStar sync of ' . count($this->mediaIds) . ' hoarding(s): ' . $result['message'];

        if ($result['retryable']) {
            // Back-off from $backoff, then failed() once $tries is used up.
            throw new RuntimeException($message);
        }

        // Credentials, a missing endpoint, a rejected request: no retry.
        $this->fail(new RuntimeException($message));
    }

    public function failed(?Throwable $e): void
    {
        $message = $e ? $e->getMessage() : 'RoadStar sync job failed.';
        Log::warning('SyncRoadStarSites gave up', ['media_ids' => $this->mediaIds, 'message' => $message]);

        try {
            app(RoadStarSyncService::class)->markFailed($this->mediaIds, $message);
        } catch (Throwable $inner) {
            report($inner);
        }
    }
}

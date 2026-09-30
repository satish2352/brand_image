<?php

namespace App\Jobs;

use App\Http\Services\Insights\MediaInsightsService;
use App\Models\ApiUsageLog;
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
 * Fetch nearby places (when due) and recalculate insights for one hoarding.
 *
 * Queued by the scheduled `insights:enrich` batch on the "insights" queue.
 *  - Unique per media while queued, so a hoarding is never in the queue twice.
 *  - RateLimited('places-api') holds workers to the provider's request rate
 *    (see AppServiceProvider); an over-rate job is released, not failed.
 *  - Budget blocks and cache hits finish the job — nothing to retry.
 *  - Temporary failures (timeouts, 5xx, 429) are retried up to $tries with
 *    growing back-off; MediaInsightsService also counts failed attempts so the
 *    batch leaves a repeatedly failing hoarding alone for a day.
 */
class EnrichMediaInsightsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    /** Seconds before the 2nd and 3rd attempt. */
    public array $backoff = [120, 900];
    /** Uniqueness lock lifetime, in case a worker dies mid-job. */
    public int $uniqueFor = 3600;

    public function __construct(public int $mediaId)
    {
        $this->onQueue('insights');
    }

    public function uniqueId(): string
    {
        return 'media-insights-' . $this->mediaId;
    }

    public function middleware(): array
    {
        return [new RateLimited('places-api')];
    }

    public function handle(MediaInsightsService $insights): void
    {
        $result = $insights->refresh($this->mediaId, null, 'scheduler');

        // Temporary provider trouble: let the queue retry with back-off.
        if (in_array($result['status'], [ApiUsageLog::FAILED, ApiUsageLog::QUOTA_EXCEEDED, 'busy'], true)) {
            throw new RuntimeException('Insights refresh for media ' . $this->mediaId . ': ' . $result['message']);
        }
    }

    public function failed(Throwable $e): void
    {
        Log::warning('EnrichMediaInsightsJob gave up', ['media_id' => $this->mediaId, 'message' => $e->getMessage()]);
    }
}

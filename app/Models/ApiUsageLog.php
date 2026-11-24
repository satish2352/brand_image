<?php

namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * One nearby-places API attempt. credits_consumed is what the provider bills
 * (Geoapify: 1 per 20 places returned; SerpApi: 1 per successful search) and
 * is 0 for anything that failed or was never sent.
 */
class ApiUsageLog extends Model
{
    use MassPrunable;

    protected $table = 'api_usage_logs';

    public const SUCCESS          = 'success';
    public const FAILED           = 'failed';
    public const CACHE_HIT        = 'cache_hit';
    public const QUOTA_BLOCKED    = 'quota_blocked';    // our own budget stopped it
    public const QUOTA_EXCEEDED   = 'quota_exceeded';   // the provider refused (429)
    public const NOT_CONFIGURED   = 'not_configured';
    public const MISSING_LOCATION = 'missing_location';

    protected $fillable = [
        'provider',
        'media_id',
        'query',
        'location_key',
        'status',
        'http_status',
        'credits_consumed',
        'result_count',
        'external_id',
        'error_message',
        'duration_ms',
        'source',
        'triggered_by',
        'requested_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
    ];

    /**
     * Rows older than INSIGHTS_LOG_RETENTION_DAYS (default 180) are deleted by
     * the daily `model:prune` schedule. Budgets only read the current day /
     * month, so old rows are history, not accounting.
     */
    public function prunable()
    {
        return static::where('requested_at', '<', now()->subDays(max(35, (int) config('services.insights.log_retention_days', 180))));
    }
}

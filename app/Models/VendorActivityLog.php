<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * Vendor Inventory module activity log (PRD 2.11.1).
 *
 * Retention: the last RETENTION_MONTHS months are kept. Older rows are deleted
 * by `php artisan model:prune` (scheduled daily in routes/console.php), and
 * every screen reads through scopeRetained() so nothing older is shown even
 * before the prune has run.
 */
class VendorActivityLog extends Model
{
    use MassPrunable;

    public const RETENTION_MONTHS = 6;

    public const UPDATED_AT = null;

    protected $fillable = [
        'vendor_id', 'media_id', 'order_id', 'actor_type', 'actor_id', 'actor_name',
        'action', 'media_code', 'details', 'created_at',
    ];

    protected $casts = [
        'details'    => 'array',
        'created_at' => 'datetime',
    ];

    public static function retentionStart(): \Carbon\Carbon
    {
        return now()->subMonths(self::RETENTION_MONTHS)->startOfDay();
    }

    /** Rows older than the retention window, for model:prune. */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', self::retentionStart());
    }

    /** Only rows inside the retention window. */
    public function scopeRetained(Builder $query): Builder
    {
        return $query->where('created_at', '>=', self::retentionStart());
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}

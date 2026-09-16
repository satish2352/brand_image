<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The search window a signed-in user gets, starting at authentication.
 *
 * At most one row per user carries status 'active' — the database enforces it
 * with a unique index on a generated column, so two tabs racing a login cannot
 * produce two windows. @see the create_portal_access_tables migration.
 */
class SearchSession extends Model
{
    protected $table = 'search_sessions';

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_EXPIRED   = 'expired';
    /** Ran its course and the user acted on it — reserved for reporting. */
    public const STATUS_COMPLETED = 'completed';
    /** Cut short by an admin rather than by the clock. */
    public const STATUS_REVOKED   = 'revoked';

    /** The one automatic window an account ever receives. */
    public const TYPE_SEARCH_TRIAL  = 'search_trial';
    /** Handed out by the team afterwards; the only way to get a second. */
    public const TYPE_ADMIN_GRANTED = 'admin_granted';

    protected $fillable = [
        'user_id',
        'type',
        'started_at',
        'expires_at',
        'status',
        'opened_by',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(WebsiteUser::class, 'user_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    public function remainingSeconds(): int
    {
        if ($this->expires_at === null) {
            return 0;
        }

        // Carbon 3 returns a float here; floor to whole seconds rather than
        // letting an implicit narrowing conversion do it.
        return (int) max(0, floor(now()->diffInSeconds($this->expires_at, false)));
    }
}

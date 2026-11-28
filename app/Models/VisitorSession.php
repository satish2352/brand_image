<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A free preview window, keyed by the token in the visitor's cookie.
 *
 * The visitor has not signed in, so there is no user to hang this off. The
 * cookie identifies the browser; this row is what actually expires, and it
 * expires against the server's clock.
 */
class VisitorSession extends Model
{
    protected $table = 'visitor_sessions';

    public const STATUS_ACTIVE  = 'active';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'visitor_token',
        'started_at',
        'expires_at',
        'status',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    /**
     * Whole seconds left, never negative.
     */
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

<?php

namespace App\Listeners;

use App\Http\Services\Website\SearchSessionService;
use App\Models\WebsiteUser;
use Illuminate\Auth\Events\Login;

/**
 * Opens a search window the moment a site user authenticates.
 *
 * Hung off the Login event rather than called from the controllers because
 * there are three ways in — password login, OTP registration and Google — and
 * a fourth added later would otherwise silently skip this.
 *
 * Idempotent: startSearchSession() returns any window already running, so
 * logging in again in a second tab shares the first one rather than buying
 * another five minutes.
 */
class StartSearchSessionOnLogin
{
    public function __construct(private SearchSessionService $sessions) {}

    public function handle(Login $event): void
    {
        // Only site users. The admin panel has its own guard and is not gated.
        if ($event->guard !== 'website' || !$event->user instanceof WebsiteUser) {
            return;
        }

        if (!$this->sessions->gateEnabled()) {
            return;
        }

        // Returns null when the account has already had its trial. Logging in
        // again, tomorrow or from another device, does not open a new one —
        // only an admin grant does.
        $this->sessions->startSearchSession((int) $event->user->getAuthIdentifier(), 'login');
    }
}

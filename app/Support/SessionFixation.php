<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Session-fixation protection for a login, without breaking the pages the
 * browser already has open.
 *
 * Every login must leave with a session id different from the one it arrived
 * on, or someone who planted an id in this browser would own the session the
 * moment it became authenticated.
 *
 * The obvious call for that is session()->regenerate(), and it is what this
 * replaces. regenerate() is migrate() plus regenerateToken() — it rotates the
 * CSRF token as well, and that second half is what hurt: @csrf stamps the
 * token into the HTML when a page is rendered, so rotating it turns every page
 * already sitting in another tab into a 419 on its next submit. Log in to the
 * admin panel in one tab and the registration form open in the next stops
 * working, with "CSRF token mismatch" as the only explanation offered.
 *
 * migrate(true) is the half that actually defends against fixation: the old
 * session row is destroyed and a new id issued, while the data — the CSRF
 * token among it — carries over. Nothing is weakened by keeping the token:
 * an attacker who could read it could read the session cookie beside it, and
 * that cookie is exactly what has just been replaced.
 *
 * Leaving one surface while the session carries on — signing out of the admin
 * panel with a customer still logged in beside it — wants the same treatment,
 * and calls this too. A logout that actually ends the session does not: that
 * one invalidates, and the genuinely new session it leaves behind should have
 * a new token.
 */
class SessionFixation
{
    public static function protect(Request $request): void
    {
        $request->session()->migrate(true);
    }
}

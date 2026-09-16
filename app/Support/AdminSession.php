<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Admin sign-in state, tracked per surface.
 *
 * One admin account can be signed in on two surfaces at once: the admin panel
 * behind /login, and "admin mode" on the public site (the Account Access
 * modal), which unlocks the shortlist ticks, the Share bar and the search
 * window. Both used to be the single session key `user_id` — one login
 * wearing two hats — so signing out of either took the other down with it, in
 * the same browser, in the middle of the other tab's work.
 *
 * Each surface now keeps its own keys. Signing in at either door seeds both,
 * so the existing convenience is unchanged: log in on the site and the Admin
 * Panel link works; log in at /login and the site knows you are staff. What
 * changes is the exit — a logout clears only the surface you left. Leaving
 * the panel does NOT drop admin mode on the site, and the reverse is just as
 * true, which is the point.
 *
 * The customer login (the `website` auth guard) lives in the same session and
 * is likewise none of a logout's business — see invalidateKeepingAdmin().
 */
class AdminSession
{
    /** Read by the SuperAdmin middleware and everything under the admin panel. */
    public const PANEL_KEYS = ['user_id', 'email', 'name'];

    /** Read by the public site: the header pill, shortlist, share, search window. */
    public const SITE_KEYS = ['site_admin_id', 'site_admin_email', 'site_admin_name'];

    /**
     * Open both surfaces for a verified admin.
     *
     * Both logins call this, so "what being signed in means" is written once
     * and the two doors cannot drift apart.
     */
    public static function login(Request $request, User $user): void
    {
        $request->session()->put([
            'user_id'          => $user->id,
            'email'            => $user->email,
            'name'             => $user->name,
            'site_admin_id'    => $user->id,
            'site_admin_email' => $user->email,
            'site_admin_name'  => $user->name,
        ]);
    }

    /** Leave the admin panel. Admin mode on the public site stays open. */
    public static function forgetPanel(Request $request): void
    {
        $request->session()->forget(self::PANEL_KEYS);
    }

    /** Leave admin mode on the public site. The admin panel stays open. */
    public static function forgetSite(Request $request): void
    {
        $request->session()->forget(self::SITE_KEYS);
    }

    /** Is this browser in admin mode on the public site? */
    public static function onSite(): bool
    {
        return session()->has('site_admin_id');
    }

    /** Is this browser signed in to the admin panel? */
    public static function onPanel(): bool
    {
        return session()->has('user_id');
    }

    /** The id behind site admin mode, or null — for stamping created_by and the like. */
    public static function siteId()
    {
        return session('site_admin_id');
    }

    public static function siteName()
    {
        return session('site_admin_name');
    }

    public static function siteEmail()
    {
        return session('site_admin_email');
    }

    /**
     * Wipe the session the way a customer logout wants to — a fresh id and a
     * fresh CSRF token, nothing left of the account being left — while
     * carrying both admin surfaces across it.
     */
    public static function invalidateKeepingAdmin(Request $request): void
    {
        $admin = $request->session()->only(array_merge(self::PANEL_KEYS, self::SITE_KEYS));

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($admin) {
            $request->session()->put($admin);
        }
    }
}

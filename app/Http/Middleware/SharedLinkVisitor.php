<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a visitor who arrived on a shared shortlist out of the full inventory.
 *
 * Opening /shared/{token} puts that token in the session. While it is there,
 * the browse-everything pages — the Map and the search results — send the
 * visitor back to their own shortlist instead. Everything else (Home, About,
 * Contact, cart, campaign, profile) is untouched.
 *
 * A visitor who never opened a shared link has no flag, so this middleware
 * does nothing at all for ordinary traffic.
 *
 * @see \App\Http\Controllers\Website\SharedLinkController::show()
 */
class SharedLinkVisitor
{
    /** Session key holding the token of the shortlist being viewed. */
    public const SESSION_KEY = 'shared_link_token';

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->session()->get(self::SESSION_KEY);

        if (!$token) {
            return $next($request);
        }

        // The team browses the real site in the same browser they generate
        // links from, so an admin is never locked out by their own link.
        if ($request->session()->has('user_id')) {
            return $next($request);
        }

        return redirect()
            ->route('shared.link.show', $token)
            ->with('info', 'You can view the hoardings shortlisted for you here.');
    }
}

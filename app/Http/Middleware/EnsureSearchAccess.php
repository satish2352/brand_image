<?php

namespace App\Http\Middleware;

use App\Http\Services\Website\SearchSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate in front of the media search.
 *
 * One middleware rather than a separate CheckVisitorPreview and
 * CheckSearchSession: which window applies is a property of who is asking, not
 * of which route was hit, and splitting it would mean two copies of the same
 * "is it still valid" logic on the same routes.
 *
 * The decision is entirely server-side — @see SearchSessionService::state().
 * Nothing the browser sends is consulted, so disabling JavaScript, editing
 * localStorage, moving the system clock or replaying the request from Postman
 * all reach exactly this check.
 *
 * Applied to the search routes only. The rest of the site stays open: an
 * expired window must not lock someone out of their cart or their campaigns.
 */
class EnsureSearchAccess
{
    public function __construct(private SearchSessionService $sessions) {}

    public function handle(Request $request, Closure $next): Response
    {
        $result = $this->sessions->evaluate($request);

        if ($result['allowed']) {
            return $next($request);
        }

        $reason  = $result['reason'];
        $message = $this->sessions->denialMessage($reason);

        // AJAX and API callers get JSON they can act on. The search page's
        // lazy-loader and the explore map both post here.
        if ($request->ajax() || $request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success'         => false,
                'message'         => $message,
                'session_expired' => true,
                'reason'          => $reason,
                'state'           => $result['state'],
            ], 403);
        }

        // The home page frames the Map at /explore?embed=1. Redirecting that
        // request to the home page loads the entire site inside the hero panel
        // — header, hero, this same modal — which is what it did before this
        // branch existed. The frame gets its own small notice instead.
        if ($request->boolean('embed')) {
            return response()->view('website.embed-blocked', [
                'message' => $message,
                'reason'  => $reason,
            ], 403);
        }

        // A normal page load goes home, where the countdown component renders
        // the matching modal. Redirecting rather than rendering here keeps the
        // expiry message in one place.
        return redirect()
            ->route('website.home')
            ->with('search_session_expired', $reason);
    }
}

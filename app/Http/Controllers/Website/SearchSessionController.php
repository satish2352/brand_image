<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Services\Website\SearchSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What the countdown component polls.
 *
 * Read-only on purpose: there is no endpoint anywhere that lets the browser
 * ask for more time. The only way a window opens is a first page view (preview)
 * or an authentication event (search), both decided server-side.
 */
class SearchSessionController extends Controller
{
    public function __construct(private SearchSessionService $sessions) {}

    public function status(Request $request): JsonResponse
    {
        $state = $this->sessions->state($request);

        return response()->json([
            'mode'              => $state['mode'],
            'active'            => $state['allowed'],
            'preview_active'    => $state['mode'] === 'preview' && $state['allowed'],
            'search_active'     => $state['mode'] === 'search' && $state['allowed'],
            'expires_at'        => $state['expires_at'],
            'remaining_seconds' => $state['remaining_seconds'],
            'message'           => $state['allowed'] ? null : $this->sessions->denialMessage($state['reason']),
            'reason'            => $state['reason'],
        ]);
    }
}

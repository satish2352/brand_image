<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * Admin screen for the search access durations.
 *
 * Writes the same keys the gate reads, so a change takes effect on the next
 * session opened — no deploy, no cache clear. Windows already running keep the
 * duration they were opened with, which is what stops an admin accidentally
 * cutting off a user mid-search.
 */
class PortalAccessController extends Controller
{
    /** The keys this screen owns, with their group. */
    private const GROUP = 'portal_access';

    public function edit()
    {
        return view('superadm.settings.portal-access', [
            'guestPreviewMinutes'   => Setting::getInt('guest_preview_minutes', (int) config('portal_access.guest_preview_minutes', 2)),
            'searchSessionMinutes'  => Setting::getInt('search_session_minutes', (int) config('portal_access.search_session_minutes', 5)),
            'gateEnabled'           => Setting::getBool('portal_access_gate_enabled', (bool) config('portal_access.gate_enabled', true)),
            'exemptPayingCustomers' => Setting::getBool('exempt_paying_customers', (bool) config('portal_access.exempt_paying_customers', true)),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            // A zero-minute window would lock everyone out instantly, and the
            // upper bound is just a guard against a stray keystroke.
            'guest_preview_minutes'  => 'required|integer|min:1|max:1440',
            'search_session_minutes' => 'required|integer|min:1|max:1440',
        ], [
            'guest_preview_minutes.min'  => 'Free visitor access must be at least 1 minute.',
            'search_session_minutes.min' => 'Logged-in search access must be at least 1 minute.',
        ]);

        Setting::put('guest_preview_minutes', $data['guest_preview_minutes'], self::GROUP);
        Setting::put('search_session_minutes', $data['search_session_minutes'], self::GROUP);
        Setting::put('portal_access_gate_enabled', $request->boolean('portal_access_gate_enabled') ? 1 : 0, self::GROUP);
        Setting::put('exempt_paying_customers', $request->boolean('exempt_paying_customers') ? 1 : 0, self::GROUP);

        return redirect()
            ->route('settings.portal-access')
            ->with('success', 'Search access settings saved. New sessions will use these durations.');
    }
}

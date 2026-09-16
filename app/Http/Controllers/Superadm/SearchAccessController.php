<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use App\Http\Services\Website\SearchSessionService;
use App\Models\MediaRequirement;
use App\Models\SearchSession;
use App\Models\WebsiteUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Who has used their search trial, and the button that grants another.
 *
 * The automatic trial is consumed once per account and never reopens on its
 * own — not the next day, not from another browser. This screen is the only
 * way to give somebody a second window, and every grant is recorded against
 * the admin who made it.
 */
class SearchAccessController extends Controller
{
    public function __construct(private SearchSessionService $sessions) {}

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $users = WebsiteUser::query()
            ->where('is_deleted', 0)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $userIds = $users->pluck('id');

        // Three aggregates in three queries rather than three per row.
        $sessionCounts = SearchSession::selectRaw('user_id, COUNT(*) as total')
            ->whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $trialUsed = SearchSession::where('type', SearchSession::TYPE_SEARCH_TRIAL)
            ->whereIn('user_id', $userIds)
            ->distinct()
            ->pluck('user_id')
            ->flip();

        $requirementCounts = MediaRequirement::selectRaw('user_id, COUNT(*) as total')
            ->whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        // Which of them is searching right now.
        $activeByUser = SearchSession::whereIn('user_id', $userIds)
            ->where('status', SearchSession::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->get()
            ->keyBy('user_id');

        return view('superadm.search-access.users', [
            'users'             => $users,
            'search'            => $search,
            'sessionCounts'     => $sessionCounts,
            'trialUsed'         => $trialUsed,
            'requirementCounts' => $requirementCounts,
            'activeByUser'      => $activeByUser,
            'searchMinutes'     => $this->sessions->searchMinutes(),
        ]);
    }

    /**
     * Every window this account has ever had, newest first.
     */
    public function history($encodedId)
    {
        $user = WebsiteUser::findOrFail(base64_decode($encodedId));

        $sessions = SearchSession::where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        // users.name for the admins who granted, resolved in one query.
        $grantorNames = DB::table('users')
            ->whereIn('id', $sessions->pluck('created_by')->filter()->unique())
            ->pluck('name', 'id');

        return view('superadm.search-access.history', [
            'user'         => $user,
            'sessions'     => $sessions,
            'grantorNames' => $grantorNames,
            'requirements' => MediaRequirement::where('user_id', $user->id)
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function grant(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:website_users,id',
        ]);

        $session = $this->sessions->grantSearchSession(
            (int) $data['user_id'],
            $request->session()->get('user_id')
        );

        return back()->with(
            'success',
            'Search access granted — ' . $this->sessions->searchMinutes()
                . ' minutes, starting now. It is recorded against your account.'
        );
    }

    /**
     * Cut a window short. The trial still counts as used.
     */
    public function revoke(Request $request)
    {
        $data = $request->validate([
            'session_id' => 'required|integer|exists:search_sessions,id',
        ]);

        SearchSession::where('id', $data['session_id'])
            ->where('status', SearchSession::STATUS_ACTIVE)
            ->update(['status' => SearchSession::STATUS_REVOKED]);

        return back()->with('success', 'Search session revoked.');
    }
}

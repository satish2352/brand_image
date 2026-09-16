<?php

namespace App\Http\Services\Website;

use App\Models\SearchSession;
use App\Models\Setting;
use App\Models\VisitorSession;
use Illuminate\Http\Request;
use App\Support\AdminSession;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Timed access to the media search.
 *
 * Everything here works from the server clock and the database. The browser is
 * told how long is left only so it can draw a countdown; it is never asked,
 * and never believed, about whether access should be granted.
 *
 * Two windows:
 *
 *  - preview  a visitor who has not signed in, identified by a cookie token
 *  - search   a signed-in user, identified by website_users.id
 *
 * Durations come from the settings table, falling back to config/portal_access
 * (which reads .env). Reading them per call is cheap: Setting caches.
 */
class SearchSessionService
{
    /** Why a request was refused, for the caller to render. */
    public const DENY_PREVIEW_EXPIRED = 'preview_expired';
    public const DENY_SEARCH_EXPIRED  = 'search_expired';

    /**
     * How long after a window closes it still counts as having "just ended".
     *
     * The expiry notice has to separate two visitors who look identical in the
     * database — window closed, trial spent — but want opposite things said to
     * them: one whose time ran out a moment ago and is still clicking around
     * the site, and one who closed the tab days ago and has come back. The
     * first should be told, on whatever page they are on. The second should be
     * greeted, because announcing "your session has ended" about something
     * that happened last Tuesday is a puzzle, not a notice.
     *
     * Half an hour is comfortably longer than a visit spent reading About and
     * Contact after the timer stopped, and comfortably shorter than a return
     * visit later on.
     */
    public const JUST_ENDED_GRACE_MINUTES = 30;

    /* ===================== configuration ===================== */

    public function previewMinutes(): int
    {
        return Setting::getInt(
            'guest_preview_minutes',
            (int) config('portal_access.guest_preview_minutes', 2)
        );
    }

    public function searchMinutes(): int
    {
        return Setting::getInt(
            'search_session_minutes',
            (int) config('portal_access.search_session_minutes', 5)
        );
    }

    public function gateEnabled(): bool
    {
        return Setting::getBool(
            'portal_access_gate_enabled',
            (bool) config('portal_access.gate_enabled', true)
        );
    }

    public function exemptsPayingCustomers(): bool
    {
        return Setting::getBool(
            'exempt_paying_customers',
            (bool) config('portal_access.exempt_paying_customers', true)
        );
    }

    /* ===================== visitor preview ===================== */

    /**
     * The token identifying this browser. Read from the cookie; minted and
     * queued onto the response if absent.
     *
     * Queuing rather than setting means the same token is used for the rest of
     * this request too, so a first-time visitor does not create one session
     * now and another on their next click.
     */
    public function visitorToken(Request $request): string
    {
        $name = config('portal_access.visitor_cookie', 'ba_visitor');

        $token = $request->cookie($name);

        // Also accept one queued earlier in this same request.
        if (!$token) {
            $queued = Cookie::getQueuedCookies();
            foreach ($queued as $cookie) {
                if ($cookie->getName() === $name) {
                    $token = $cookie->getValue();
                    break;
                }
            }
        }

        if (!$token || !is_string($token) || strlen($token) > 64) {
            $token = (string) Str::uuid();
            Cookie::queue(
                $name,
                $token,
                60 * 24 * (int) config('portal_access.visitor_cookie_days', 365),
                null,
                null,
                $request->secure(),
                true // httpOnly: JavaScript has no business rewriting this
            );
        }

        return $token;
    }

    /**
     * The preview window for this browser, started on first sight.
     *
     * Reused on every later request, so a refresh does not extend anything:
     * expires_at is written once, at creation, and only read afterwards.
     */
    public function visitorSession(Request $request, bool $createIfMissing = true): ?VisitorSession
    {
        $token = $this->visitorToken($request);

        $session = VisitorSession::where('visitor_token', $token)->first();

        if ($session) {
            // Record the expiry once rather than recomputing it forever.
            if ($session->status === VisitorSession::STATUS_ACTIVE && !$session->isActive()) {
                $session->update(['status' => VisitorSession::STATUS_EXPIRED]);
            }

            return $session;
        }

        if (!$createIfMissing) {
            return null;
        }

        $now = now();

        // firstOrCreate, not create: two tabs opening at once would otherwise
        // race on the unique token.
        return VisitorSession::firstOrCreate(
            ['visitor_token' => $token],
            [
                'started_at' => $now,
                'expires_at' => $now->copy()->addMinutes($this->previewMinutes()),
                'status'     => VisitorSession::STATUS_ACTIVE,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            ]
        );
    }

    /* ===================== logged-in search session ===================== */

    /**
     * The user's current window, or null. Expired rows are marked as such on
     * the way past so the "one active per user" index stays truthful.
     */
    public function activeSearchSession(int $userId): ?SearchSession
    {
        $session = SearchSession::where('user_id', $userId)
            ->where('status', SearchSession::STATUS_ACTIVE)
            ->latest('id')
            ->first();

        if (!$session) {
            return null;
        }

        if (!$session->isActive()) {
            $session->update(['status' => SearchSession::STATUS_EXPIRED]);

            return null;
        }

        return $session;
    }

    /**
     * Has this account already consumed its one automatic search trial?
     *
     * Read from the session history, not from a flag or a date. That is what
     * makes the answer survive a cleared cookie, a different browser, a new
     * device, and tomorrow.
     */
    public function hasUsedSearchTrial(int $userId): bool
    {
        return SearchSession::where('user_id', $userId)
            ->where('type', SearchSession::TYPE_SEARCH_TRIAL)
            ->exists();
    }

    /**
     * Open the automatic trial for a user who has just authenticated.
     *
     * Returns null when there is nothing to open — the trial has already been
     * used. Deliberately NOT date-aware: "has this account had its trial" is
     * the question, never "was the last one yesterday", which would hand out a
     * fresh window every morning.
     *
     * Idempotent for a window that is still running, so a second tab or a
     * repeated login shares the first rather than buying more time.
     */
    public function startSearchSession(int $userId, string $openedBy = 'login'): ?SearchSession
    {
        return DB::transaction(function () use ($userId, $openedBy) {
            // Lock the user's rows so two simultaneous logins cannot both
            // decide the trial is unused and both insert one.
            $active = SearchSession::where('user_id', $userId)
                ->where('status', SearchSession::STATUS_ACTIVE)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($active) {
                if ($active->isActive()) {
                    return $active;
                }

                $active->update(['status' => SearchSession::STATUS_EXPIRED]);
            }

            // The trial is spent. Only an admin grant opens another.
            if ($this->hasUsedSearchTrial($userId)) {
                return null;
            }

            return $this->createSession(
                $userId,
                SearchSession::TYPE_SEARCH_TRIAL,
                $openedBy
            );
        });
    }

    /**
     * An extra window handed out by the team, recorded against the admin who
     * granted it so the history says who and when.
     */
    public function grantSearchSession(int $userId, ?int $grantedByAdminId): SearchSession
    {
        return DB::transaction(function () use ($userId, $grantedByAdminId) {
            // A window already running is extended by replacement rather than
            // stacked, so "one active per user" still holds.
            SearchSession::where('user_id', $userId)
                ->where('status', SearchSession::STATUS_ACTIVE)
                ->lockForUpdate()
                ->update(['status' => SearchSession::STATUS_EXPIRED]);

            return $this->createSession(
                $userId,
                SearchSession::TYPE_ADMIN_GRANTED,
                'admin_grant',
                $grantedByAdminId
            );
        });
    }

    private function createSession(
        int $userId,
        string $type,
        string $openedBy,
        ?int $createdBy = null
    ): SearchSession {
        $now = now();

        return SearchSession::create([
            'user_id'    => $userId,
            'type'       => $type,
            'started_at' => $now,
            'expires_at' => $now->copy()->addMinutes($this->searchMinutes()),
            'status'     => SearchSession::STATUS_ACTIVE,
            'opened_by'  => $openedBy,
            'created_by' => $createdBy,
        ]);
    }

    /* ===================== the decision ===================== */

    /**
     * May this request reach the search?
     *
     * Returns ['allowed' => bool, 'reason' => ?string, 'state' => array] where
     * state is the same shape the status endpoint and the countdown consume.
     */
    public function evaluate(Request $request): array
    {
        $state = $this->state($request);

        return [
            'allowed' => $state['allowed'],
            'reason'  => $state['reason'],
            'state'   => $state,
        ];
    }

    /**
     * The whole picture for this request: who the visitor is, which window
     * applies, and how long is left of it.
     */
    public function state(Request $request): array
    {
        $base = [
            'mode'              => 'unrestricted',
            'allowed'           => true,
            'reason'            => null,
            'expires_at'        => null,
            'remaining_seconds' => null,
            // Default for everyone not on a logged-in window.
            'trial_used'        => false,
            // When the closed window ran out, and whether that was recent
            // enough to be worth announcing. Both null/false for anyone whose
            // window is still open or who was never on a timer.
            'ended_at'          => null,
            'just_ended'        => false,
            'preview_minutes'   => $this->previewMinutes(),
            'search_minutes'    => $this->searchMinutes(),
        ];

        // Gate off: the site behaves exactly as it did before this feature.
        if (!$this->gateEnabled()) {
            return $base;
        }

        // Staff browsing the public site are not customers on a timer.
        if (AdminSession::onSite()) {
            return array_merge($base, ['mode' => 'admin']);
        }

        $user = auth('website')->user();

        if ($user) {
            // website_users.access_exempt predates this feature and is exactly
            // the per-user override it looks like: an account the team does not
            // want on a timer at all.
            if ((int) ($user->access_exempt ?? 0) === 1) {
                return array_merge($base, ['mode' => 'exempt']);
            }

            if ($this->exemptsPayingCustomers() && $this->hasPaid($user->id)) {
                return array_merge($base, ['mode' => 'customer']);
            }

            $session = $this->activeSearchSession($user->id);

            if (!$session) {
                $endedAt = $this->lastSearchSessionEnd($user->id);

                return array_merge($base, [
                    'mode'              => 'search',
                    'allowed'           => false,
                    'reason'            => self::DENY_SEARCH_EXPIRED,
                    'remaining_seconds' => 0,
                    // Separates "your window just ran out" from "you are a
                    // returning user whose trial was spent days ago". Same
                    // access either way; different thing to say.
                    'trial_used'        => $this->hasUsedSearchTrial($user->id),
                    'ended_at'          => $endedAt?->toIso8601String(),
                    'just_ended'        => $this->endedRecently($endedAt),
                ]);
            }

            return array_merge($base, [
                'mode'              => 'search',
                'expires_at'        => $session->expires_at->toIso8601String(),
                'remaining_seconds' => $session->remainingSeconds(),
                'trial_used'        => true,
            ]);
        }

        // Not signed in: the free preview.
        $session = $this->visitorSession($request);

        if (!$session || !$session->isActive()) {
            $endedAt = $session?->expires_at;

            return array_merge($base, [
                'mode'              => 'preview',
                'allowed'           => false,
                'reason'            => self::DENY_PREVIEW_EXPIRED,
                'remaining_seconds' => 0,
                'ended_at'          => $endedAt?->toIso8601String(),
                'just_ended'        => $this->endedRecently($endedAt),
            ]);
        }

        return array_merge($base, [
            'mode'              => 'preview',
            'expires_at'        => $session->expires_at->toIso8601String(),
            'remaining_seconds' => $session->remainingSeconds(),
        ]);
    }

    /**
     * When this user's most recent window ran out, or null if they never had
     * one. Read only on the expired path, so an open window costs no query.
     */
    private function lastSearchSessionEnd(int $userId): ?\Illuminate\Support\Carbon
    {
        return SearchSession::where('user_id', $userId)
            ->latest('id')
            ->value('expires_at');
    }

    /**
     * Recent enough to still be worth announcing.
     *
     * A null timestamp means there is nothing to date — a preview session that
     * was never created, say — and is treated as NOT recent: the notice is for
     * a window the visitor watched run out, and silence is the safer default
     * when we cannot tell.
     */
    private function endedRecently(?\Illuminate\Support\Carbon $endedAt): bool
    {
        return $endedAt !== null
            && $endedAt->greaterThan(now()->subMinutes(self::JUST_ENDED_GRACE_MINUTES));
    }

    /**
     * Has this user ever completed a payment? Read straight from the orders
     * table rather than a flag, so it cannot drift.
     */
    private function hasPaid(int $userId): bool
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('orders')) {
            return false;
        }

        return DB::table('orders')
            ->where('user_id', $userId)
            // The values this column actually carries: PAID, ADMIN_BOOKED, PENDING.
            ->whereIn(DB::raw('UPPER(payment_status)'), ['PAID', 'ADMIN_BOOKED'])
            ->exists();
    }

    /**
     * The message shown when a window closes. Kept here so the modal, the JSON
     * response and any future channel all say the same thing.
     */
    public function denialMessage(?string $reason): string
    {
        return $reason === self::DENY_PREVIEW_EXPIRED
            ? 'Your free preview has ended. Please Login / Register to continue.'
            : 'Your search session has ended.';
    }
}

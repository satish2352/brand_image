{{-- ================= SEARCH ACCESS COUNTDOWN =================
     Display only. The server decides whether a search is allowed; this shows
     how long is left and puts up the right message when the window closes.
     Everything here works from the server's expires_at, never the browser
     clock, and with JavaScript off the gate still holds — the visitor simply
     does not see the ticking number.

     $searchAccess comes from the view composer in AppServiceProvider. --}}
@php
    $access = $searchAccess ?? null;
    // 'unrestricted', 'admin' and 'customer' are not on a timer at all.
    $onTimer = $access && in_array($access['mode'], ['preview', 'search'], true);

    // Signed in, trial spent, and nothing ran out just now: a returning user.
    // The home page greets them instead; an expiry modal would be announcing
    // something that happened days ago.
    //
    // just_ended is what keeps that from swallowing a real expiry: a window
    // that closed minutes ago still gets the notice, on whatever page the
    // visitor happens to be reading. @see SearchSessionService.
    $returningUser = $onTimer
        && $access['mode'] === 'search'
        && !$access['allowed']
        && ($access['trial_used'] ?? false)
        && !($access['just_ended'] ?? false)
        && !session()->has('search_session_expired');

    // Where the expiry message is allowed to take over the screen: everywhere.
    // The window closing is the event, not reaching the search — a visitor
    // whose time runs out while reading About should be told there and then,
    // rather than finding out later by clicking Search.
    //
    // Allowed is not the same as repeated, though: it is said ONCE, when the
    // window closes, and then the visitor is left alone to read Home, About
    // and Contact. Putting the same modal in front of them on every page they
    // open afterwards is not informing them a second time, it is a wall.
    // Which window has already been announced is tracked in the browser,
    // keyed on when that window ended — see the script below.
    //
    // The one exception is the map inside the home page hero: that is this
    // same layout in an iframe, and the notice belongs on the page around it,
    // not stacked inside a panel a few hundred pixels tall.
    $mayShowMessage = !$returningUser && !request()->boolean('embed');

    // When this window ends (or ended). Identifies the window, so the notice
    // for a preview that ran out is not mistaken for the one owed to the
    // search session the visitor gets after registering.
    $windowEnd = $access['expires_at'] ?? $access['ended_at'] ?? null;

    // Bounced off a guarded route by the middleware. Worth repeating even if
    // already said: the visitor just tried to reach the search and landed
    // somewhere else, and silence would leave that unexplained.
    $redirectedHere = session()->has('search_session_expired');

    // This browser is holding a link the team sent out. Same test the header
    // and the home page already use to decide someone is a shortlist client
    // rather than a visitor who found the site on their own.
    $onSharedShortlist = session()->has('shared_link_token') && !site_admin();
@endphp

{{-- A shared shortlist is its own grant: the link the team sent IS the
     client's access to those hoardings, and SharedLinkVisitor keeps them from
     wandering into the wider inventory whatever the preview clock says. So the
     countdown does not belong in front of them, and neither does "your free
     preview has ended, please log in" — it announces the loss of something
     they were never using and offers a remedy that would change nothing.

     For the whole visit, not just the shortlist page. The client leaves it the
     moment they click Read More on one of the hoardings they were sent, and
     meeting them on the detail page with an expiry notice is the same wrong
     message one click later. Home, About and Contact are reachable from the
     header and read no better.

     Display only, and deliberately so: the server-side gate in
     EnsureSearchAccess is untouched, so this hides a message, never a check. --}}
@if ($onTimer && !$onSharedShortlist)
    <div id="searchAccessBar" class="sa-bar {{ $access['allowed'] ? '' : 'is-hidden' }}"
        data-mode="{{ $access['mode'] }}"
        data-remaining="{{ (int) ($access['remaining_seconds'] ?? 0) }}"
        data-status-url="{{ route('search.session.status') }}"
        data-show-message="{{ $mayShowMessage ? '1' : '0' }}"
        data-window-end="{{ $windowEnd }}"
        data-force-message="{{ $redirectedHere ? '1' : '0' }}"
        data-active="{{ $access['allowed'] ? '1' : '0' }}">
        <span class="sa-bar-dot" aria-hidden="true"></span>
        <span class="sa-bar-text">
            {{ $access['mode'] === 'preview' ? 'Free preview' : 'Search session' }}:
            <strong id="searchAccessLeft">—</strong>
        </span>
    </div>
    {{-- Preview over: sign in or register.
         No close button, but not a trap either: Home, About Us and Contact Us
         stay open to an expired visitor, so the link below lets them carry on
         reading while only the search stays shut. --}}
    <div class="sa-modal" id="previewEndedModal" role="dialog" aria-modal="true"
        aria-labelledby="previewEndedTitle">
        <div class="sa-modal-card">
            <button type="button" class="sa-modal-close" data-sa-close aria-label="Close">&times;</button>
            <span class="sa-modal-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
            <h4 id="previewEndedTitle">Your free preview has ended.</h4>
            <p>Please Login / Register to continue.</p>
            <div class="sa-modal-actions">
                <button type="button" class="sa-btn sa-btn-primary" data-sa-close data-bs-toggle="modal"
                    data-bs-target="#authModal">Login</button>
                <button type="button" class="sa-btn sa-btn-ghost" data-sa-close data-bs-toggle="modal"
                    data-bs-target="#authModal" onclick="if (window.showSignup) showSignup();">Register</button>
            </div>

            {{-- data-sa-close as well as the href: on the home page itself the
                 link would otherwise reload the same page just to dismiss. --}}
            <p class="sa-modal-note">
                <a href="{{ route('website.home') }}" data-sa-close>Go to Home page</a>
            </p>
        </div>
    </div>

    {{-- Search session over. The user stays signed in — only the search
         permission has lapsed — but the portal is closed to them until the
         team picks it up, so the two routes forward are the only actions. --}}
    <div class="sa-modal" id="searchEndedModal" role="dialog" aria-modal="true"
        aria-labelledby="searchEndedTitle">
        <div class="sa-modal-card">
            <button type="button" class="sa-modal-close" data-sa-close aria-label="Close">&times;</button>
            <span class="sa-modal-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
            <h4 id="searchEndedTitle">Your search session has ended.</h4>
            <p>Our team can take it from here — call us, or send us your requirement and we will come back to you.</p>

            <div class="sa-modal-actions">
                {{-- Still a tel: link — a phone dials it, a desktop hands it to
                     whatever is installed — but labelled rather than printing the
                     number, which read as raw data next to the action beside it.
                     The number rides along on the title and the aria-label, so
                     it is still there to read, copy or hear. --}}
                <a href="tel:{{ config('portal_access.contact_phone') }}" class="sa-btn sa-btn-ghost"
                    title="{{ config('portal_access.contact_phone') }}"
                    aria-label="Contact our team on {{ config('portal_access.contact_phone') }}">
                    <i class="bi bi-telephone-fill" aria-hidden="true"></i>
                    Contact Our Team
                </a>
                <a href="{{ route('website.requirement.create', ['from' => 'expired']) }}"
                    class="sa-btn sa-btn-primary" data-sa-close>
                    <i class="bi bi-pencil-square" aria-hidden="true"></i> Share Your Requirement
                </a>
            </div>

            <p class="sa-modal-note">
                <a href="{{ route('website.home') }}" data-sa-close>Go to Home page</a>
            </p>
        </div>
    </div>

    <script>
        (function () {
            const bar = document.getElementById('searchAccessBar');
            if (!bar) return;

            const mode = bar.dataset.mode;
            const modal = document.getElementById(
                mode === 'preview' ? 'previewEndedModal' : 'searchEndedModal'
            );
            const leftEl = document.getElementById('searchAccessLeft');

            // Anchored to a local monotonic-ish deadline derived from the
            // server's remaining_seconds. Deliberately NOT parsed from a wall
            // clock the visitor controls: moving the system clock cannot buy
            // time here, and could not buy access anyway — the server re-checks
            // every request.
            let remaining = parseInt(bar.dataset.remaining, 10) || 0;
            let active = bar.dataset.active === '1';
            let deadline = Date.now() + remaining * 1000;

            // Whether expire() has already run. Separate from `active` on
            // purpose: a page that LOADS expired starts with active === false,
            // and guarding expire() on that flag alone made it return before
            // opening anything — which is why an expired visitor could walk
            // from page to page and never be told.
            let expired = false;

            function label(seconds) {
                if (seconds <= 0) return 'ended';

                // mm:ss, ticking every second: 4:45, 4:44 ... 0:59 ... 0:01.
                // Seconds are zero-padded so the width does not jump; minutes
                // are not, since they never exceed the configured duration.
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;

                return mins + ':' + String(secs).padStart(2, '0') + ' left';
            }

            // Whether the message may take over this page. About and Contact
            // stay readable, so there the window simply closes quietly and the
            // search controls (if any) grey out.
            const mayShowMessage = bar.dataset.showMessage === '1';

            /* ---------- saying it once ----------
               One key per window, so the notice owed to a preview that ran out
               is never confused with the one owed to the search session that
               follows it. localStorage rather than sessionStorage: the visitor
               may well have the site open in more than one tab, and being told
               again in each of them is the complaint this fixes.

               Wrapped, because storage throws rather than returns null in a
               browser set to block site data. Failing that way means the
               notice shows — which is the old behaviour, and the safe side of
               this trade: better said twice than never said at all. */
            const windowKey = 'sa-notice:' + mode + ':' + (bar.dataset.windowEnd || 'none');
            const forceMessage = bar.dataset.forceMessage === '1';

            function alreadyAnnounced() {
                try {
                    return localStorage.getItem(windowKey) === '1';
                } catch (e) {
                    return false;
                }
            }

            function markAnnounced() {
                try {
                    localStorage.setItem(windowKey, '1');
                } catch (e) {
                    /* nothing to do: see above */
                }
            }

            function expire() {
                if (expired) return;
                expired = true;
                active = false;
                bar.classList.add('is-hidden');

                // The countdown running out on the page in front of the
                // visitor lands here with the window unannounced, so it shows.
                // Every page they open afterwards lands here too, with the
                // same key already marked, and stays quiet.
                if (modal && mayShowMessage && (forceMessage || !alreadyAnnounced())) {
                    modal.classList.add('is-open');
                    markAnnounced();
                }

                // Not conditional: whether or not anything is said, controls
                // the server would now refuse must not look usable.
                disableSearchControls();
            }

            // Grey out the controls that would now be refused anyway, so the
            // visitor is not invited to fill in a form that cannot submit.
            function disableSearchControls() {
                document.querySelectorAll(
                    '#searchForm input, #searchForm select, #searchForm button, ' +
                    '#exploreForm input, #exploreForm select, #exploreForm button, ' +
                    '.btn-search, .btn-clear'
                ).forEach(function (el) {
                    el.disabled = true;
                });
                document.querySelectorAll('#searchForm, #exploreForm, .media-search-card')
                    .forEach(el => el.classList.add('sa-disabled'));
            }

            function tick() {
                if (!active) return;

                const seconds = Math.max(0, Math.round((deadline - Date.now()) / 1000));
                leftEl.textContent = label(seconds);

                if (seconds <= 0) {
                    // Confirm with the server before declaring it over: a clock
                    // that drifted, or a window reopened elsewhere, is the
                    // server's call, not ours.
                    verify();
                    return;
                }

                setTimeout(tick, 1000);
            }

            function verify() {
                fetch(bar.dataset.statusUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                })
                    .then(r => r.json())
                    .then(function (s) {
                        if (s.active && s.remaining_seconds > 0) {
                            // Still open — resynchronise and carry on.
                            deadline = Date.now() + s.remaining_seconds * 1000;
                            setTimeout(tick, 1000);
                            return;
                        }
                        expire();
                    })
                    .catch(expire);
            }

            if (!active) {
                expire();
            } else {
                tick();
                // Re-synchronise when a backgrounded tab comes forward: its
                // timers may have been throttled while it was hidden.
                document.addEventListener('visibilitychange', function () {
                    if (!document.hidden && active) verify();
                });
                // Restored from the back/forward cache: the page comes back
                // exactly as it was parked, timers and all, so a window that
                // ran out in the meantime would otherwise still be counting
                // down a number that expired minutes ago.
                window.addEventListener('pageshow', function (e) {
                    if (e.persisted && active) verify();
                });
            }

            // Any guarded XHR that comes back 403 with session_expired ends the
            // window immediately, without waiting for the countdown.
            document.addEventListener('search-session-expired', expire);

            /* ---------- closing the message ----------
               Only the actions close it. The backdrop and Escape deliberately
               do not: the window is shut, every other page redirects back
               here, and dismissing this would leave the visitor staring at a
               page they cannot use with no way forward. The Login and Register
               buttons close it because the auth dialog has to come out in
               front of it. */
            function closeMessage(el) {
                if (el) el.classList.remove('is-open');
            }

            document.addEventListener('click', function (e) {
                const trigger = e.target.closest('[data-sa-close]');
                if (trigger) {
                    closeMessage(trigger.closest('.sa-modal'));
                }
            });
        })();
    </script>
@endif

{{-- There used to be a second script here that opened the modal on a page load
     carrying the middleware's redirect flash. It was the only path that worked
     while expire() was swallowing the already-expired case, and now that the
     component opens the modal on any expired load it would just be a second
     place that decides which modal appears — one keyed on `reason`, the other
     on `mode`, free to disagree. The flash still matters: it is what makes
     $returningUser false, so a redirected user is told rather than greeted. --}}

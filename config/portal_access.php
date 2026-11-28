<?php

/*
|--------------------------------------------------------------------------
| Portal access gate
|--------------------------------------------------------------------------
|
| Timed access to the media search: a short free preview for a visitor who has
| not signed in, then a longer session once they have.
|
| These are DEFAULTS. The admin panel writes the same keys into the `settings`
| table, and a stored setting always wins — see App\Models\Setting and
| App\Http\Services\Website\SearchSessionService. The values here are what a
| fresh install (or an environment with no settings row) falls back to, so the
| feature is configurable from .env without touching code.
|
*/

return [

    /*
    | Minutes a brand-new visitor may browse and search before being asked to
    | log in or register.
    */
    'guest_preview_minutes' => (int) env('GUEST_PREVIEW_MINUTES', 2),

    /*
    | Minutes of search access granted once a visitor signs in or completes
    | registration. Starts at the moment of authentication, not at page load.
    */
    'search_session_minutes' => (int) env('SEARCH_SESSION_MINUTES', 5),

    /*
    | Master switch. Off means every request behaves exactly as it did before
    | this feature existed, which is the safe position for a rollback.
    */
    'gate_enabled' => (bool) env('PORTAL_ACCESS_GATE_ENABLED', true),

    /*
    | Whether a customer who has already paid for a booking skips the timer.
    | Off by default: the brief asks for the search window to apply to
    | authenticated users generally. The admin screen can switch it on.
    */
    'exempt_paying_customers' => (bool) env('PORTAL_ACCESS_EXEMPT_PAYING', false),

    /*
    | Shown as "Contact Brand Adda Team" when a search session ends. Kept here
    | rather than typed into the view so the number lives in one place.
    */
    'contact_phone' => env('PORTAL_ACCESS_CONTACT_PHONE', '+917770018173'),

    /*
    | Name of the cookie carrying the visitor token, and how long the browser
    | keeps it. The token identifies a visitor across refreshes and restarts;
    | the session it points at is what actually expires, server-side.
    */
    'visitor_cookie' => env('PORTAL_ACCESS_COOKIE', 'ba_visitor'),
    'visitor_cookie_days' => (int) env('PORTAL_ACCESS_COOKIE_DAYS', 365),

];

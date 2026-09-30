<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    //  ADD THIS
    'razorpay' => [
        'key'    => env('RAZORPAY_KEY'),
        'secret' => env('RAZORPAY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI'),
    ],
    /* ---------------- Google reCAPTCHA ---------------- */
    'recaptcha' => [
        'enabled' => env('RECAPTCHA_ENABLED', false),
        'site'    => env('RECAPTCHA_SITE_KEY'),
        'secret'  => env('RECAPTCHA_SECRET_KEY'),
    ],

    /* ---------------- Media Location Insights ----------------
       Which API supplies nearby places: geoapify (default) or serpapi. */
    'insights' => [
        'places_provider' => env('INSIGHTS_PLACES_PROVIDER', 'geoapify'),
        // Fetch nearby places the first time a hoarding's insights are viewed
        // (then served from the database). Still limited by the credit budget.
        'fetch_on_view' => (bool) env('INSIGHTS_FETCH_ON_VIEW', true),
        // api_usage_logs rows older than this are pruned daily (min 35).
        'log_retention_days' => (int) env('INSIGHTS_LOG_RETENTION_DAYS', 180),
    ],

    /* ---------------- Geoapify Places (Media Location Insights) ----------------
       https://apidocs.geoapify.com/docs/places/ — server-side only: the key is
       read here and by App\Http\Services\Insights\GeoapifyPlacesService, never
       passed to a view, a JS variable, a JSON response or a log line. */
    'geoapify' => [
        'key'               => env('GEOAPIFY_API_KEY'),
        'base_url'          => env('GEOAPIFY_BASE_URL', 'https://api.geoapify.com'),
        'timeout'           => (int) env('GEOAPIFY_TIMEOUT', 15),
        // Search radius around the hoarding, in metres.
        'radius'            => (int) env('GEOAPIFY_RADIUS', 1000),
        // Places per request (API allows 1–500). Billing is 1 credit per 20
        // places returned, so 60 costs at most 3 credits per location.
        'limit'             => (int) env('GEOAPIFY_LIMIT', 60),
        'categories'        => env('GEOAPIFY_CATEGORIES',
            'education.school,education.college,education.university,office,'
            . 'commercial.shopping_mall,commercial.marketplace,building.commercial,'
            . 'healthcare.hospital,healthcare.clinic_or_praxis,leisure.park,'
            . 'tourism.attraction,tourism.sights'),
        'lang'              => env('GEOAPIFY_LANG', 'en'),
        // Our own ceiling per calendar day (free plan: 3,000 credits/day).
        // Kept below the plan so manual refreshes always have headroom.
        'daily_credit_limit' => (int) env('GEOAPIFY_DAILY_CREDIT_LIMIT', 2500),
        // Days a location's places are reused before a refresh may spend credits.
        'cache_days'        => (int) env('GEOAPIFY_CACHE_DAYS', 90),
        // Rounding for the shared location cache (3 dp ≈ 110 m).
        'coord_precision'   => (int) env('GEOAPIFY_COORD_PRECISION', 3),
        // Scheduled batch: hoardings queued per run, and the request rate the
        // queued jobs are held to (free plan allows 5 requests/second).
        'batch_size'        => (int) env('GEOAPIFY_BATCH_SIZE', 500),
        'requests_per_second' => (int) env('GEOAPIFY_REQUESTS_PER_SECOND', 4),
    ],

    /* ---------------- SerpApi (Media Location Insights) ----------------
       Server-side only: the key is read here and by App\Services\SerpApiService,
       never passed to a view, a JS variable or a JSON response. */
    'serpapi' => [
        'key'            => env('SERPAPI_API_KEY'),
        'base_url'       => env('SERPAPI_BASE_URL', 'https://serpapi.com'),
        // Our own ceiling for successful searches per calendar month. The free
        // plan is 250; keep this at or below the plan so we stop before SerpApi does.
        'monthly_limit'  => (int) env('SERPAPI_MONTHLY_LIMIT', 250),
        'timeout'        => (int) env('SERPAPI_TIMEOUT', 20),
        // How long a location's nearby-places result is reused before a refresh
        // is allowed to spend another search on it.
        'cache_days'     => (int) env('SERPAPI_CACHE_DAYS', 90),
        // google_maps engine parameters
        'maps_query'     => env('SERPAPI_MAPS_QUERY', 'popular places'),
        'maps_zoom'      => (int) env('SERPAPI_MAPS_ZOOM', 16),
        'hl'             => env('SERPAPI_HL', 'en'),
        'gl'             => env('SERPAPI_GL', 'in'),
        // Decimal places lat/lng are rounded to before searching. 3 dp is about
        // 110 m, so hoardings standing together share one search.
        'coord_precision' => (int) env('SERPAPI_COORD_PRECISION', 3),
    ],
];

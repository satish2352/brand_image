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
       Which API supplies nearby places: geoapify (default), tomtom or serpapi. */
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

    /* ---------------- TomTom Search + Traffic (Media Location Insights) ----------------
       https://developer.tomtom.com/search-api/documentation/search-service/nearby-search
       https://developer.tomtom.com/traffic-api/documentation/traffic-flow/flow-segment-data
       Server-side only: the key is read here and by
       App\Http\Services\Insights\TomTomPlacesService / TomTomTrafficService,
       never sent to a view. 'key' and 'api_key' are the same TOMTOM_API_KEY. */
    'tomtom' => [
        'key'               => env('TOMTOM_API_KEY'),
        'api_key'           => env('TOMTOM_API_KEY'),
        'base_url'          => env('TOMTOM_BASE_URL', 'https://api.tomtom.com'),
        'timeout'           => (int) env('TOMTOM_TIMEOUT', 15),
        // Search radius around the hoarding, in metres.
        'radius'            => (int) env('TOMTOM_RADIUS', 1000),
        // Places per request (API allows 1–100). Billed per request, not per place.
        'limit'             => (int) env('TOMTOM_LIMIT', 60),
        // TomTom POI category ids. List them all with:
        //   GET https://api.tomtom.com/search/2/poiCategories.json?key=YOUR_KEY
        // 7372 School, 7377 College/University, 7321 Hospital/Polyclinic,
        // 7373 Shopping Center, 7332 Market, 9362 Park & Recreation Area,
        // 7376 Important Tourist Attraction, 7367 Government Office, 9352 Company.
        'category_set'      => env('TOMTOM_CATEGORY_SET', '7372,7377,7321,7373,7332,9362,7376,7367,9352'),
        'language'          => env('TOMTOM_LANGUAGE', 'en-GB'),
        // Our own ceiling per calendar day (free plan: 2,500 requests/day).
        'daily_request_limit' => (int) env('TOMTOM_DAILY_REQUEST_LIMIT', 2000),
        // Days a location's places are reused before a refresh may spend a request.
        'cache_days'        => (int) env('TOMTOM_CACHE_DAYS', 90),
        // Rounding for the shared location cache (3 dp ≈ 110 m).
        'coord_precision'   => (int) env('TOMTOM_COORD_PRECISION', 3),
        // Request rate the queued jobs are held to.
        'requests_per_second' => (int) env('TOMTOM_REQUESTS_PER_SECOND', 4),

        // ---- Traffic Flow (refresh:hoarding-traffic → hoarding_traffic_data) ----
        // Days a hoarding's stored traffic flow is used before it is fetched again.
        'traffic_refresh_days' => (int) env('TOMTOM_CACHE_DAYS', 15),
        // Hoardings read from the database per chunk (never all at once).
        'traffic_batch_size'   => (int) env('TOMTOM_BATCH_SIZE', 100),
        // Pause between two traffic requests, in milliseconds (one at a time).
        'traffic_request_delay_ms' => (int) env('TOMTOM_REQUEST_DELAY_MS', 250),
        // Our own ceiling on traffic requests per calendar day. TomTom's free
        // plan is 2,500 non-tile requests a day, shared with Search.
        'traffic_daily_limit'  => (int) env('TOMTOM_TRAFFIC_DAILY_LIMIT', 2000),
        // A failed hoarding is tried again after this many hours, not every run.
        'traffic_retry_hours'  => (int) env('TOMTOM_TRAFFIC_RETRY_HOURS', 24),
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

    /* ---------------- RoadStar Audience API ----------------
       "RoadStar API for Audience Data" (API Documentation V2, Relu Ai Systems).
       HTTP Basic auth on every request. Server-side only: username/password
       are read here and by App\Http\Services\RoadStar\RoadStarService, never
       passed to a view, a JS variable, a JSON response, a log line or the
       database.

       The documentation states no rate limit, page size or batch size, so
       every limit below is our own and configurable — not RoadStar's. */
    'roadstar' => [
        'base_url'        => env('ROADSTAR_BASE_URL'),
        'username'        => env('ROADSTAR_USERNAME'),
        'password'        => env('ROADSTAR_PASSWORD'),
        'timeout'         => (int) env('ROADSTAR_TIMEOUT', 60),
        'connect_timeout' => (int) env('ROADSTAR_CONNECT_TIMEOUT', 10),

        // Sites sent in one POST /sitedata (it accepts an array of sites).
        'batch_size'      => (int) env('ROADSTAR_BATCH_SIZE', 20),
        // Pause between two requests when running in-process (--sync).
        'request_delay_ms' => (int) env('ROADSTAR_REQUEST_DELAY_MS', 1000),
        // Requests per minute the queued jobs are held to.
        'requests_per_minute' => (int) env('ROADSTAR_REQUESTS_PER_MINUTE', 30),
        // Most hoardings `roadstar:sync` queues per run, so a first load of
        // ~10,000 is spread over several days instead of one burst.
        'max_per_run'     => (int) env('ROADSTAR_MAX_PER_RUN', 2000),
        'queue'           => env('ROADSTAR_QUEUE', 'roadstar'),
        // Bulk sync (scheduled roadstar:sync, admin "sync many"). Off until
        // single-media sync is confirmed; single-media sync always works.
        'bulk_enabled'    => (bool) env('ROADSTAR_BULK_SYNC_ENABLED', false),

        // A mapped hoarding is refreshed this many days after its last sync.
        'refresh_days'    => (int) env('ROADSTAR_REFRESH_DAYS', 15),
        // A failed attempt (network, 5xx, 429, auth) is retried after this.
        'retry_hours'     => (int) env('ROADSTAR_RETRY_HOURS', 24),

        // Audience period requested from /sitedata (st / ed). Rolling window:
        // the last ROADSTAR_PERIOD_DAYS days ending ROADSTAR_PERIOD_END_OFFSET_DAYS
        // before today. ROADSTAR_PERIOD_START + ROADSTAR_PERIOD_END (yyyy-mm-dd)
        // pin a fixed period instead — the dev server only answers for
        // 2024-03-01 → 2024-03-31.
        'period_days'     => (int) env('ROADSTAR_PERIOD_DAYS', 30),
        'period_end_offset_days' => (int) env('ROADSTAR_PERIOD_END_OFFSET_DAYS', 1),
        'period_start'    => env('ROADSTAR_PERIOD_START'),
        'period_end'      => env('ROADSTAR_PERIOD_END'),

        // Site identifier ("arr") we register a hoarding under with /addsite:
        // prefix + hoarding code (or "M" + media id when it has no code).
        'site_prefix'     => env('ROADSTAR_SITE_PREFIX', 'BI/'),

        // Scheduled `roadstar:sync`: time of day, and weekdays only (the test
        // server is up Mon–Fri 10:00–20:00).
        'schedule_at'     => env('ROADSTAR_SCHEDULE_AT', '11:00'),
        'schedule_weekdays_only' => (bool) env('ROADSTAR_SCHEDULE_WEEKDAYS_ONLY', true),
    ],
];

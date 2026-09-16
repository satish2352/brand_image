<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- <title>@yield('title', 'Website')</title> --}}
    <title>Brand Adda Maharashtra's Outdoor Media Platform</title>

    {{-- The live site is brand-adda.co.in, but APP_URL is whatever the current
         install runs on (localhost in development), so the public address gets
         its own key. The path is carried over rather than hardcoding "/", or
         every page would be declaring itself a copy of the home page. --}}
    @php
        $siteUrl = rtrim(config('app.site_url'), '/');
        $canonical = $siteUrl . '/' . ltrim(request()->path() === '/' ? '' : request()->path(), '/');
    @endphp
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:site_name" content="Brand Adda Maharashtra's Outdoor Media Platform">
    <meta property="og:title" content="Brand Adda Maharashtra's Outdoor Media Platform">
    <meta property="og:url" content="{{ $canonical }}">

    {{-- This page pulls CSS and JS from four third party origins, and the
         browser cannot start any of them until it has done a DNS lookup, a TCP
         connection and a TLS handshake for each — serially, while rendering is
         blocked. Warming the connections here lets all four happen at once,
         alongside the HTML parse, instead of one after another.

         The uploaded pictures come from FILE_VIEW, which is a different host
         again from the site itself, so that one is warmed too — otherwise the
         hero slider image cannot even begin downloading until its handshake
         finishes. --}}
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://code.jquery.com" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    @php
        $fileHost = rtrim((string) env('FILE_VIEW'), '/');
        $fileOrigin = $fileHost ? parse_url($fileHost, PHP_URL_SCHEME) . '://' . parse_url($fileHost, PHP_URL_HOST) : null;
    @endphp
    @if ($fileOrigin && $fileOrigin !== rtrim(config('app.url'), '/'))
        <link rel="preconnect" href="{{ $fileOrigin }}" crossorigin>
    @endif

    {{-- Bootstrap 5 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="icon" type="image/webp" href="{{ asset('assets/img/logo/brand_adda_browser.webp') }}">
    <link rel="shortcut icon" type="image/webp" href="{{ asset('assets/img/logo/brand_adda_browser.webp') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/logo/brand_adda_browser.webp') }}">
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum/build/pannellum.css" />
    {{-- Date pickers are only built inside click handlers, never while the page
         parses, so this does not have to block the first paint. --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>

    <!-- fontawesome -->
    <link rel="stylesheet" href="{{ asset('assets/css/all.min.css') }}">
    <!-- bootstrap -->
    {{-- <link rel="stylesheet" href="{{ asset('assets/bootstrap/css/bootstrap.min.css') }}"> --}}
    {{-- owl carousel, magnific popup and animate.css were removed: all three
         were render blocking on every page and styled nothing. The elements
         they dress (.owl-carousel, .mfp-*) are not rendered anywhere any more —
         the sliders are Swiper — and no stylesheet or view uses an animate.css
         class, the page animations being AOS and this site's own keyframes.
         animate.css alone was 66KB of blocking CSS. --}}
    <!-- mean menu css -->
    <link rel="stylesheet" href="{{ asset('assets/css/meanmenu.min.css') }}">
    @php
        // Cache-buster for the stylesheets we actually edit. Browsers hang on to
        // these for a long time, so a design change can look like it "did not
        // apply" until a hard refresh. Appending the file's own modification
        // time changes the URL whenever the file changes, and never otherwise.
        $cssVersion = fn(string $path) => file_exists(public_path($path)) ? filemtime(public_path($path)) : null;
    @endphp

    <!-- main style -->
    <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}?v={{ $cssVersion('assets/css/main.css') }}">
    <!-- responsive -->
    <link rel="stylesheet" href="{{ asset('assets/css/responsive.css') }}?v={{ $cssVersion('assets/css/responsive.css') }}">

    {{-- Custom Website CSS --}}
    <link rel="stylesheet"
        href="{{ asset('asset/css/website_css/style.css') }}?v={{ $cssVersion('asset/css/website_css/style.css') }}">

    {{-- Swal.fire() is only ever called from a handler or a ready callback. --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>

    {{-- jQuery stays render blocking on purpose: inline <script> blocks all over
         the site use $ as the page parses (every $(document).ready(...) among
         them), and those run before any deferred script would have defined it.
         It is loaded once here — the footer used to load a second, older copy
         over the top of this one. --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet"> --}}

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    {{-- Every slider is constructed inside DOMContentLoaded, which fires after
         deferred scripts have run — so this no longer has to block the parse. --}}
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute('content')
            }
        });
    </script>

</head>
<style>
    /* DIRECT CHILDREN ONLY.
       These describe the header's own square tiles — cart, account, admin.
       As descendant rules they also reached every <a> inside the dropdown
       MENUS that hang in this same container: the 55x46 squashed each row to
       the size of a tile and clipped its label, and the orange background
       below turned every row into a solid block with the text lost inside it.
       The account menu already had to out-specify both with !important; the
       admin menu hit the same wall. Scoping with ">" is what the white-glyph
       rule in style.css settled on for exactly this reason — one place to fix
       rather than an override per menu. */
    .header-icons>.btn,
    .header-icons>a {
        width: 55px;
        height: 46px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }

    .header-icons {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .header-icons>a {
        /* width: 55px;
    height: 46px; */
        background: #F97316;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        position: relative;
    }

    .header-icons i,
    .header-icons img {
        font-size: 18px;
        /* width: 22px;
    height: 22px; */
    }

    .cart-count {
        position: absolute;
        top: -6px;
        right: -6px;
        background: #0F172A;
        color: #FFFFFF;
        font-size: 12px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .user-icon img {
        width: 22px;
        height: 22px;
        object-fit: cover;
        border-radius: 50%;
    }
</style>

<body class="d-flex flex-column min-vh-100 {{ auth('website')->check() ? 'user-logged-in' : 'user-guest' }}">

    {{-- Include Header --}}
    {{-- @include('website.includes.header') --}}
    {{-- ?embed=1 renders the page bare for use inside an iframe (the home page
         hero embeds the map page this way) — a second navbar inside the frame
         would just be confusing. --}}
    @unless (request()->boolean('embed'))
        @include('website.includes.newheader')
    @endunless

    {{-- Main Content --}}
    <main class="flex-fill">
        @yield('content')
    </main>

    {{-- Include Footer --}}
    @if (!request()->is('search') && !request()->is('brand_image/public/search') && !request()->is('explore') && !request()->is('brand_image/public/explore'))
        @include('website.includes.footer')
    @else
        {{-- The map pages render without the site footer, but that include is
             also where every script tag lives — the mobile menu plugin among
             them, which left these pages with no hamburger at all. Pull in just
             that one plugin here; main.js and sticker.js are deliberately left
             out because they would undo this page's header overrides. --}}
        <script src="{{ asset('assets/js/jquery.meanmenu.min.js') }}"></script>
        <script>
            jQuery(function ($) {
                $('.main-menu').meanmenu({
                    meanMenuContainer: '.mobile-menu',
                    meanScreenWidth: '992',
                    // The plugin's default is "<span /><span /><span />".
                    // jQuery 1.11 (loaded with the footer on every other page)
                    // expands those self-closing tags into three siblings;
                    // jQuery 3 nests them instead, leaving a single visible
                    // bar. Spell the three bars out so both parsers agree.
                    meanMenuOpen: '<span></span><span></span><span></span>'
                });
            });
        </script>
    @endif

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"
        defer>
    </script>

    <!-- GLOBAL LOADER -->
    <div id="globalLoader" aria-hidden="true" class="global-loader">
        <div class="global-loader__backdrop"></div>

        <div class="loader-wrapper">
            <div class="dotted-loader"></div>
            <div class="loader-text">Please wait...</div>
        </div>
    </div>

    @yield('scripts')
    {{-- The 360° viewer is only built when someone opens a panorama. --}}
    <script src="https://cdn.jsdelivr.net/npm/pannellum/build/pannellum.js" defer></script>
    @if(session('password_changed'))
    <script>
        $(document).ready(function () {
            Swal.fire({
                icon: 'success',
                title: 'Password Changed!',
                text: '{{ session('password_changed') }}',
                confirmButtonText: 'Login',
                confirmButtonColor: '#F97316'
            }).then(function () {
                // open login modal
                var modal = new bootstrap.Modal(document.getElementById('authModal'));
                modal.show();
            });
        });
    </script>
    @endif
    <script>
        // Force hide theme preloader in case CDN resources are slow
        $(document).ready(function() {
            $(".loader").fadeOut(500);
        });
        // Fallback: hide after 3 seconds no matter what
        setTimeout(function() {
            $(".loader").hide();
        }, 3000);
    </script>

    {{-- Search access countdown and expiry messages. Renders nothing at all
         when the visitor is not on a timer (gate off, admin, paying customer). --}}
    @include('website.includes.search-session')
</body>

</html>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $vendor->display_name) · Powered by Brand Adda</title>
    <meta name="description" content="Outdoor media inventory of {{ $vendor->display_name }}, powered by Brand Adda.">
    <link rel="icon" type="image/webp" href="{{ asset('assets/img/logo/brand_adda_browser.webp') }}">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --vs-brand: #0F172A;
            --vs-brand-rgb: 15, 23, 42;
            --vs-brand-text: #FFFFFF;
            --vs-brand-muted: #CBD5E1;
            --vs-brand-raised: #1E293B;
            --vs-brand-link: #FDBA74;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Montserrat', system-ui, sans-serif; color: #0F172A; background: #F8FAFC; }
        a { color: #C2410C; }
        a:hover { color: #9A3412; }
        .vs-wrap { width: 100%; max-width: 1280px; margin: 0 auto; padding: 0 32px; }
        /* Header: stays at the top while scrolling; gains a shadow once the page moves. */
        .vs-head { position: sticky; top: 0; z-index: 1000; background: rgba(255,255,255,.96); backdrop-filter: saturate(180%) blur(8px);
            border-bottom: 1px solid #E5E7EB; transition: box-shadow .2s; }
        .vs-head.is-scrolled { box-shadow: 0 8px 24px rgba(15,23,42,.08); }
        [id] { scroll-margin-top: 100px; }
        .vs-head .vs-wrap { display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between; padding-top: 14px; padding-bottom: 14px; }
        .vs-brand { display: flex; align-items: center; gap: 14px; text-decoration: none; color: #0F172A; }
        .vs-mono { width: 52px; height: 52px; border-radius: 12px; background: #F97316; color: #0F172A; display: grid; place-items: center; font-weight: 800; font-size: 19px; overflow: hidden; flex: none; }
        .vs-mono img { width: 100%; height: 100%; object-fit: contain; background: #fff; }
        .vs-name { font-size: 21px; font-weight: 800; }
        .vs-sub { font-size: 13px; color: #475569; }
        .vs-nav { display: flex; gap: 14px; align-items: center; }
        .vs-nav a.vs-link { min-height: 42px; display: inline-flex; align-items: center; gap: 8px; padding: 0 18px; border-radius: 999px;
            font-size: 14px; font-weight: 600; color: #334155; text-decoration: none; transition: background .15s, color .15s; }
        .vs-nav a.vs-link i { font-size: 14px; color: #94A3B8; transition: color .15s; }
        .vs-nav a.vs-link:hover { background: #F8FAFC; color: #0F172A; }
        .vs-nav a.vs-link:hover i { color: #F97316; }
        .vs-nav a.vs-link.is-active { background: #FFF7ED; color: #C2410C; }
        .vs-nav a.vs-link.is-active i { color: #F97316; }
        .vs-nav a.vs-cta { margin-left: 14px; min-height: 42px; display: inline-flex; align-items: center; gap: 8px; padding: 0 20px; border-radius: 999px;
            background: #F97316; color: #fff; font-size: 14px; font-weight: 700; text-decoration: none; box-shadow: 0 6px 16px rgba(249,115,22,.28); transition: background .15s, transform .15s; }
        .vs-nav a.vs-cta:hover { background: #EA580C; color: #fff; transform: translateY(-1px); }
        .vs-menu-btn { display: none; width: 44px; height: 44px; border-radius: 12px; border: 1px solid #E5E7EB; background: #fff; color: #0F172A; font-size: 18px; cursor: pointer; }
        @media (max-width: 900px) {
            .vs-head .vs-wrap { position: relative; flex-wrap: nowrap; }
            .vs-menu-btn { display: grid; place-items: center; flex: none; }
            .vs-nav { display: none; position: absolute; left: 16px; right: 16px; top: calc(100% + 8px); flex-direction: column; align-items: stretch; gap: 4px;
                padding: 10px; background: #fff; border: 1px solid #E5E7EB; border-radius: 16px; box-shadow: 0 16px 40px rgba(15,23,42,.14); }
            .vs-nav.is-open { display: flex; }
            .vs-nav a.vs-link { border-radius: 10px; }
            .vs-nav a.vs-cta { margin: 6px 0 0; justify-content: center; }
            .vs-brand-sep, .vs-powered span { display: none; }
        }
        .vs-brandbar { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; }
        .vs-brand-sep { width: 1px; height: 40px; background: #E5E7EB; }
        .vs-powered { display: flex; flex-direction: column; align-items: flex-start; gap: 3px; text-decoration: none; padding: 4px 0 4px 4px; }
        .vs-powered span { font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: #64748B; line-height: 1; }
        .vs-powered img { height: 34px; width: auto; display: block; }
        @media (max-width: 640px) { .vs-powered img { height: 26px; } }
        .vs-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
        .vs-label { display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; }
        .vs-input { width: 100%; min-height: 44px; padding: 0 12px; border: 1px solid #CBD5E1; border-radius: 10px; font: 500 14px 'Montserrat', sans-serif; color: #0F172A; background: #fff; }
        .vs-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 44px; padding: 0 18px; border-radius: 10px; font: 700 14px 'Montserrat', sans-serif; text-decoration: none; cursor: pointer; border: 1px solid transparent; }
        .vs-btn-o, .vs-btn-o:hover { background: #F97316; color: #0F172A; }
        .vs-btn-n, .vs-btn-n:hover { background: var(--vs-brand); color: var(--vs-brand-text); border-color: var(--vs-brand-raised); }
        .vs-btn-g, .vs-btn-g:hover { background: #fff; color: #0F172A; border-color: #CBD5E1; }
        .vs-ph { background: repeating-linear-gradient(135deg, #E2E8F0 0 12px, #F1F5F9 12px 24px); display: grid; place-items: center; color: #475569; font-size: 13px; font-weight: 600; }
        .vs-alert { padding: 12px 16px; border-radius: 10px; font-size: 14px; }
        .vs-alert-error { background: #FEF2F2; border: 1px solid #FECACA; color: #7F1D1D; }
        .vs-alert-success { background: #F0FDF4; border: 1px solid #BBF7D0; color: #166534; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
        @media (max-width: 640px) { .vs-wrap { padding: 0 16px; } .vs-name { font-size: 17px; } }
    </style>
    @yield('styles')
</head>

<body>
    <header class="vs-head">
        <div class="vs-wrap">
            {{-- Left: the vendor, then "Powered by Brand Adda". Right: page links. --}}
            <div class="vs-brandbar">
            <a href="{{ route('vendor-site.show', $vendor->slug) }}" class="vs-brand">
                <span class="vs-mono">
                    @if ($vendor->logo_url)<img src="{{ $vendor->logo_url }}" alt="">@else{{ $vendor->initials }}@endif
                </span>
                <span>
                    <span class="vs-name" style="display:block">{{ $vendor->display_name }}</span>
                    @hasSection('tagline')<span class="vs-sub">@yield('tagline')</span>@endif
                </span>
            </a>
            <span class="vs-brand-sep" aria-hidden="true"></span>
            <a href="{{ url('/') }}" class="vs-powered" target="_blank" rel="noopener" title="Brand Adda – Maharashtra’s Outdoor Media Platform">
                <span>Powered by</span>
                <img src="{{ asset('assets/img/logo/brand_adda_powered.png') }}" alt="Brand Adda – Maharashtra’s Outdoor Media Platform">
            </a>
            </div>
            @hasSection('nav')
                <button type="button" class="vs-menu-btn" aria-expanded="false" aria-controls="vsNav" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
                <nav class="vs-nav" id="vsNav" aria-label="Main menu">
                    @yield('nav')
                </nav>
            @endif
        </div>
    </header>

    @yield('content')

    <script>
        (function () {
            // Shadow under the header once the page scrolls.
            const head = document.querySelector('.vs-head');
            const onScroll = function () { head.classList.toggle('is-scrolled', window.scrollY > 8); };
            window.addEventListener('scroll', onScroll, { passive: true }); onScroll();

            // Phone menu.
            const btn = document.querySelector('.vs-menu-btn'), nav = document.getElementById('vsNav');
            if (btn && nav) {
                const close = function () { nav.classList.remove('is-open'); btn.setAttribute('aria-expanded', 'false'); btn.innerHTML = '<i class="fa-solid fa-bars"></i>'; };
                btn.addEventListener('click', function () {
                    const open = !nav.classList.contains('is-open');
                    nav.classList.toggle('is-open', open); btn.setAttribute('aria-expanded', String(open));
                    btn.innerHTML = open ? '<i class="fa-solid fa-xmark"></i>' : '<i class="fa-solid fa-bars"></i>';
                });
                nav.addEventListener('click', function (e) { if (e.target.closest('a')) close(); });
                document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
            }
        })();
    </script>

    @yield('scripts')
</body>

</html>

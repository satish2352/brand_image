<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $vendor->vendor_name) · Powered by Brand Adda</title>
    <meta name="description" content="Outdoor media inventory of {{ $vendor->vendor_name }}, powered by Brand Adda.">
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
        .vs-head { background: #fff; border-bottom: 1px solid #E5E7EB; }
        .vs-head .vs-wrap { display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between; padding-top: 14px; padding-bottom: 14px; }
        .vs-brand { display: flex; align-items: center; gap: 14px; text-decoration: none; color: #0F172A; }
        .vs-mono { width: 52px; height: 52px; border-radius: 12px; background: #F97316; color: #0F172A; display: grid; place-items: center; font-weight: 800; font-size: 19px; overflow: hidden; flex: none; }
        .vs-mono img { width: 100%; height: 100%; object-fit: contain; background: #fff; }
        .vs-name { font-size: 21px; font-weight: 800; }
        .vs-sub { font-size: 13px; color: #475569; }
        .vs-nav { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .vs-nav a.vs-link { min-height: 44px; display: inline-flex; align-items: center; padding: 0 14px; font-size: 14px; font-weight: 700; color: #0F172A; text-decoration: none; }
        .vs-powered { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: #475569; }
        .vs-powered img { height: 38px; width: auto; }
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
            <a href="{{ route('vendor-site.show', $vendor->slug) }}" class="vs-brand">
                <span class="vs-mono">
                    @if ($vendor->logo_url)<img src="{{ $vendor->logo_url }}" alt="">@else{{ $vendor->initials }}@endif
                </span>
                <span>
                    <span class="vs-name" style="display:block">{{ $vendor->vendor_name }}</span>
                    @hasSection('tagline')<span class="vs-sub">@yield('tagline')</span>@endif
                </span>
            </a>
            <nav class="vs-nav" aria-label="Page sections">
                @yield('nav')
                <span class="vs-powered">Powered by<img src="{{ asset('assets/img/logo/brand_adda.png') }}" alt="Brand Adda"></span>
            </nav>
        </div>
    </header>

    @yield('content')

    @yield('scripts')
</body>

</html>

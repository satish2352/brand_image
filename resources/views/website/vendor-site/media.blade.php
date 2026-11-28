@extends('website.vendor-site.layout')
@section('title', collect([$media->hoarding_code ?: $media->media_code, $media->media_title])->filter()->implode(' · ') ?: 'Media')

@section('nav')
    @include('website.vendor-site.partials.nav', ['active' => 'media'])
@endsection

@section('styles')
    @if ($media->panorama_image)<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum/build/pannellum.css">@endif
    @if ($availability['status'] !== 'ask')<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">@endif
    <style>
        .vs-head .vs-wrap { max-width: 1440px; }
        .md { max-width: 1440px; padding-top: 22px; padding-bottom: 64px; }
        .md [id], .md section { scroll-margin-top: 104px; }
        .md h2 { margin: 0; font-size: 20px; font-weight: 800; }
        /* Banner with a dark shadow, so the breadcrumb on it stays readable. */
        .md-banner { position: relative; height: clamp(104px, 13vw, 220px); background: #0F172A; overflow: hidden; }
        .md-banner img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: center 45%; }
        .md-banner::after { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, rgba(15,23,42,.86) 0%, rgba(15,23,42,.66) 45%, rgba(15,23,42,.45) 100%); }
        .md-banner .vs-wrap { position: relative; z-index: 1; max-width: 1440px; height: 100%; display: flex; gap: 16px; align-items: center; justify-content: space-between; }
        .md-crumb ol { display: flex; flex-wrap: wrap; gap: 4px 12px; align-items: center; margin: 0; padding: 0; list-style: none; font-size: 18px; font-weight: 700; color: #fff; }
        .md-crumb li + li::before { content: '/'; margin-right: 12px; font-weight: 500; color: #CBD5E1; }
        .md-crumb a { display: inline-flex; align-items: center; min-height: 44px; color: #FDBA74; text-decoration: none; }
        .md-crumb a:hover { color: #fff; text-decoration: underline; }
        .md-banner .vs-btn { flex: none; }
        @media (max-width: 640px) { .md-crumb ol { font-size: 15px; gap: 0 8px; } .md-crumb li + li::before { margin-right: 8px; } .md-crumb a { min-height: 32px; } }
        .md-info h1 { margin: 0; font-size: 30px; line-height: 1.2; font-weight: 800; color: #EA580C; overflow-wrap: anywhere; }
        .md-code { display: inline-block; margin: 12px 0 10px; padding: 5px 10px; border-radius: 6px; background: #F97316; color: #0F172A; font-size: 13px; font-weight: 700; letter-spacing: .5px; }
        .md-place { display: flex; gap: 8px; align-items: baseline; font-size: 15px; color: #334155; }
        .md-place i { color: #F97316; }
        /* Same layout as the Brand Adda media page. Left: photos, then Location Insights. Right: the site
           with its booking dates and buttons, then more sites. Both columns end on the same line. */
        .md-cols { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 24px; align-items: stretch; }
        .md-col { display: flex; flex-direction: column; gap: 24px; min-width: 0; }
        .md-gallery { padding: 16px !important; }
        .md-info { display: flex; flex-direction: column; }
        #media-insights { flex: 1; }
        #more { flex: 1; display: flex; flex-direction: column; }
        .md-info h2 { margin-bottom: 14px; }
        .md-info hr { width: 100%; margin: 20px 0; border: 0; border-top: 1px solid #CBD5E1; }
        .md-card { background: #fff; border: 1px solid #E5E7EB; border-radius: 16px; padding: 24px; }
        .md-lead { margin: 4px 0 18px; font-size: 14px; color: #475569; }

        /* photos */
        .md-photo { position: relative; border-radius: 10px; overflow: hidden; background: #F1F5F9; aspect-ratio: 3 / 2; }
        .md-photo > button.md-zoom { position: absolute; inset: 0; width: 100%; height: 100%; padding: 0; border: 0; background: none; cursor: zoom-in; }
        .md-photo img { width: 100%; height: 100%; object-fit: contain; display: block; transition: transform .3s ease; }
        @media (hover: hover) { .md-photo.zoom img { transform: scale(2); } }
        .md-nav { position: absolute; top: 50%; margin-top: -24px; width: 48px; height: 48px; border: 0; border-radius: 50%; background: rgba(255,255,255,.94); color: #0F172A; font-size: 16px; cursor: pointer; }
        .md-nav:hover { background: #fff; }
        .md-count { position: absolute; left: 14px; bottom: 14px; padding: 6px 12px; border-radius: 999px; background: rgba(15,23,42,.82); color: #fff; font-size: 13px; font-weight: 600; }
        .md-photo .md-360 { position: absolute; right: 14px; bottom: 14px; }
        .md-thumbs { display: flex; gap: 10px; overflow-x: auto; padding: 2px; margin-top: 12px; }
        .vs-thumb { flex: 0 0 116px; height: 78px; border-radius: 12px; overflow: hidden; border: 3px solid transparent; padding: 0; cursor: pointer; background: #F1F5F9; }
        .vs-thumb.active { border-color: #F97316; }
        .vs-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .md-box { max-width: min(1400px, 94vw); max-height: 92vh; padding: 0; border: 0; border-radius: 14px; background: #0F172A; overflow: hidden; }
        .md-box::backdrop { background: rgba(15,23,42,.86); }
        .md-box img { display: block; max-width: min(1400px, 94vw); max-height: 92vh; }
        .md-box button { position: absolute; top: 12px; right: 12px; width: 48px; height: 48px; border: 0; border-radius: 50%; background: #fff; font-size: 18px; cursor: pointer; }

        /* site details: bold label, then the value, two a row */
        .md-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 24px; margin: 0; font-size: 15px; line-height: 1.5; }
        .md-list dt, .md-list dd { display: inline; margin: 0; overflow-wrap: anywhere; }
        .md-list dt { font-weight: 700; } .md-list dt::after { content: ': '; }
        .md-list dd { color: #334155; }
        .md-list .w { grid-column: 1 / -1; }

        /* buying card */
        .md-acts { display: flex; flex-direction: column; gap: 10px; margin-top: 18px; }
        .md-price { margin: 18px 0 14px; font-size: 26px; font-weight: 800; line-height: 1; color: #EA580C; }
        .md-price small { font-size: 15px; font-weight: 500; color: #475569; }
        .md-avail { display: flex; gap: 12px; padding: 14px 16px; border-radius: 12px; font-size: 14px; line-height: 1.45; }
        .md-avail i { margin-top: 3px; font-size: 17px; }
        .md-avail b { display: block; font-size: 15px; }
        .md-acts .vs-btn { min-height: 50px; padding: 6px 10px; line-height: 1.3; text-align: center; }
        .md-acts .md-enquire { box-shadow: 0 4px 12px rgba(249,115,22,.35); }
        .md-two > :only-child { grid-column: 1 / -1; }
        .md-enquire, .md-enquire:hover { background: #F97316; color: #0F172A; font-size: 16px; }
        .md-two { display: grid; grid-template-columns: repeat(auto-fit, minmax(112px, 1fr)); gap: 10px; }
        .md-wa, .md-wa:hover { background: #15803D; color: #fff; }
        .md-note { font-size: 13px; color: #475569; line-height: 1.5; margin: 0; }

        /* Booking calendar: the one on the Brand Adda media page, in its bordered box. */
        .md-calbox { display: flex; flex-wrap: wrap; gap: 16px 24px; align-items: center; padding: 10px; border: 1px solid #E5E7EB; border-radius: 8px; background: #fff; }
        .md-calbox .flatpickr-calendar { box-shadow: none; border: 1px solid #E5E7EB; border-radius: 6px; font-family: 'Montserrat', sans-serif; max-width: 100%; top: 0; }
        .md-calbox .flatpickr-current-month { font-size: 116%; padding-top: 5px; }
        .md-calbox .flatpickr-current-month .flatpickr-monthDropdown-months, .md-calbox .flatpickr-current-month input.cur-year { font-weight: 500; }
        .md-calbox .flatpickr-day { color: #0F172A; border-radius: 4px; }
        .md-calbox .flatpickr-day:hover { background: #FFF7ED; border-color: #F97316; }
        .md-calbox .flatpickr-day.prevMonthDay, .md-calbox .flatpickr-day.nextMonthDay, .md-calbox .flatpickr-day.flatpickr-disabled, .md-calbox .flatpickr-day.flatpickr-disabled:hover { color: #94A3B8; background: transparent; border-color: transparent; cursor: not-allowed; }
        .md-calbox .flatpickr-day.nextMonthDay:not(.flatpickr-disabled) { cursor: pointer; }
        .md-calbox .flatpickr-day.today { border-color: #0F172A; border-radius: 50%; }
        .md-calbox .flatpickr-day.booked-date, .md-calbox .flatpickr-day.booked-date:hover { background: #F97316; border-color: #F97316; color: #0F172A; border-radius: 4px; overflow: visible; }
        .md-calbox .flatpickr-day.inRange, .md-calbox .flatpickr-day.startRange, .md-calbox .flatpickr-day.endRange, .md-calbox .flatpickr-day.selected { background: #0F172A; border-color: #0F172A; color: #fff; border-radius: 4px; box-shadow: none; }
        @media (hover: hover) {
            .md-calbox .flatpickr-day.booked-date:hover::after, .md-calbox .flatpickr-day:not(.flatpickr-disabled):hover::after { content: 'Available'; position: absolute; left: 50%; bottom: 84%; transform: translateX(-50%); z-index: 9;
                padding: 3px 7px; border-radius: 4px; background: #0F172A; color: #fff; font-size: 11px; line-height: 1.3; white-space: nowrap; }
            .md-calbox .flatpickr-day.booked-date:hover::after { content: 'Booked'; }
        }
        .md-ask { display: grid; justify-items: center; gap: 14px; padding: 44px 20px; text-align: center; border: 1px dashed #CBD5E1; border-radius: 14px; color: #334155; }
        .md-ask i.big { font-size: 34px; color: #F97316; }
        .md-key { display: flex; flex-direction: column; gap: 12px; padding: 4px; font-size: 13px; color: #334155; }
        .md-key span { display: inline-flex; gap: 8px; align-items: center; }
        .md-key i { width: 16px; height: 16px; border-radius: 5px; }

        /* similar sites */
        .md-similar { flex: 1; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); grid-auto-rows: 1fr; gap: 18px; margin-top: 14px; }
        .md-sim { display: flex; flex-direction: column; background: #fff; border: 1px solid #E5E7EB; border-radius: 14px; overflow: hidden; text-decoration: none; color: #0F172A; transition: box-shadow .15s; }
        .md-sim:hover { box-shadow: 0 10px 24px rgba(15,23,42,.12); color: #0F172A; }
        .md-sim .p { position: relative; flex: 1; min-height: 150px; background: #F1F5F9; }
        .md-sim img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; }
        .md-sim .vs-badge { position: absolute; left: 10px; top: 10px; }
        .md-sim .t { padding: 14px 16px 16px; }
        .md-sim b { display: block; font-size: 16px; line-height: 1.3; }
        .md-sim small { display: block; font-size: 13px; color: #475569; margin: 4px 0 8px; }

        .md-bar { display: none; }
        @media (max-width: 1080px) {
            .md-cols { display: flex; flex-direction: column; }
            .md-col { display: contents; }
            .md-gallery { order: 1; } .md-info { order: 2; } #media-insights { order: 3; } #more { order: 4; }
            .md-similar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 640px) {
            .md-calbox { justify-content: center; } .md-calbox > div:first-child { width: 100%; }
            .md-calbox .flatpickr-calendar, .md-calbox .flatpickr-rContainer, .md-calbox .flatpickr-days, .md-calbox .dayContainer { width: 100%; min-width: 0; max-width: 100%; }
            .md-calbox .flatpickr-rContainer { display: block; } .md-calbox .flatpickr-day { max-width: none; }
            .md-key { flex-direction: row; flex-wrap: wrap; gap: 8px 18px; }
            .md { padding-bottom: 110px; }
            .md-info h1 { font-size: 25px; }
            .md-list { grid-template-columns: minmax(0, 1fr); gap: 10px; }
            .md-gallery { padding: 10px !important; }
            .md-cols { gap: 16px; }
            .md-card { padding: 18px; }
            /* More sites: swipe sideways instead of a very long page. */
            .md-similar { display: flex; gap: 14px; overflow-x: auto; scroll-snap-type: x mandatory; margin-left: -16px; margin-right: -16px; padding: 0 16px 8px; scrollbar-width: none; }
            .md-sim { flex: 0 0 78%; scroll-snap-align: center; }
            .md-bar { position: fixed; left: 0; right: 0; bottom: 0; z-index: 900; display: flex; gap: 12px; align-items: center; justify-content: space-between; padding: 10px 16px;
                background: #fff; border-top: 1px solid #E5E7EB; box-shadow: 0 -6px 20px rgba(15,23,42,.1); }
            .md-bar b { font-size: 18px; } .md-bar small { display: block; color: #475569; font-size: 12px; }
            .md-bar .vs-btn { min-height: 48px; } .md-bar .md-wa { padding: 0; width: 50px; font-size: 20px; }
            .md-bar .go { display: flex; gap: 8px; }
        }

        /* Location Insights, shown for buyers: the shared design (insights/assets), minus the admin bar
           and the tiles that have no data. */
        #media-insights .ins-tile:has(> .ins-value--na):not(.ins-tile--wide) { display: none; }
        #media-insights .ins-admin { display: none !important; }
    </style>
@endsection

@section('content')
    @php
        $code = $media->hoarding_code ?: $media->media_code;
        // Title and area, as on the Brand Adda page; the area once only.
        $title = $media->media_title ?: $media->category_name;
        $title .= $media->area_name && !\Illuminate\Support\Str::contains($title, $media->area_name, true) ? ' ' . $media->area_name : '';
        $hasSize = (float) $media->width > 0 && (float) $media->height > 0;
        $area = $media->area_auto ? (float) $media->area_auto : ($hasSize ? (float) $media->width * (float) $media->height : 0);
        $place = collect([$media->area_name, $media->city_name, $media->state_name])->filter()->unique()->implode(', ');
        $enquireUrl = route('vendor-site.contact', ['vendorSlug' => $vendor->slug, 'media' => $media->id]) . '#enquiry';
        $waText = 'Hi ' . $vendor->display_name . ', I am interested in ' . collect([$code, $media->media_title])->filter()->implode(' · ') . '. ' . url()->current();

        // Only what is known: empty rows are left out.
        $details = array_filter([
            'Category Media Type' => $media->category_name,
            'Facing'          => $media->facing,
            'Area Type'       => $media->areatype_name,
            'Size'            => $hasSize ? number_format((float) $media->width, 2) . ' x ' . number_format((float) $media->height, 2) . ' ft' : null,
            'Total Area'      => $area ? number_format($area, 2) . ' SQFT' : null,
            'Illumination'    => $media->illumination_name,
            'Airport'         => $media->airport_name ?? null,
            'Zone'            => $media->zone_type ?? null,
            'Media Type'      => $media->media_type ?? null,
            'Transit Type'    => $media->transit_type ?? null,
            'Branding Type'   => $media->branding_type ?? null,
            'Vehicle Count'   => $media->vehicle_count ?? null,
            'Building Name'   => $media->building_name ?? null,
            'Mall Name'       => $media->mall_name ?? null,
            'Media Format'    => $media->media_format ?? null,
            'Landmarks'       => $landmarks->implode(', '),
        ], 'filled');

        $fmt = fn ($d) => \Carbon\Carbon::parse($d)->format('d M Y');
        $nextRange = $ranges->first(fn ($r) => $r->from_date > now()->toDateString());
        [$avTitle, $avText, $avIcon] = match ($availability['status']) {
            'booked'  => ['Booked till ' . $fmt($availability['to']), 'Available from ' . $availableFrom->format('d M Y') . '. Send an enquiry for dates after that.', 'fa-regular fa-calendar-check'],
            'blocked' => ['Not available till ' . $fmt($availability['to']), 'Available from ' . $availableFrom->format('d M Y') . '. Send an enquiry for dates after that.', 'fa-regular fa-calendar-xmark'],
            'ask'     => ['Ask for availability', 'Send an enquiry and ' . $vendor->display_name . ' will confirm the dates.', 'fa-regular fa-circle-question'],
            default   => ['Available now', $nextRange ? 'Free till ' . \Carbon\Carbon::parse($nextRange->from_date)->subDay()->format('d M Y') . '.' : 'No upcoming bookings.', 'fa-regular fa-circle-check'],
        };
    @endphp

    <div class="md-banner">
        <img src="{{ asset('assets/img/vendor_media_banner.webp') }}" width="2000" height="350" alt="">
        <div class="vs-wrap">
            <nav class="md-crumb" aria-label="Breadcrumb">
                <ol>
                    <li><a href="{{ route('vendor-site.show', $vendor->slug) }}#inventory">All media</a></li>
                    <li aria-current="page">{{ $media->media_title ?: $media->category_name }}</li>
                </ol>
            </nav>
            <button type="button" id="shareLink" class="vs-btn vs-btn-g"><i class="fa-solid fa-share-nodes" aria-hidden="true"></i><span>Share</span></button>
        </div>
    </div>

    <main class="vs-wrap md">
        <div class="md-cols">
            <div class="md-col">
            {{-- ================= PHOTOS ================= --}}
            <section class="md-card md-gallery" aria-label="Photos">
                <div class="md-photo">
                    @if ($images->isNotEmpty())
                        <button type="button" class="md-zoom" id="zoomPhoto" aria-label="Open photo full size">
                            <img id="mainPhoto" src="{{ \App\Support\MediaImageUrl::for($images->first()->images) }}" alt="{{ $media->media_title }}"></button>
                        @if ($images->count() > 1)
                            <button type="button" class="md-nav" style="left:14px" data-step="-1" aria-label="Previous photo"><i class="fa-solid fa-chevron-left"></i></button>
                            <button type="button" class="md-nav" style="right:14px" data-step="1" aria-label="Next photo"><i class="fa-solid fa-chevron-right"></i></button>
                        @endif
                        <span class="md-count" aria-live="polite"><span id="photoNo">1</span> / {{ $images->count() }}</span>
                    @else
                        <div class="vs-ph" style="height:100%">No photo yet</div>
                    @endif
                    @if ($media->panorama_image)
                        <button type="button" id="open360" class="vs-btn vs-btn-g md-360"><i class="fa-solid fa-street-view"></i>View 360°</button>
                    @endif
                </div>
                @if ($images->count() > 1)
                    <div class="md-thumbs">
                        @foreach ($images as $img)
                            <button type="button" class="vs-thumb {{ $loop->first ? 'active' : '' }}" data-src="{{ \App\Support\MediaImageUrl::for($img->images) }}" aria-label="Photo {{ $loop->iteration }}">
                                <img src="{{ \App\Support\MediaImageUrl::for($img->images) }}" alt="" loading="lazy"></button>
                        @endforeach
                    </div>
                @endif
                @if ($media->panorama_image)
                    <div id="pano" style="display:none;height:420px;border-radius:16px;overflow:hidden;margin-top:12px"></div>
                @endif
            </section>

            {{-- Location Insights: the same panel as the Brand Adda media page, loaded by AJAX. --}}
            <section id="media-insights" class="insights-card md-card" aria-live="polite" data-url="{{ route('media.insights.show', base64_encode($media->id)) }}">
                <h4>Location Insights</h4>
                <div class="ins-loading" role="status">Loading location insights…</div>
            </section>
            </div>

            <div class="md-col">
            {{-- ================= SITE, PRICE, BOOKING DATES ================= --}}
            <section class="md-card md-info" id="availability">
                <h1>{{ $title }}</h1>
                @if ($code)<div><span class="md-code">{{ $code }}</span></div>@endif
                @if ($media->address || $place)<div class="md-place"><i class="fa-solid fa-location-dot" aria-hidden="true"></i><span>{{ $media->address ? collect([$media->address, $media->city_name])->filter()->implode(', ') : $place }}</span></div>@endif
                <hr>
                <dl class="md-list">
                    @foreach ($details as $k => $v)<div @class(['w' => $k === 'Landmarks'])><dt>{{ $k }}</dt><dd>{!! str_replace('/', '/<wbr>', e($v)) !!}</dd></div>@endforeach
                </dl>
                <div class="md-price">₹ {{ number_format((float) $media->price, 2) }} <small>/ month</small></div>
                <div class="md-avail" style="background:{{ $availability['bg'] }};color:{{ $availability['fg'] }}">
                    <i class="{{ $avIcon }}" aria-hidden="true"></i><div><b>{{ $avTitle }}</b>{{ $avText }}</div>
                </div>
                <hr>

                @if ($availability['status'] !== 'ask')
                    <h2>Select Booking Dates</h2>
                    <div class="md-calbox">
                        <div><input type="text" id="booking_range" hidden aria-label="Booking dates"></div>
                        <div class="md-key">
                            <span><i style="box-shadow:inset 0 0 0 1px #CBD5E1"></i>Available</span><span><i style="background:#F97316"></i>Booked</span>
                            <span><i style="background:#0F172A"></i>Your dates</span><span><i style="border:1px solid #0F172A;border-radius:50%"></i>Today</span>
                        </div>
                    </div>
                @else
                    <div class="md-ask"><i class="big fa-regular fa-calendar" aria-hidden="true"></i><span>{{ $vendor->display_name }} will tell you which dates are free.</span></div>
                @endif
                <div class="md-acts">
                    <a href="{{ $enquireUrl }}" class="vs-btn md-enquire" id="enquireMain"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i><span>Enquire about this site</span></a>
                    <div class="md-two">
                        @if ($contact['tel'])<a href="tel:{{ $contact['tel'] }}" class="vs-btn vs-btn-g"><i class="fa-solid fa-phone" aria-hidden="true"></i>Call</a>@endif
                        @if ($contact['whatsapp'])<a href="https://wa.me/{{ $contact['whatsapp'] }}?text={{ rawurlencode($waText) }}" target="_blank" rel="noopener" class="vs-btn md-wa"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i>WhatsApp</a>@endif
                    </div>
                </div>
            </section>

            {{-- ================= SIMILAR SITES ================= --}}
            @if ($similar->isNotEmpty())
                <section id="more" aria-labelledby="similarTitle">
                    <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:center">
                        <h2 id="similarTitle">More sites from {{ $vendor->display_name }}</h2>
                        <a href="{{ route('vendor-site.show', $vendor->slug) }}#inventory" style="font-weight:700">View all media</a>
                    </div>
                    <div class="md-similar">
                        @foreach ($similar as $s)
                            <a class="md-sim" href="{{ route('vendor-site.media', [$vendor->slug, $s->id]) }}">
                                <div class="p">
                                    @if (isset($similarCovers[$s->id]))<img src="{{ \App\Support\MediaImageUrl::for($similarCovers[$s->id]) }}" alt="" loading="lazy">@else<div class="vs-ph" style="height:100%">No photo yet</div>@endif
                                    <span class="vs-badge" style="background:{{ $s->availability['bg'] }};color:{{ $s->availability['fg'] }}">{{ $s->availability['label'] }}</span>
                                </div>
                                <div class="t">
                                    <b>{{ $s->media_title }}</b>
                                    <small>{{ collect([$s->area_name, $s->city_name])->filter()->unique()->implode(', ') }}</small>
                                    <span style="font-size:17px;font-weight:800">₹ {{ number_format((float) $s->price) }}</span> <span style="font-size:13px;color:#475569">/ month</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
            </div>
        </div>
    </main>

    {{-- Phone: price and the main action stay in reach. --}}
    <div class="md-bar">
        <div><b>₹ {{ number_format((float) $media->price) }}</b> <small>/ month</small></div>
        <div class="go">
            @if ($contact['whatsapp'])<a href="https://wa.me/{{ $contact['whatsapp'] }}?text={{ rawurlencode($waText) }}" target="_blank" rel="noopener" class="vs-btn md-wa" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a>@endif
            <a href="{{ $enquireUrl }}" class="vs-btn md-enquire">Enquire</a>
        </div>
    </div>

    @if ($images->isNotEmpty())
        <dialog class="md-box" id="photoBox" aria-label="Photo"><img alt="{{ $media->media_title }}"><button type="button" aria-label="Close"><i class="fa-solid fa-xmark"></i></button></dialog>
    @endif
@endsection

@section('scripts')
    @include('insights.assets')
    @if ($availability['status'] !== 'ask')<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>@endif
    @if ($media->panorama_image)<script src="https://cdn.jsdelivr.net/npm/pannellum/build/pannellum.js"></script>@endif
    <script>
        (function () {
            // Booking dates, as on the Brand Adda media page. Choosing across a booked day is not possible.
            const picker = document.getElementById('booking_range');
            if (picker && window.flatpickr) {
                const booked = @json($bookedRanges);
                const go = document.getElementById('enquireMain'), barText = document.querySelector('.md-bar small');
                const asks = Array.from(document.querySelectorAll('a')).filter(function (a) { return a.href === go.href; });
                const base = go.href.split('#')[0], iso = function (d) { return flatpickr.formatDate(d, 'Y-m-d'); };
                const cal = flatpickr(picker, {
                    mode: 'range', minDate: 'today', dateFormat: 'Y-m-d', inline: true, disable: booked,
                    onDayCreate: function (a, b, c, el) {
                        const d = iso(el.dateObj);
                        if (booked.some(function (r) { return r.from <= d && r.to >= d; })) el.classList.add('booked-date');
                    },
                    onChange: function (dates) {
                        const done = dates.length === 2, same = done && iso(dates[0]) === iso(dates[1]);
                        const n = done ? Math.round((dates[1] - dates[0]) / 86400000) + 1 : 0;
                        go.querySelector('span').textContent = done ? 'Enquire for ' + flatpickr.formatDate(dates[0], 'd M') + (same ? '' : ' – ' + flatpickr.formatDate(dates[1], 'd M')) : 'Enquire about this site';
                        // Every Enquire button of this site carries the chosen dates.
                        asks.forEach(function (a) { a.href = base + (done ? '&from=' + iso(dates[0]) + '&to=' + iso(dates[1]) : '') + '#enquiry'; });
                        barText.textContent = done ? '/ month · ' + n + (n === 1 ? ' day chosen' : ' days chosen') : '/ month';
                    }
                });
                // Not free today: open on the month in which it is free again.
                @if ($availableFrom) cal.jumpToDate(@json($availableFrom->toDateString())); @endif
            }

            // Photos: thumbnails, arrows, arrow keys and a full-size view.
            const main = document.getElementById('mainPhoto');
            const thumbs = Array.from(document.querySelectorAll('.vs-thumb'));
            let at = 0;
            function show(i) {
                if (!thumbs.length) return;
                at = (i + thumbs.length) % thumbs.length;
                main.src = thumbs[at].dataset.src;
                document.getElementById('photoNo').textContent = at + 1;
                thumbs.forEach(function (t, k) { t.classList.toggle('active', k === at); });
            }
            thumbs.forEach(function (t, i) { t.addEventListener('click', function () { show(i); }); });
            document.querySelectorAll('.md-nav').forEach(function (b) { b.addEventListener('click', function () { show(at + Number(b.dataset.step)); }); });
            document.querySelector('.md-photo').addEventListener('keydown', function (e) {
                if (e.key === 'ArrowRight') show(at + 1); else if (e.key === 'ArrowLeft') show(at - 1);
            });
            // Move over the photo to zoom in, as on the Brand Adda media page.
            const frame = document.querySelector('.md-zoom');
            if (frame) {
                frame.addEventListener('mousemove', function (e) {
                    const r = frame.getBoundingClientRect();
                    main.style.transformOrigin = ((e.clientX - r.left) / r.width * 100) + '% ' + ((e.clientY - r.top) / r.height * 100) + '%';
                    frame.parentNode.classList.add('zoom');
                });
                frame.addEventListener('mouseleave', function () { frame.parentNode.classList.remove('zoom'); });
            }
            const box = document.getElementById('photoBox');
            if (box && main) {
                document.getElementById('zoomPhoto').addEventListener('click', function () { box.querySelector('img').src = main.src; box.showModal(); });
                box.addEventListener('click', function (e) { if (e.target === box || e.target.closest('button')) box.close(); });
            }

            document.getElementById('shareLink').addEventListener('click', function () {
                const label = this.querySelector('span'), url = window.location.href.split('#')[0];
                if (navigator.share) navigator.share({ title: document.title, url: url }).catch(function () {});
                else navigator.clipboard.writeText(url).then(function () { label.textContent = 'Link copied'; });
            });

            @if ($media->panorama_image)
                document.getElementById('open360').addEventListener('click', function () {
                    const pano = document.getElementById('pano');
                    pano.style.display = 'block';
                    pannellum.viewer('pano', { type: 'equirectangular', panorama: @json(\App\Support\MediaImageUrl::for($media->panorama_image)), autoLoad: true });
                    pano.scrollIntoView({ behavior: 'smooth' });
                });
            @endif
        })();
    </script>
@endsection

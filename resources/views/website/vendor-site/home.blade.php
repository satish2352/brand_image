@extends('website.vendor-site.layout')
@section('title', $vendor->display_name)
@section('tagline', $totals['media'] . ' outdoor media sites')

@section('nav')
    @include('website.vendor-site.partials.nav', ['active' => 'home'])
@endsection

@section('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
    <style>
        .vs-slider { position: relative; height: 480px; background: var(--vs-brand); overflow: hidden; }
        .vs-slide { position: absolute; inset: 0; opacity: 0; transition: opacity .6s ease; }
        .vs-slide.active { opacity: 1; }
        .vs-slide img, .vs-slide video { width: 100%; height: 100%; object-fit: cover; display: block; }
        .vs-slide::after { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, rgba(var(--vs-brand-rgb), .85) 0%, rgba(var(--vs-brand-rgb), .15) 65%); }
        .vs-hero-text { position: relative; z-index: 2; height: 100%; display: flex; flex-direction: column; justify-content: center; gap: 16px; color: var(--vs-brand-text); }
        .vs-arrow { position: absolute; top: 50%; margin-top: -24px; width: 48px; height: 48px; border-radius: 999px; border: 0; background: rgba(255,255,255,.9); color: #0F172A; font-size: 18px; cursor: pointer; z-index: 3; }
        .vs-dots { position: absolute; left: 0; right: 0; bottom: 22px; display: flex; justify-content: center; gap: 8px; z-index: 3; }
        .vs-dots button { width: 8px; height: 8px; border-radius: 999px; background: rgba(255,255,255,.6); border: 0; padding: 0; cursor: pointer; }
        .vs-dots button.active { width: 28px; background: #F97316; }
        /* Media cards four per row (photo on top), with the map below them */
        .vs-wide { max-width: 1600px; }
        .vs-split { display: flex; flex-direction: column; gap: 32px; }
        #vsGrid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; }
        .vs-map-title { margin: 0 0 14px; font-size: 24px; font-weight: 800; }
        .vs-item { position: relative; display: flex; flex-direction: column; gap: 12px; padding: 12px;
            background: #fff; border: 2px solid transparent; border-radius: 12px; box-shadow: 0 4px 12px rgba(15, 23, 42, .12); transition: box-shadow .2s, border-color .2s; }
        /* The title link covers the whole card; the card's own buttons stay above it. */
        .vs-item-title a::after { content: ''; position: absolute; inset: 0; border-radius: 12px; z-index: 1; }
        .vs-item .vs-onmap, .vs-item .vs-read, .vs-item .vs-pill { position: relative; z-index: 2; }
        .vs-item .vs-ribbon { pointer-events: none; }
        .vs-item:hover { box-shadow: 0 8px 20px rgba(15, 23, 42, .18); }
        .vs-item.is-hot { border-color: #F97316; box-shadow: 0 0 0 3px rgba(15, 23, 42, .25), 0 8px 20px rgba(15, 23, 42, .18); }
        .vs-item-photo { position: relative; height: 180px; flex: none; border-radius: 8px; overflow: hidden; background: #F1F5F9; }
        .vs-item-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .vs-ribbon { position: absolute; top: 18px; left: -44px; width: 150px; padding: 6px 0; text-align: center; transform: rotate(-45deg); z-index: 2;
            font-size: 11px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: #fff; box-shadow: 0 4px 10px rgba(15, 23, 42, .3); }
        .vs-ribbon.is-long { top: 34px; left: -60px; width: 220px; font-size: 9px; letter-spacing: 0; }
        .vs-item-body { display: flex; flex: 1; flex-direction: column; gap: 7px; min-width: 0; padding: 0 4px 2px; }
        .vs-item-title { margin: 0; font-size: 18px; font-weight: 600; line-height: 1.3; }
        .vs-item-title a { color: #F97316; text-decoration: none; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
        .vs-item-code { font-size: 12px; font-weight: 700; color: #64748B; }
        .vs-item-meta { font-size: 14px; color: #0F172A; display: flex; flex-wrap: wrap; gap: 4px 20px; }
        .vs-item-price { font-size: 20px; font-weight: 700; color: #F97316; }
        .vs-item-price small { font-size: 13px; font-weight: 400; color: rgba(15, 23, 42, .55); }
        .vs-onmap { display: inline-flex; align-items: center; gap: 8px; align-self: flex-start; padding: 0; border: 0; background: none; cursor: pointer;
            font: 500 14px 'Montserrat', sans-serif; color: #334155; }
        .vs-onmap img { width: 24px; height: auto; }
        .vs-item-links { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 22px; }
        .vs-360 { text-decoration: none; }
        .vs-360:hover span { color: #F97316; }
        .vs-360-badge { position: absolute; right: 10px; bottom: 10px; z-index: 2; pointer-events: none; display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 999px; background: rgba(15, 23, 42, .78); color: #fff; font-size: 12px; font-weight: 700; }
        .vs-item-actions { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: auto; padding-top: 4px; }
        .vs-read { font-size: 14px; font-weight: 600; color: #F97316; text-decoration: none; white-space: nowrap; }
        .vs-pill { display: inline-flex; align-items: center; min-height: 38px; padding: 0 16px; border-radius: 30px; background: #F97316; color: #fff !important;
            font-weight: 600; font-size: 14px; white-space: nowrap; text-decoration: none; transition: transform .2s; }
        .vs-pill:hover { transform: scale(1.05); }
        .vs-mapbox { position: relative; border-radius: 10px; overflow: hidden; background: #fff; border: 1px solid #E5E7EB; }
        #vsMap { height: 520px; }
        .vs-loader { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 14px 0 18px; font-size: 14px; font-weight: 600; color: #475569; }
        .vs-spin { width: 22px; height: 22px; border-radius: 50%; border: 3px solid #FED7AA; border-top-color: #F97316; animation: vsSpin .8s linear infinite; }
        @keyframes vsSpin { to { transform: rotate(360deg); } }
        @media (max-width: 1240px) { #vsGrid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 1024px) {
            #vsGrid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            #vsMap { height: 440px; }
        }
        @media (max-width: 640px) {
            #vsGrid { grid-template-columns: minmax(0, 1fr); }
        }
        [hidden] { display: none !important; }
        .vs-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 20px; margin-bottom: 20px; }
        .vs-count { font-size: 14px; color: #475569; white-space: nowrap; }
        .vs-count b { color: #0F172A; }
        .vs-qsearch { display: flex; align-items: center; gap: 10px; padding: 6px 6px 6px 18px; background: #fff; border: 1px solid #CBD5E1; border-radius: 999px;
            box-shadow: 0 6px 18px rgba(15,23,42,.06); transition: border-color .15s, box-shadow .15s; }
        .vs-qsearch:focus-within { border-color: #F97316; box-shadow: 0 0 0 4px rgba(249,115,22,.15); }
        .vs-qsearch i { color: #94A3B8; }
        .vs-qsearch input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; font: 500 15px 'Montserrat', sans-serif; color: #0F172A; min-height: 40px; }
        .vs-qsearch button { border: 0; border-radius: 999px; background: #F97316; color: #fff; font: 700 14px 'Montserrat', sans-serif; padding: 0 22px; min-height: 42px; cursor: pointer; }
        .vs-qsearch button:hover { background: #EA580C; }

        /* Full filter panel (same filters as the main search). */
        .vs-filters { background: #fff; border: 1px solid #E5E7EB; border-radius: 16px; box-shadow: 0 12px 32px rgba(15, 23, 42, .1);
            padding: 20px 24px; display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 14px 16px; align-items: end; }
        .vs-filters .vs-input:disabled { background: #F8FAFC; color: #94A3B8; }
        .vs-filters-actions { display: flex; gap: 10px; grid-column: 1 / -1; justify-content: flex-end; flex-wrap: wrap; }
        .vs-multi { position: relative; }
        .vs-multi-btn { display: flex; align-items: center; justify-content: space-between; gap: 8px; text-align: left; cursor: pointer; }
        .vs-multi-text { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vs-multi-menu { position: absolute; left: 0; right: 0; top: calc(100% + 4px); z-index: 20; max-height: 220px; overflow: auto; padding: 6px;
            background: #fff; border: 1px solid #CBD5E1; border-radius: 10px; box-shadow: 0 12px 24px rgba(15, 23, 42, .15); }
        .vs-multi-menu label { display: flex; align-items: center; gap: 8px; padding: 6px 8px; border-radius: 6px; font-size: 14px; cursor: pointer; }
        .vs-multi-menu label:hover { background: #FFF7ED; }
        .vs-muted-sm { display: block; padding: 6px 8px; font-size: 13px; color: #64748B; }
        .vs-range-track { position: relative; height: 44px; }
        .vs-range-track::before { content: ''; position: absolute; left: 0; right: 0; top: 20px; height: 4px; border-radius: 4px; background: #E2E8F0; }
        .vs-range-track .fill { position: absolute; top: 20px; height: 4px; border-radius: 4px; background: #F97316; }
        .vs-range-track input[type=range] { position: absolute; left: 0; top: 12px; width: 100%; height: 20px; margin: 0; background: none; pointer-events: none; -webkit-appearance: none; appearance: none; }
        .vs-range-track input[type=range]::-webkit-slider-thumb { pointer-events: auto; -webkit-appearance: none; width: 18px; height: 18px; border-radius: 50%; background: #0F172A; border: 3px solid #fff; box-shadow: 0 0 0 1px #0F172A; cursor: pointer; }
        .vs-range-track input[type=range]::-moz-range-thumb { pointer-events: auto; width: 14px; height: 14px; border-radius: 50%; background: #0F172A; border: 3px solid #fff; cursor: pointer; }
        .vs-range-track input[type=range]::-webkit-slider-runnable-track { background: none; }
        .vs-range-vals { display: flex; justify-content: space-between; margin-top: -8px; font-size: 12px; font-weight: 600; color: #475569; }
        @media (max-width: 640px) { .vs-filters { padding: 16px; grid-template-columns: 1fr 1fr; } .vs-filters-actions .vs-btn { flex: 1; } }
        @media (max-width: 640px) { .vs-slider { height: 360px; } .vs-hero-text h1 { font-size: 30px !important; } }
    </style>
@endsection

@section('content')
    <section class="vs-slider" aria-label="Slider" aria-roledescription="carousel">
        @forelse ($slides as $s)
            <div class="vs-slide {{ $loop->first ? 'active' : '' }}" aria-hidden="{{ $loop->first ? 'false' : 'true' }}">
                @if ($s->media_type === 'video')
                    <video src="{{ $s->url }}" muted loop playsinline {{ $loop->first ? 'autoplay' : '' }}></video>
                @else
                    <img src="{{ $s->url }}" alt="{{ $s->title ?: $vendor->display_name }}">
                @endif
                <div class="vs-wrap vs-hero-text" style="position:absolute;inset:0">
                    <span class="vs-badge" style="align-self:flex-start;background:#F97316;color:#0F172A">{{ $totals['media'] }} media · {{ $totals['districts'] }} district{{ $totals['districts'] == 1 ? '' : 's' }}</span>
                    <h1 style="margin:0;font-size:46px;line-height:1.1;font-weight:800;max-width:640px">{{ $s->title ?: $vendor->display_name }}</h1>
                </div>
            </div>
        @empty
            <div class="vs-slide active" style="background:var(--vs-brand)">
                <div class="vs-wrap vs-hero-text" style="position:absolute;inset:0">
                    <span class="vs-badge" style="align-self:flex-start;background:#F97316;color:#0F172A">{{ $totals['media'] }} media · {{ $totals['districts'] }} district{{ $totals['districts'] == 1 ? '' : 's' }}</span>
                    <h1 style="margin:0;font-size:46px;line-height:1.1;font-weight:800;max-width:640px">{{ $vendor->display_name }}</h1>
                    <p style="margin:0;font-size:17px;color:var(--vs-brand-muted)">Outdoor media inventory · Powered by Brand Adda</p>
                </div>
            </div>
        @endforelse
        @if ($slides->count() > 1)
            <button type="button" class="vs-arrow" style="left:20px" aria-label="Previous slide" data-step="-1"><i class="fa-solid fa-chevron-left"></i></button>
            <button type="button" class="vs-arrow" style="right:20px" aria-label="Next slide" data-step="1"><i class="fa-solid fa-chevron-right"></i></button>
            <div class="vs-dots">
                @foreach ($slides as $s)<button type="button" class="{{ $loop->first ? 'active' : '' }}" aria-label="Slide {{ $loop->iteration }}" data-to="{{ $loop->index }}"></button>@endforeach
            </div>
        @endif
    </section>

    <div class="vs-wrap" style="position:relative;margin-top:-40px;z-index:5">
        {{-- Same filters as the main /search page, limited to this vendor's media
             (VendorSiteController::siteFilters → HomeRepository). Options list only what this vendor has. --}}
        @php
            $hasFilters = collect($filters)->filter(fn ($v) => filled($v))->isNotEmpty();
            $selLandmarks = $filters['landmark_ids'] ?? [];
            $o = $options;
        @endphp
        <form method="GET" action="{{ route('vendor-site.show', $vendor->slug) }}#inventory" aria-label="Filter inventory" id="vsFilters" class="vs-filters">
            <div><label class="vs-label" for="f-cat">Category</label>
                <select id="f-cat" name="category_id" class="vs-input"><option value="">Select Category</option>
                    @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(($filters['category_id'] ?? '') == $c->id)>{{ $c->category_name }}</option>@endforeach</select></div>
            <div><label class="vs-label" for="f-state">State</label>
                <select id="f-state" name="state_id" class="vs-input"><option value="">Select State</option>
                    @foreach ($o['states'] as $s)<option value="{{ $s->id }}" @selected(($filters['state_id'] ?? '') == $s->id)>{{ $s->state_name }}</option>@endforeach</select></div>
            <div><label class="vs-label" for="f-dis">District</label>
                <select id="f-dis" name="district_id" class="vs-input"><option value="">Select District</option>
                    @foreach ($districts as $d)<option value="{{ $d->id }}" data-parent="{{ $d->state_id }}" @selected(($filters['district_id'] ?? '') == $d->id)>{{ $d->district_name }}</option>@endforeach</select></div>
            <div><label class="vs-label" for="f-town">Town</label>
                <select id="f-town" name="city_id" class="vs-input"><option value="">Select Town</option>
                    @foreach ($towns as $t)<option value="{{ $t->id }}" data-parent="{{ $t->district_id }}" @selected(($filters['city_id'] ?? '') == $t->id)>{{ $t->city_name }}</option>@endforeach</select></div>
            <div><label class="vs-label" for="f-near">Nearby Landmark</label>
                <select id="f-near" name="nearby_place" class="vs-input" data-selected="{{ $filters['nearby_place'] ?? '' }}"><option value="">Select Town first</option></select>
                <input type="hidden" name="nearby_place_name" id="f-near-name" value="{{ $filters['nearby_place_name'] ?? '' }}"></div>
            <div><label class="vs-label" for="f-within">Within</label>
                <select id="f-within" name="nearby_distance" class="vs-input">
                    @foreach ($o['distances'] as $m)<option value="{{ $m }}" @selected(($filters['nearby_distance'] ?? 1000) == $m)>{{ $m >= 1000 ? ($m / 1000) . ' km' : $m . ' m' }}</option>@endforeach</select></div>
            <div><label class="vs-label" for="f-area">Area</label>
                <select id="f-area" name="area_id" class="vs-input"><option value="">Select Area</option>
                    @foreach ($o['areas'] as $a)<option value="{{ $a->id }}" data-parent="{{ $a->city_id }}" @selected(($filters['area_id'] ?? '') == $a->id)>{{ $a->area_name }}</option>@endforeach</select></div>
            <div><label class="vs-label" for="f-atype">Area Type</label>
                <select id="f-atype" name="areatype_id" class="vs-input"><option value="">Select Type</option>
                    @foreach ($o['areaTypes'] as $t)<option value="{{ $t->id }}" @selected(($filters['areatype_id'] ?? '') == $t->id)>{{ $t->areatype_name }}</option>@endforeach</select></div>
            <div><label class="vs-label" for="f-hw">Highway</label>
                <select id="f-hw" name="highway_id" class="vs-input"><option value="">Select Highway</option>
                    @foreach ($o['highways'] as $h)<option value="{{ $h->id }}" @selected(($filters['highway_id'] ?? '') == $h->id)>{{ $h->highway_name }}</option>@endforeach</select></div>
            <div class="vs-multi" id="f-lm">
                <span class="vs-label">Landmarks</span>
                <button type="button" class="vs-input vs-multi-btn" aria-haspopup="true" aria-expanded="false">
                    <span class="vs-multi-text">Select Landmarks</span><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></button>
                <div class="vs-multi-menu" hidden>
                    @forelse ($o['landmarks'] as $l)
                        <label><input type="checkbox" name="landmark_ids[]" value="{{ $l->id }}" @checked(in_array($l->id, $selLandmarks))> {{ $l->landmark_name }}</label>
                    @empty
                        <span class="vs-muted-sm">No landmarks tagged</span>
                    @endforelse
                </div>
            </div>
            <div><label class="vs-label" for="f-from">From Date</label>
                <input id="f-from" type="date" name="from_date" class="vs-input" value="{{ $filters['from_date'] ?? '' }}" min="{{ now()->toDateString() }}"></div>
            <div><label class="vs-label" for="f-to">To Date</label>
                <input id="f-to" type="date" name="to_date" class="vs-input" value="{{ $filters['to_date'] ?? '' }}" min="{{ now()->toDateString() }}"></div>
            <div><label class="vs-label" for="f-days">Available Days</label>
                <select id="f-days" name="available_days" class="vs-input"><option value="">Select Days</option>
                    @foreach ([0 => 'Instantly Available', 7 => 'Available After 7 Days', 15 => 'Available After 15 Days'] as $v => $lbl)
                        <option value="{{ $v }}" @selected(isset($filters['available_days']) && (int) $filters['available_days'] === $v)>{{ $lbl }}</option>
                    @endforeach</select></div>
            <div><label class="vs-label" for="f-code">Sector Code</label>
                <input id="f-code" type="text" name="hoarding_code" class="vs-input" placeholder="e.g. HD000034" value="{{ $filters['hoarding_code'] ?? '' }}"></div>

            {{-- Range sliders: the hidden min/max inputs are only sent once a handle moves off its end. --}}
            <div class="vs-range" data-unit="sqft" data-min="{{ $o['area']['min'] }}" data-max="{{ max($o['area']['max'], $o['area']['min'] + 1) }}">
                <span class="vs-label">Media Size (sq.ft)</span>
                <div class="vs-range-track"><input type="range" class="lo" aria-label="Minimum size"><input type="range" class="hi" aria-label="Maximum size"></div>
                <div class="vs-range-vals"><span class="lo-v"></span><span class="hi-v"></span></div>
                <input type="hidden" name="min_area" value="{{ $filters['min_area'] ?? '' }}"><input type="hidden" name="max_area" value="{{ $filters['max_area'] ?? '' }}">
            </div>
            <div class="vs-range" data-unit="km" data-any="1" data-min="0" data-max="{{ max(1, $o['radiusMax']) }}">
                <span class="vs-label">Radius <small style="font-weight:500;color:#64748B">(from the town)</small></span>
                <div class="vs-range-track"><input type="range" class="lo" aria-label="Minimum radius"><input type="range" class="hi" aria-label="Maximum radius"></div>
                <div class="vs-range-vals"><span class="lo-v"></span><span class="hi-v"></span></div>
                <input type="hidden" name="min_radius" value="{{ $filters['min_radius'] ?? '' }}"><input type="hidden" name="max_radius" value="{{ $filters['max_radius'] ?? '' }}">
            </div>
            <div class="vs-range" data-unit="₹" data-min="{{ $o['price']['min'] }}" data-max="{{ max($o['price']['max'], $o['price']['min'] + 1) }}">
                <span class="vs-label">Budget <small style="font-weight:500;color:#64748B">(per month)</small></span>
                <div class="vs-range-track"><input type="range" class="lo" aria-label="Minimum budget"><input type="range" class="hi" aria-label="Maximum budget"></div>
                <div class="vs-range-vals"><span class="lo-v"></span><span class="hi-v"></span></div>
                <input type="hidden" name="min_price" value="{{ $filters['min_price'] ?? '' }}"><input type="hidden" name="max_price" value="{{ $filters['max_price'] ?? '' }}">
            </div>

            @if (!empty($filters['q']))<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
            @if (!empty($filters['available']))<input type="hidden" name="available" value="1">@endif
            <div class="vs-filters-actions">
                <button type="submit" class="vs-btn vs-btn-o"><i class="fa-solid fa-magnifying-glass"></i>Search Media</button>
                @if ($hasFilters)
                    <a href="{{ route('vendor-site.show', $vendor->slug) }}#inventory" class="vs-btn vs-btn-g" title="Clear all filters">
                        <i class="fa-solid fa-rotate-right"></i>Clear Filters</a>
                @endif
            </div>
        </form>
        <script type="application/json" id="vsNearby">@json($o['nearby'])</script>
    </div>

    <section id="inventory" class="vs-wrap vs-wide" style="padding-top:44px;padding-bottom:56px">
        {{-- One row: quick search · count · All / Available now. --}}
        <div class="vs-toolbar">
            {{-- Quick search over this vendor's media; keeps the other filters. --}}
            <div style="flex:1 1 420px;max-width:640px">
                <form method="GET" action="{{ route('vendor-site.show', $vendor->slug) }}#inventory" role="search" class="vs-qsearch">
                    @foreach (\Illuminate\Support\Arr::except($filters, 'q') as $k => $v)
                        @if (is_array($v))
                            @foreach ($v as $item)<input type="hidden" name="{{ $k }}[]" value="{{ $item }}">@endforeach
                        @elseif (filled($v))
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endif
                    @endforeach
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <label for="vsQuick" class="sr-only">Search media</label>
                    <input id="vsQuick" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search by area, landmark, title or code">
                    <button type="submit">Search</button>
                </form>
            </div>
            <div class="vs-count">
                Showing <b>{{ $media->count() }}</b> of {{ $totals['media'] }} media
                @if ($hasFilters)
                    · <a href="{{ route('vendor-site.show', $vendor->slug) }}#inventory" style="font-weight:700">Clear filters</a>
                @endif
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-left:auto">
                <a href="{{ route('vendor-site.show', array_merge(['vendorSlug' => $vendor->slug], \Illuminate\Support\Arr::except($filters, 'available'))) }}#inventory" class="vs-btn {{ empty($filters['available']) ? 'vs-btn-n' : 'vs-btn-g' }}" style="min-height:40px;border-radius:999px">All</a>
                <a href="{{ route('vendor-site.show', array_merge(['vendorSlug' => $vendor->slug], $filters, ['available' => 1])) }}#inventory" class="vs-btn {{ !empty($filters['available']) ? 'vs-btn-n' : 'vs-btn-g' }}" style="min-height:40px;border-radius:999px">Available now</a>
            </div>
        </div>

        <div class="vs-split">
        <div class="vs-list" id="vsList">
        {{-- Brand Adda team only (as on /search): tick every card loaded so far. More cards
             load as the list scrolls, hence "loaded". --}}
        @if (site_admin() && $media->isNotEmpty())
            <div class="share-toolbar" style="margin:0 0 14px">
                <label class="share-all">
                    <input type="checkbox" id="shareSelectAll">
                    <span>Select all loaded</span>
                </label>
                <span class="share-toolbar-hint" id="shareToolbarHint"></span>
            </div>
        @endif
        <div id="vsGrid" aria-live="polite">
            @if ($media->isEmpty())
                <p style="color:#475569">No media matches these filters.</p>
            @else
                @include('website.vendor-site.partials.inventory-cards', ['items' => $firstItems])
            @endif
        </div>
        @if ($media->count() > $firstItems->count())
            {{-- Reaching this loads the next {{ $perPage }} cards, as on the Brand Adda search page. --}}
            <div id="vsMoreSentinel" data-next="2" aria-hidden="true" style="height:1px"></div>
            <div id="vsLoader" class="vs-loader" hidden><span class="vs-spin" aria-hidden="true"></span>Loading more media…</div>
        @endif
        </div>

        <aside id="map" aria-label="Location map">
            <h2 class="vs-map-title">Location Map</h2>
            <div class="vs-mapbox">
                <div id="vsMap" role="region" aria-label="Map of {{ $vendor->display_name }} sites"></div>
                <div class="vs-legend" aria-label="Legend">
                    @foreach (\App\Support\MediaAvailability::LABELS as $k => $l)
                        <span><i style="background:{{ ['available' => '#16A34A', 'booked' => '#2563EB', 'blocked' => '#DC2626', 'ask' => '#D97706'][$k] }}"></i>{{ $l }}</span>
                    @endforeach
                </div>
            </div>
        </aside>
        </div>
    </section>

    {{-- Shortlist bar + share dialog (Share selected / PPT / Excel): the same one /search uses.
         Renders only for a logged-in Brand Adda admin; its endpoints check that again. --}}
    @include('website.includes.share-link')

@endsection

@section('scripts')
    @if (site_admin())
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <link rel="stylesheet" href="{{ asset('asset/css/shortlist.css') }}?v={{ @filemtime(public_path('asset/css/shortlist.css')) }}">
        <script>
            // Ticks on the vendor cards feed the shared shortlist (window.BrandAddaShare),
            // exactly as the cards on /search do.
            (function () {
                const share = window.BrandAddaShare;
                if (!share) return;
                const grid = document.getElementById('vsGrid');
                const selectAll = document.getElementById('shareSelectAll');
                const hintEl = document.getElementById('shareToolbarHint');
                const boxes = () => Array.from(grid.querySelectorAll('.share-pick-input'));

                function setBox(box, on) {
                    box.checked = on;
                    share.toggle(box.value, on);
                    box.closest('.vs-item')?.classList.toggle('is-picked', on);
                }

                // Master tick: checked when every loaded card is picked, partial otherwise.
                share.onRender(function () {
                    const all = boxes(), ticked = all.filter(b => b.checked).length;
                    if (selectAll) {
                        selectAll.checked = all.length > 0 && ticked === all.length;
                        selectAll.indeterminate = ticked > 0 && ticked < all.length;
                    }
                    if (hintEl) hintEl.textContent = all.length ? ticked + ' of ' + all.length + ' selected' : '';
                });
                share.onClear(function () {
                    boxes().forEach(function (b) { b.checked = false; b.closest('.vs-item')?.classList.remove('is-picked'); });
                });
                if (selectAll) {
                    selectAll.addEventListener('change', function () {
                        const on = selectAll.checked; // read once: each tick re-renders the master box
                        boxes().forEach(b => setBox(b, on));
                    });
                }
                // (The tick sits above the card's full-card link, so ticking never opens the card.)
                grid.addEventListener('change', function (e) {
                    const box = e.target.closest('.share-pick-input');
                    if (box) setBox(box, box.checked);
                });

                // Cards loaded while scrolling: show ticks for ones already shortlisted, update the hint.
                new MutationObserver(function () {
                    boxes().forEach(function (b) {
                        if (share.has(b.value) && !b.checked) { b.checked = true; b.closest('.vs-item')?.classList.add('is-picked'); }
                    });
                    share.refresh();
                }).observe(grid, { childList: true });
                share.refresh();
            })();
        </script>
    @endif
    @include('website.vendor-site.partials.map-kit')
    <script>
        // ---------- Filter panel ----------
        (function () {
            const form = document.getElementById('vsFilters');
            if (!form) return;
            const $ = (s) => form.querySelector(s);

            // State → District → Town → Area: each list shows only the children of the chosen parent.
            function chain(parentSel, childSel) {
                const parent = $(parentSel), child = $(childSel);
                function sync() {
                    const p = parent.value;
                    child.querySelectorAll('option[data-parent]').forEach(function (o) {
                        const show = !p || o.dataset.parent === p;
                        o.hidden = !show; o.disabled = !show;
                    });
                    if (child.selectedOptions[0] && child.selectedOptions[0].disabled) { child.value = ''; child.dispatchEvent(new Event('change')); }
                }
                parent.addEventListener('change', sync);
                sync();
            }
            chain('#f-state', '#f-dis');
            chain('#f-dis', '#f-town');
            chain('#f-town', '#f-area');

            // Nearby Landmark: places around the chosen town (sent with the page).
            const nearby = JSON.parse(document.getElementById('vsNearby').textContent || '{}');
            const near = $('#f-near'), nearName = $('#f-near-name'), within = $('#f-within'), town = $('#f-town');
            function fillNearby() {
                const list = nearby[town.value] || [];
                const keep = near.value || near.dataset.selected;
                near.innerHTML = '';
                const first = document.createElement('option');
                first.value = '';
                first.textContent = !town.value ? 'Select Town first' : (list.length ? 'Select Landmark' : 'No nearby places saved');
                near.appendChild(first);
                list.forEach(function (p) {
                    const o = document.createElement('option');
                    o.value = p.value; o.textContent = p.name; o.dataset.name = p.name;
                    if (p.value === keep) o.selected = true;
                    near.appendChild(o);
                });
                near.disabled = !town.value || !list.length;
                near.dataset.selected = '';
                syncNearName();
            }
            function syncNearName() {
                const o = near.selectedOptions[0];
                nearName.value = o && o.value ? o.dataset.name : '';
                within.disabled = !near.value;
            }
            town.addEventListener('change', fillNearby);
            near.addEventListener('change', syncNearName);
            fillNearby();

            // Landmarks: checkbox dropdown.
            const lm = document.getElementById('f-lm'), lmBtn = lm.querySelector('.vs-multi-btn'), lmMenu = lm.querySelector('.vs-multi-menu'), lmText = lm.querySelector('.vs-multi-text');
            function lmLabel() {
                const names = Array.from(lmMenu.querySelectorAll('input:checked')).map(i => i.parentElement.textContent.trim());
                lmText.textContent = names.length === 0 ? 'Select Landmarks' : (names.length <= 2 ? names.join(', ') : names.length + ' landmarks selected');
            }
            lmBtn.addEventListener('click', function () { lmMenu.hidden = !lmMenu.hidden; lmBtn.setAttribute('aria-expanded', String(!lmMenu.hidden)); });
            document.addEventListener('click', function (e) { if (!lm.contains(e.target)) { lmMenu.hidden = true; lmBtn.setAttribute('aria-expanded', 'false'); } });
            lmMenu.addEventListener('change', lmLabel);
            lmLabel();

            // Dates: To Date never before From Date.
            const from = $('#f-from'), to = $('#f-to');
            from.addEventListener('change', function () { to.min = from.value || to.getAttribute('min'); if (to.value && from.value && to.value < from.value) to.value = from.value; });

            // Two-handle range sliders (size, radius, budget).
            form.querySelectorAll('.vs-range').forEach(function (box) {
                const min = +box.dataset.min, max = +box.dataset.max, unit = box.dataset.unit, any = box.dataset.any === '1';
                const lo = box.querySelector('.lo'), hi = box.querySelector('.hi');
                const hidLo = box.querySelectorAll('input[type=hidden]')[0], hidHi = box.querySelectorAll('input[type=hidden]')[1];
                const loV = box.querySelector('.lo-v'), hiV = box.querySelector('.hi-v');
                const track = box.querySelector('.vs-range-track'), fill = document.createElement('span');
                fill.className = 'fill'; track.prepend(fill);
                // Step 1 so both ends are exactly reachable (a coarser step stopped short of max
                // and quietly sent a max that dropped the largest media).
                const step = 1;
                [lo, hi].forEach(function (r) { r.min = min; r.max = max; r.step = step; });
                lo.value = hidLo.value !== '' ? Math.max(min, Math.min(max, +hidLo.value)) : min;
                hi.value = hidHi.value !== '' ? Math.max(min, Math.min(max, +hidHi.value)) : max;
                const fmt = function (v) {
                    if (unit === '₹') return '₹ ' + Number(v).toLocaleString('en-IN');
                    if (unit === 'km') return (any && +v === 0) ? 'Any' : v + ' KM';
                    return Number(v).toLocaleString('en-IN') + ' sqft';
                };
                function update(e) {
                    if (+lo.value > +hi.value) { if (e && e.target === lo) lo.value = hi.value; else hi.value = lo.value; }
                    const a = (+lo.value - min) / (max - min) * 100, b = (+hi.value - min) / (max - min) * 100;
                    fill.style.left = a + '%'; fill.style.width = (b - a) + '%';
                    loV.textContent = fmt(lo.value); hiV.textContent = fmt(hi.value);
                    // Only filter on an end that was moved; untouched ends send nothing.
                    hidLo.value = +lo.value > min ? lo.value : '';
                    hidHi.value = +hi.value < max ? hi.value : '';
                    lo.style.zIndex = (+lo.value >= max - step) ? 3 : 2; // keep the low handle reachable at the far end
                }
                lo.addEventListener('input', update); hi.addEventListener('input', update);
                update();
            });

            // Do not send empty fields: keeps the address short and readable.
            form.addEventListener('submit', function () {
                form.querySelectorAll('select, input').forEach(function (el) {
                    if (el.type === 'checkbox' || el.type === 'range') { if (el.type === 'range') el.disabled = true; return; }
                    if (el.value === '') el.disabled = true;
                });
                if (!near.value) within.disabled = true;
            });
        })();

        (function () {
            // Slider
            const slides = Array.from(document.querySelectorAll('.vs-slide'));
            const dots = Array.from(document.querySelectorAll('.vs-dots button'));
            let current = 0, timer = null;
            function go(n) {
                if (slides.length < 2) return;
                slides[current].classList.remove('active');
                slides[current].setAttribute('aria-hidden', 'true');
                const oldVideo = slides[current].querySelector('video'); if (oldVideo) oldVideo.pause();
                current = (n + slides.length) % slides.length;
                slides[current].classList.add('active');
                slides[current].setAttribute('aria-hidden', 'false');
                const video = slides[current].querySelector('video'); if (video) { video.currentTime = 0; video.play().catch(function () {}); }
                dots.forEach(function (d, i) { d.classList.toggle('active', i === current); });
                restart();
            }
            function restart() { clearInterval(timer); timer = setInterval(function () { go(current + 1); }, 6000); }
            document.querySelectorAll('.vs-arrow').forEach(function (b) { b.addEventListener('click', function () { go(current + Number(b.dataset.step)); }); });
            dots.forEach(function (d) { d.addEventListener('click', function () { go(Number(d.dataset.to)); }); });
            if (slides.length > 1) restart();

            // Map below the cards.
            const kit = VsMap.create('vsMap', @json($mapPoints), { wheel: false, onPin: function (id) { select(id, false); } });
            const map = kit.map, cluster = kit.cluster, markers = kit.markers;

            const grid = document.getElementById('vsGrid');
            // Cards loading above the map would push it away mid-jump, so loading waits while jumping to it.
            let holdUntil = 0;
            function holdLoading() { holdUntil = Date.now() + 2000; }
            if (location.hash === '#map') holdLoading();
            const cardEl = function (id) { return grid.querySelector('.vs-item[data-id="' + id + '"]'); };
            let selected = null;

            function select(id, fromList) {
                if (selected && markers[selected]) { markers[selected].setIcon(markers[selected]._plain); markers[selected].setZIndexOffset(0); }
                selected = String(id);
                grid.querySelectorAll('.vs-item.is-hot').forEach(function (c) { c.classList.remove('is-hot'); });
                const card = cardEl(selected);
                // Outline the card; a pin click leaves the page on the map.
                if (card) card.classList.add('is-hot');
                const mk = markers[selected];
                if (!mk) return;
                mk.setIcon(mk._big); mk.setZIndexOffset(1000);
                if (fromList) { holdLoading(); document.getElementById('map').scrollIntoView({ behavior: 'smooth', block: 'start' }); }
                if (fromList) cluster.zoomToShowLayer(mk, function () { map.panTo(mk.getLatLng()); mk.openPopup(); });
            }

            // Start with every pin in view.
            const allBounds = Object.keys(markers).map(function (id) { return markers[id].getLatLng(); });
            function fitAll() { if (allBounds.length) map.fitBounds(allBounds, { padding: [50, 50], maxZoom: 14 }); }

            // The wheel scrolls the page; it zooms the map only after the map is clicked.
            map.on('click', function () { map.scrollWheelZoom.enable(); });
            map.on('mouseout', function () { map.scrollWheelZoom.disable(); });

            // "View on Map" scrolls to the map and selects the pin. Delegated, so cards loaded later work too.
            grid.addEventListener('click', function (e) {
                const btn = e.target.closest('.vs-onmap');
                if (btn) select(btn.dataset.map, true);
            });

            // Lazy loading: when the end of the list scrolls into view, fetch the next page
            // of cards from the server (same filters) and append it.
            const sentinel = document.getElementById('vsMoreSentinel');
            const loader = document.getElementById('vsLoader');
            if (sentinel) {
                let loading = false;
                const io = new IntersectionObserver(function (entries) {
                    if (!entries[0].isIntersecting || loading || Date.now() < holdUntil) return;
                    loading = true;
                    loader.hidden = false;
                    const url = new URL(window.location.href);
                    url.hash = '';
                    url.searchParams.set('page', sentinel.dataset.next);
                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
                        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
                        .then(function (html) {
                            loader.hidden = true;
                            if (!html.trim()) { io.disconnect(); sentinel.remove(); return; }
                            grid.insertAdjacentHTML('beforeend', html);
                            sentinel.dataset.next = Number(sentinel.dataset.next) + 1;
                            if (selected) { const c = cardEl(selected); if (c) c.classList.add('is-hot'); }
                            loading = false;
                            // Still at the end (short list or tall screen)? Load the next page too.
                            io.unobserve(sentinel); io.observe(sentinel);
                        })
                        .catch(function () { loader.hidden = true; loading = false; });
                }, { rootMargin: '0px 0px 200px 0px' });
                io.observe(sentinel);
            }

            fitAll();
            let fitted = false;
            new ResizeObserver(function () { map.invalidateSize(); if (!fitted) { fitted = true; fitAll(); } }).observe(document.getElementById('vsMap'));
        })();
    </script>
@endsection

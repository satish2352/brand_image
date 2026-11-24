@extends('website.vendor-site.layout')
@section('title', $vendor->vendor_name)
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
        /* List of media (left) and map (right) in one row, like the Brand Adda search page */
        .vs-wide { max-width: 1600px; }
        .vs-split { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 24px; align-items: start; }
        .vs-list { height: 78vh; min-height: 560px; overflow-y: auto; padding: 4px 10px 4px 4px; scrollbar-width: thin; }
        .vs-item { position: relative; display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); gap: 18px; padding: 12px; margin-bottom: 16px;
            background: #fff; border: 2px solid transparent; border-radius: 12px; box-shadow: 0 4px 12px rgba(15, 23, 42, .12); cursor: pointer; transition: box-shadow .2s, border-color .2s; }
        .vs-item:hover { box-shadow: 0 8px 20px rgba(15, 23, 42, .18); }
        .vs-item.is-hot { border-color: #F97316; box-shadow: 0 0 0 3px rgba(15, 23, 42, .25), 0 8px 20px rgba(15, 23, 42, .18); }
        .vs-item-photo { position: relative; height: 200px; border-radius: 8px; overflow: hidden; background: #F1F5F9; }
        .vs-item-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .vs-ribbon { position: absolute; top: 18px; left: -44px; width: 150px; padding: 6px 0; text-align: center; transform: rotate(-45deg); z-index: 2;
            font-size: 11px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: #fff; box-shadow: 0 4px 10px rgba(15, 23, 42, .3); }
        .vs-ribbon.is-long { top: 34px; left: -60px; width: 220px; font-size: 9px; letter-spacing: 0; }
        .vs-item-body { display: flex; flex-direction: column; gap: 8px; min-width: 0; padding: 4px 4px 4px 0; }
        .vs-item-title { margin: 0; font-size: 21px; font-weight: 600; line-height: 1.3; }
        .vs-item-title a { color: #F97316; text-decoration: none; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
        .vs-item-code { font-size: 12px; font-weight: 700; color: #64748B; }
        .vs-item-meta { font-size: 15px; color: #0F172A; display: flex; flex-wrap: wrap; gap: 4px 20px; }
        .vs-item-price { font-size: 20px; font-weight: 700; color: #F97316; }
        .vs-item-price small { font-size: 13px; font-weight: 400; color: rgba(15, 23, 42, .55); }
        .vs-onmap { display: inline-flex; align-items: center; gap: 8px; align-self: flex-start; padding: 0; border: 0; background: none; cursor: pointer;
            font: 500 15px 'Montserrat', sans-serif; color: #334155; }
        .vs-onmap img { width: 28px; height: auto; }
        .vs-item-actions { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: auto; }
        .vs-read { font-weight: 600; color: #F97316; text-decoration: none; padding-left: 4px; }
        .vs-pill { display: inline-flex; align-items: center; min-height: 38px; padding: 0 18px; border-radius: 30px; background: #F97316; color: #fff !important;
            font-weight: 600; font-size: 15px; text-decoration: none; transition: transform .2s; }
        .vs-pill:hover { transform: scale(1.05); }
        .vs-mapbox { position: relative; border-radius: 10px; overflow: hidden; background: #fff; border: 1px solid #E5E7EB; }
        #vsMap { height: 78vh; min-height: 560px; }
        .vs-legend { position: absolute; left: 12px; bottom: 12px; z-index: 500; background: rgba(255,255,255,.96); border: 1px solid #E5E7EB; border-radius: 12px;
            padding: 10px 12px; display: grid; grid-template-columns: repeat(2, auto); gap: 6px 16px; font-size: 12px; font-weight: 600; box-shadow: 0 6px 16px rgba(15,23,42,.12); }
        .vs-legend span { display: inline-flex; align-items: center; gap: 8px; }
        .vs-legend i { width: 12px; height: 12px; border-radius: 999px; display: inline-block; }
        .vs-pin { background: transparent; border: 0; }
        .vs-pin.is-selected { z-index: 1000 !important; }
        .vs-pin.is-selected svg { animation: vsBounce .7s ease infinite alternate; transform-origin: center bottom; }
        @keyframes vsBounce { from { transform: translateY(0); } to { transform: translateY(-7px); } }
        .vs-cluster { background: transparent; }
        .vs-cluster div { width: 100%; height: 100%; border-radius: 50%; background: #F97316; border: 3px solid #fff; box-shadow: 0 2px 6px rgba(15,23,42,.35);
            display: flex; align-items: center; justify-content: center; color: #fff; font: 700 14px 'Montserrat', sans-serif; }
        .vs-loader { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 14px 0 18px; font-size: 14px; font-weight: 600; color: #475569; }
        .vs-spin { width: 22px; height: 22px; border-radius: 50%; border: 3px solid #FED7AA; border-top-color: #F97316; animation: vsSpin .8s linear infinite; }
        @keyframes vsSpin { to { transform: rotate(360deg); } }
        @media (max-width: 1024px) {
            .vs-split { grid-template-columns: 1fr; }
            .vs-list { height: auto; min-height: 0; overflow: visible; padding: 0; }
            #vsMap { height: 440px; min-height: 0; }
        }
        @media (max-width: 640px) {
            .vs-item { grid-template-columns: 1fr; }
            .vs-item-title { font-size: 18px; }
        }
        .vs-pop { width: 220px; font-family: 'Montserrat', sans-serif; }
        .vs-pop img { width: 100%; height: 110px; object-fit: cover; border-radius: 8px; display: block; margin-bottom: 8px; }
        .vs-pop a.vs-btn { min-height: 34px; margin-top: 8px; font-size: 12px; color: #0F172A; width: 100%; }
        .leaflet-popup-content { margin: 12px; }
        [hidden] { display: none !important; }
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
                    <img src="{{ $s->url }}" alt="{{ $s->title ?: $vendor->vendor_name }}">
                @endif
                <div class="vs-wrap vs-hero-text" style="position:absolute;inset:0">
                    <span class="vs-badge" style="align-self:flex-start;background:#F97316;color:#0F172A">{{ $totals['media'] }} media · {{ $totals['districts'] }} district{{ $totals['districts'] == 1 ? '' : 's' }}</span>
                    <h1 style="margin:0;font-size:46px;line-height:1.1;font-weight:800;max-width:640px">{{ $s->title ?: $vendor->vendor_name }}</h1>
                </div>
            </div>
        @empty
            <div class="vs-slide active" style="background:var(--vs-brand)">
                <div class="vs-wrap vs-hero-text" style="position:absolute;inset:0">
                    <span class="vs-badge" style="align-self:flex-start;background:#F97316;color:#0F172A">{{ $totals['media'] }} media · {{ $totals['districts'] }} district{{ $totals['districts'] == 1 ? '' : 's' }}</span>
                    <h1 style="margin:0;font-size:46px;line-height:1.1;font-weight:800;max-width:640px">{{ $vendor->vendor_name }}</h1>
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
        <form method="GET" action="{{ route('vendor-site.show', $vendor->slug) }}#inventory" aria-label="Filter inventory"
            style="background:#fff;border:1px solid #E5E7EB;border-radius:16px;box-shadow:0 12px 32px rgba(15,23,42,.1);padding:20px 24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;align-items:end">
            <div><label class="vs-label" for="c">Category</label>
                <select id="c" name="category_id" class="vs-input"><option value="">All categories</option>
                    @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(($filters['category_id'] ?? '') == $c->id)>{{ $c->category_name }}</option>@endforeach</select></div>
            <div><label class="vs-label" for="d">District</label>
                <select id="d" name="district_id" class="vs-input"><option value="">All districts</option>
                    @foreach ($districts as $d)<option value="{{ $d->id }}" @selected(($filters['district_id'] ?? '') == $d->id)>{{ $d->district_name }}</option>@endforeach</select></div>
            <div><label class="vs-label" for="t">Town</label>
                <select id="t" name="city_id" class="vs-input"><option value="">All towns</option>
                    @foreach ($towns as $t)<option value="{{ $t->id }}" @selected(($filters['city_id'] ?? '') == $t->id)>{{ $t->city_name }}</option>@endforeach</select></div>
            <div><label class="vs-label" for="s">Search</label><input id="s" type="search" name="q" class="vs-input" placeholder="Area, landmark or code" value="{{ $filters['q'] ?? '' }}"></div>
            @php $hasFilters = collect($filters)->filter(fn($v) => filled($v))->isNotEmpty(); @endphp
            <div style="display:flex;gap:10px">
                <button type="submit" class="vs-btn vs-btn-n" style="flex:1"><i class="fa-solid fa-magnifying-glass"></i>Search</button>
                @if ($hasFilters)
                    <a href="{{ route('vendor-site.show', $vendor->slug) }}#inventory" class="vs-btn vs-btn-g" style="flex:1" title="Clear all filters">
                        <i class="fa-solid fa-rotate-right"></i>Reset</a>
                @endif
            </div>
        </form>
    </div>

    <section id="inventory" class="vs-wrap vs-wide" style="padding-top:44px;padding-bottom:56px">
        <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-end;gap:12px;margin-bottom:20px">
            <div>
                <h2 style="margin:0;font-size:28px;font-weight:800">Our Inventory</h2>
                <div style="font-size:14px;color:#475569;margin-top:6px">
                    Showing {{ $media->count() }} of {{ $totals['media'] }} media
                    @if ($hasFilters)
                        · <a href="{{ route('vendor-site.show', $vendor->slug) }}#inventory" style="font-weight:700">Clear filters</a>
                    @endif
                </div>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
                <a href="{{ route('vendor-site.show', array_merge(['vendorSlug' => $vendor->slug], \Illuminate\Support\Arr::except($filters, 'available'))) }}#inventory" class="vs-btn {{ empty($filters['available']) ? 'vs-btn-n' : 'vs-btn-g' }}" style="min-height:40px;border-radius:999px">All</a>
                <a href="{{ route('vendor-site.show', array_merge(['vendorSlug' => $vendor->slug], $filters, ['available' => 1])) }}#inventory" class="vs-btn {{ !empty($filters['available']) ? 'vs-btn-n' : 'vs-btn-g' }}" style="min-height:40px;border-radius:999px">Available now</a>
            </div>
        </div>

        <div class="vs-split">
        <div class="vs-list" id="vsList">
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
            <div class="vs-mapbox">
                <div id="vsMap" role="region" aria-label="Map of {{ $vendor->vendor_name }} sites"></div>
                <div class="vs-legend" aria-label="Legend">
                    @foreach (\App\Support\MediaAvailability::LABELS as $k => $l)
                        <span><i style="background:{{ ['available' => '#16A34A', 'booked' => '#2563EB', 'blocked' => '#DC2626', 'ask' => '#D97706'][$k] }}"></i>{{ $l }}</span>
                    @endforeach
                </div>
            </div>
        </aside>
        </div>
    </section>

@endsection

@section('scripts')
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script>
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

            const points = @json($mapPoints);
            const colours = { available: '#16A34A', booked: '#2563EB', blocked: '#DC2626', ask: '#D97706' };
            const esc = function (s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };
            // Map beside the list: orange clusters with the count, a teardrop pin per media
            // (coloured by availability), and the selected one in navy, bouncing.
            const map = L.map('vsMap', { scrollWheelZoom: true }).setView([19.0, 75.0], 7);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);

            function pinIcon(color, big) {
                const w = big ? 40 : 30, h = big ? 54 : 42;
                const svg = '<svg width="' + w + '" height="' + h + '" viewBox="0 0 32 44" xmlns="http://www.w3.org/2000/svg" style="overflow:visible;filter:drop-shadow(0 3px 3px rgba(15,23,42,.4))">'
                    + '<path d="M16 0C7.16 0 0 7.16 0 16c0 11.5 16 28 16 28s16-16.5 16-28C32 7.16 24.84 0 16 0z" fill="' + color + '" stroke="#fff" stroke-width="2.5"/>'
                    + '<circle cx="16" cy="16" r="6" fill="#fff"/></svg>';
                return L.divIcon({ className: 'vs-pin' + (big ? ' is-selected' : ''), html: svg, iconSize: [w, h], iconAnchor: [w / 2, h], popupAnchor: [0, -h + 8] });
            }

            const cluster = L.markerClusterGroup({
                showCoverageOnHover: false, spiderfyOnMaxZoom: true, maxClusterRadius: 40,
                iconCreateFunction: function (c) {
                    const n = c.getChildCount(), size = n < 10 ? 36 : (n < 100 ? 42 : 50);
                    return L.divIcon({ html: '<div>' + n + '</div>', className: 'vs-cluster', iconSize: L.point(size, size) });
                }
            });
            const markers = {};
            points.forEach(function (p) {
                const photo = p.img ? '<img src="' + esc(p.img) + '" alt="">' : '';
                const mk = L.marker([p.lat, p.lng], { icon: pinIcon(colours[p.status] || '#F97316', false) })
                    .bindPopup('<div class="vs-pop">' + photo + '<strong>' + esc(p.title) + '</strong><br><span style="color:#475569">' + esc(p.meta)
                        + '</span><br><span style="font-weight:800;color:#F97316">' + esc(p.price) + '</span><a class="vs-btn vs-btn-o" href="' + esc(p.url) + '">View details</a></div>');
                mk._plain = mk.options.icon;
                mk._big = pinIcon('#0F172A', true);
                mk.on('click', function () { select(p.id, false); });
                markers[p.id] = mk;
                cluster.addLayer(mk);
            });
            map.addLayer(cluster);

            const grid = document.getElementById('vsGrid');
            const cardEl = function (id) { return grid.querySelector('.vs-item[data-id="' + id + '"]'); };
            let selected = null;

            function select(id, fromList) {
                if (selected && markers[selected]) { markers[selected].setIcon(markers[selected]._plain); markers[selected].setZIndexOffset(0); }
                selected = String(id);
                grid.querySelectorAll('.vs-item.is-hot').forEach(function (c) { c.classList.remove('is-hot'); });
                const card = cardEl(selected);
                if (card) {
                    card.classList.add('is-hot');
                    if (!fromList) card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
                const mk = markers[selected];
                if (!mk) return;
                mk.setIcon(mk._big); mk.setZIndexOffset(1000);
                if (fromList) cluster.zoomToShowLayer(mk, function () { map.panTo(mk.getLatLng()); mk.openPopup(); });
            }

            // Start with every pin in view.
            const allBounds = Object.keys(markers).map(function (id) { return markers[id].getLatLng(); });
            function fitAll() { if (allBounds.length) map.fitBounds(allBounds, { padding: [50, 50], maxZoom: 14 }); }

            // A card click (anywhere but its links) or "View on Map" selects its pin.
            // Delegated, so cards loaded later work too.
            grid.addEventListener('click', function (e) {
                if (e.target.closest('a')) return;
                const c = e.target.closest('.vs-item');
                if (c) select(c.dataset.id, true);
            });

            // Lazy loading: when the end of the list scrolls into view, fetch the next page
            // of cards from the server (same filters) and append it.
            const sentinel = document.getElementById('vsMoreSentinel');
            const loader = document.getElementById('vsLoader');
            if (sentinel) {
                let loading = false;
                const io = new IntersectionObserver(function (entries) {
                    if (!entries[0].isIntersecting || loading) return;
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
                }, { root: null, rootMargin: '0px 0px 300px 0px' });
                io.observe(sentinel);
            }

            fitAll();
            let fitted = false;
            new ResizeObserver(function () { map.invalidateSize(); if (!fitted) { fitted = true; fitAll(); } }).observe(document.getElementById('vsMap'));
        })();
    </script>
@endsection

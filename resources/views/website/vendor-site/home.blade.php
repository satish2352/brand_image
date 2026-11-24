@extends('website.vendor-site.layout')
@section('title', $vendor->vendor_name)
@section('tagline', $totals['media'] . ' outdoor media sites')

@section('nav')
    @include('website.vendor-site.partials.nav', ['active' => 'home'])
@endsection

@section('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <style>
        .vs-slider { position: relative; height: 480px; background: #0F172A; overflow: hidden; }
        .vs-slide { position: absolute; inset: 0; opacity: 0; transition: opacity .6s ease; }
        .vs-slide.active { opacity: 1; }
        .vs-slide img, .vs-slide video { width: 100%; height: 100%; object-fit: cover; display: block; }
        .vs-slide::after { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, rgba(15,23,42,.85) 0%, rgba(15,23,42,.15) 65%); }
        .vs-hero-text { position: relative; z-index: 2; height: 100%; display: flex; flex-direction: column; justify-content: center; gap: 16px; color: #fff; }
        .vs-arrow { position: absolute; top: 50%; margin-top: -24px; width: 48px; height: 48px; border-radius: 999px; border: 0; background: rgba(255,255,255,.9); color: #0F172A; font-size: 18px; cursor: pointer; z-index: 3; }
        .vs-dots { position: absolute; left: 0; right: 0; bottom: 22px; display: flex; justify-content: center; gap: 8px; z-index: 3; }
        .vs-dots button { width: 8px; height: 8px; border-radius: 999px; background: rgba(255,255,255,.6); border: 0; padding: 0; cursor: pointer; }
        .vs-dots button.active { width: 28px; background: #F97316; }
        .vs-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 22px; }
        .vs-card { background: #fff; border: 1px solid #E5E7EB; border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; }
        .vs-card img { width: 100%; height: 190px; object-fit: cover; display: block; }
        #vsMap { flex: 999 1 560px; height: 460px; }
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
            <div class="vs-slide active" style="background:#0F172A">
                <div class="vs-wrap vs-hero-text" style="position:absolute;inset:0">
                    <span class="vs-badge" style="align-self:flex-start;background:#F97316;color:#0F172A">{{ $totals['media'] }} media · {{ $totals['districts'] }} district{{ $totals['districts'] == 1 ? '' : 's' }}</span>
                    <h1 style="margin:0;font-size:46px;line-height:1.1;font-weight:800;max-width:640px">{{ $vendor->vendor_name }}</h1>
                    <p style="margin:0;font-size:17px;color:#E2E8F0">Outdoor media inventory · Powered by Brand Adda</p>
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

    <section id="inventory" class="vs-wrap" style="padding-top:44px;padding-bottom:20px">
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
            <div style="display:flex;gap:8px">
                <a href="{{ route('vendor-site.show', array_merge(['vendorSlug' => $vendor->slug], \Illuminate\Support\Arr::except($filters, 'available'))) }}#inventory" class="vs-btn {{ empty($filters['available']) ? 'vs-btn-n' : 'vs-btn-g' }}" style="min-height:40px;border-radius:999px">All</a>
                <a href="{{ route('vendor-site.show', array_merge(['vendorSlug' => $vendor->slug], $filters, ['available' => 1])) }}#inventory" class="vs-btn {{ !empty($filters['available']) ? 'vs-btn-n' : 'vs-btn-g' }}" style="min-height:40px;border-radius:999px">Available now</a>
            </div>
        </div>

        <div class="vs-cards">
            @forelse ($media as $m)
                <article class="vs-card">
                    <div style="position:relative">
                        @if (isset($covers[$m->id]))
                            <img src="{{ \App\Support\MediaImageUrl::for($covers[$m->id]) }}" alt="{{ $m->media_title }}" loading="lazy">
                        @else
                            <div class="vs-ph" style="height:190px">No photo yet</div>
                        @endif
                        <span class="vs-badge" style="position:absolute;left:12px;top:12px;background:#fff;color:#0F172A">{{ $m->category_name }}</span>
                        <span class="vs-badge" style="position:absolute;right:12px;top:12px;background:{{ $m->availability['bg'] }};color:{{ $m->availability['fg'] }}">{{ $m->availability['label'] }}</span>
                    </div>
                    <div style="padding:16px 18px 18px;display:flex;flex-direction:column;gap:10px;flex:1">
                        <div style="font-size:12px;font-weight:700;color:#475569">{{ $m->hoarding_code ?: $m->media_code }}</div>
                        <h3 style="margin:0;font-size:17px;font-weight:800">{{ $m->media_title }}</h3>
                        <div style="display:flex;gap:6px;align-items:center;font-size:13px;color:#334155"><i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ $m->area_name }}{{ $m->city_name ? ', ' . $m->city_name : '' }}</div>
                        <div style="display:flex;gap:16px;font-size:13px;color:#334155">
                            @if ($m->width && $m->height)<span>{{ (float) $m->width }} × {{ (float) $m->height }} ft</span>@endif
                            @if ($m->illumination_name)<span>{{ $m->illumination_name }}</span>@endif
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto;padding-top:12px;border-top:1px solid #F1F5F9">
                            <div><span style="font-size:18px;font-weight:800">₹ {{ number_format((float) $m->price) }}</span><span style="font-size:12px;color:#475569"> / month</span></div>
                            <a href="{{ route('vendor-site.media', [$vendor->slug, $m->id]) }}" class="vs-btn vs-btn-o" style="min-height:40px;font-size:13px">View details</a>
                        </div>
                    </div>
                </article>
            @empty
                <p style="color:#475569">No media matches these filters.</p>
            @endforelse
        </div>
    </section>

    <section id="map" class="vs-wrap" style="padding-top:36px;padding-bottom:56px">
        <h2 style="margin:0 0 18px;font-size:28px;font-weight:800">Location Map</h2>
        <div style="display:flex;flex-wrap:wrap;border:1px solid #E5E7EB;border-radius:16px;overflow:hidden;background:#fff">
            <div id="vsMap" role="region" aria-label="Map of {{ $vendor->vendor_name }} sites"></div>
            <div style="flex:1 1 280px;padding:18px 20px;display:flex;flex-direction:column;gap:14px">
                <div style="font-size:14px;font-weight:800">Legend</div>
                @foreach (\App\Support\MediaAvailability::LABELS as $k => $l)
                    <div style="display:flex;align-items:center;gap:10px;font-size:14px">
                        <span style="width:14px;height:14px;border-radius:999px;background:{{ ['available' => '#16A34A', 'booked' => '#2563EB', 'blocked' => '#DC2626', 'ask' => '#D97706'][$k] }}"></span>{{ $l }}</div>
                @endforeach
                <div style="margin-top:8px;padding-top:14px;border-top:1px solid #F1F5F9;font-size:13px;line-height:1.6;color:#475569">Click a pin to see the media. Only {{ $vendor->vendor_name }}’s sites are shown.</div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
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

            // Map
            const points = @json($mapPoints);
            const colours = { available: '#16A34A', booked: '#2563EB', blocked: '#DC2626', ask: '#D97706' };
            const map = L.map('vsMap', { scrollWheelZoom: false }).setView([19.0, 75.0], 7);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
            const bounds = [];
            const esc = function (s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
            points.forEach(function (p) {
                L.circleMarker([p.lat, p.lng], { radius: 9, color: '#fff', weight: 3, fillColor: colours[p.status] || '#0F172A', fillOpacity: 1 })
                    .addTo(map)
                    .bindPopup('<strong>' + esc(p.title) + '</strong><br><span style="color:#475569">' + esc(p.meta) + '</span><br><a href="' + p.url + '">View details</a>');
                bounds.push([p.lat, p.lng]);
            });
            if (bounds.length) map.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 });
        })();
    </script>
@endsection

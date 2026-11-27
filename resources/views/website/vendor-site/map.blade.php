@extends('website.vendor-site.layout')
@section('title', 'Map · ' . $vendor->display_name)

@section('nav')
    @include('website.vendor-site.partials.nav', ['active' => 'map'])
@endsection

@section('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
    <style>
        /* The filter sidebar is the /explore sidebar: same markup classes, same stylesheet. */
        @include('website.partials.explore-sidebar-styles')

        .vs-head .vs-wrap { max-width: none; }
        /* Filters on the left; the map fills the rest of the screen under the header. */
        .vm { display: flex; height: calc(100vh - var(--hh, 86px)); min-height: 440px; }
        .vm .explore-sidebar { min-height: 0; }
        .vm .explore-sidebar-body { min-height: 0; padding-bottom: 18px; }
        .vm-unplaced { display: block; margin-top: 4px; font-size: 12px; color: #334155; }
        .vm .exp-range-input { background: #fff; color: #0F172A; font-family: inherit; }
        .vm-map { position: relative; flex: 1; min-width: 0; }
        #vmMap { position: absolute; inset: 0; }
        .vm-all { position: absolute; right: 14px; top: 14px; z-index: 500; min-height: 44px; box-shadow: 0 6px 16px rgba(15,23,42,.16); }
        .vm-none { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); z-index: 600; padding: 18px 26px; border-radius: 14px; background: #fff; font-weight: 600; box-shadow: 0 10px 28px rgba(15,23,42,.2); }
        @media (max-width: 960px) {
            .vm { display: block; height: auto; min-height: 0; }
            .vm .explore-sidebar { width: auto; min-width: 0; border-right: 0; }
            .vm .explore-sidebar-body { overflow: visible; }
            .vm-map { height: 68vh; min-height: 440px; }
            .vm-all { top: auto; bottom: 30px; width: 48px; min-height: 48px; padding: 0; justify-content: center; }
            .vm-all span { display: none; }
        }
    </style>
@endsection

@section('content')
    @php
        $onMap = $sites->filter(fn ($s) => $s['lat'])->values();
        $unplaced = $sites->count() - $onMap->count();
    @endphp
    <main class="vm">
        {{-- Same filters and look as /explore (ExploreRepository via vendor-site.map.search), limited to this vendor. --}}
        <aside class="explore-sidebar" aria-label="Filters">
            <div class="explore-sidebar-head">
                <div class="title">Filters</div>
                <div class="explore-showing" aria-live="polite">
                    <span>Showing</span>
                    <span><b id="vmCount">{{ $onMap->count() }}</b> / {{ $onMap->count() }}</span>
                </div>
                @if ($unplaced)<small class="vm-unplaced">{{ $unplaced }} {{ $unplaced === 1 ? 'site has' : 'sites have' }} no map location yet.</small>@endif
            </div>

            <form id="vmForm" class="explore-sidebar-body" onsubmit="return false">
                {{-- SEARCH --}}
                <div class="exp-group">
                    <div class="exp-group-title">Search</div>
                    <div class="exp-search-wrap" id="vmQWrap">
                        <input type="text" name="q" id="vmQ" class="exp-search-input" placeholder="Search location, area..." autocomplete="off">
                        <span class="exp-search-clear" id="vmQClear" title="Clear search">&times;</span>
                    </div>
                </div>

                {{-- CHECKBOX GROUPS --}}
                @foreach ($groups as $g)
                    <div class="exp-group">
                        <div class="exp-group-title">{{ $g['title'] }}</div>
                        @if ($g['search'])
                            <input type="text" class="grp-filter" placeholder="Search {{ strtolower($g['title']) }}..." aria-label="Search {{ strtolower($g['title']) }}">
                        @endif
                        <div class="exp-options">
                            @forelse ($g['rows'] as $row)
                                <label class="exp-check">
                                    <input type="checkbox" name="{{ $g['key'] }}[]" value="{{ $row['id'] }}">
                                    <span>{{ $row['name'] }}</span>
                                </label>
                            @empty
                                <div class="exp-empty-opt">No options</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach

                {{-- STATUS TODAY (vendor map only) --}}
                <div class="exp-group">
                    <div class="exp-group-title">Status Today</div>
                    <select id="vmStatus" class="exp-range-input" aria-label="Status today"><option value="">All availability</option>
                        @foreach (\App\Support\MediaAvailability::LABELS as $k => $l)
                            @php $n = $onMap->where('status', $k)->count(); @endphp
                            @if ($n)<option value="{{ $k }}">{{ $l }} ({{ $n }})</option>@endif
                        @endforeach
                    </select>
                </div>

                {{-- AVAILABILITY (FROM / TO DATE) --}}
                <div class="exp-group">
                    <div class="exp-group-title">Availability</div>
                    <div class="exp-range">
                        <div class="exp-range-field">
                            <label for="vmFrom">From</label>
                            <input type="date" name="from_date" id="vmFrom" class="exp-range-input" min="{{ now()->toDateString() }}">
                        </div>
                        <div class="exp-range-field">
                            <label for="vmTo">To</label>
                            <input type="date" name="to_date" id="vmTo" class="exp-range-input" min="{{ now()->toDateString() }}">
                        </div>
                    </div>
                </div>

                {{-- RADIUS (single handle; measures out from the ticked towns) --}}
                <div class="exp-group">
                    <div class="exp-group-title">Radius</div>
                    <div class="exp-slider-labels"><span id="vmRadiusLabel">Any</span><span>{{ $radiusMax }} KM</span></div>
                    <div class="exp-slider single is-disabled" id="vmRadiusBox">
                        <div class="exp-slider-fill" id="vmRadiusFill"></div>
                        <input type="range" name="radius_id" id="vmRadius" min="0" max="{{ max(1, $radiusMax) }}" step="1" value="0" aria-label="Radius in km" disabled>
                    </div>
                    <div class="exp-slider-hint" id="vmRadiusHint">Select a Town to use this</div>
                </div>

                {{-- BUDGET / MEDIA SIZE (two handles) --}}
                @foreach ([['price', 'Budget (₹)', $price], ['area', 'Media Size (sq.ft)', $area]] as [$kind, $title, $r])
                    <div class="exp-group vm-dual" data-kind="{{ $kind }}" data-min="{{ $r['min'] }}" data-max="{{ max($r['max'], $r['min']) }}">
                        <div class="exp-group-title">{{ $title }}</div>
                        <div class="exp-slider-labels"><span class="lo-v"></span><span class="hi-v"></span></div>
                        <div class="exp-slider">
                            <input type="hidden" name="min_{{ $kind }}"><input type="hidden" name="max_{{ $kind }}">
                            <div class="exp-slider-fill"></div>
                            <input type="range" class="lo" aria-label="Minimum {{ strtolower($title) }}">
                            <input type="range" class="hi" aria-label="Maximum {{ strtolower($title) }}">
                        </div>
                    </div>
                @endforeach
            </form>

            <div class="explore-sidebar-foot">
                <button type="button" class="btn-clear-all" id="vmClear">Clear All Filters</button>
            </div>
        </aside>

        <div class="vm-map">
            <div id="vmMap" role="region" aria-label="Map of {{ $vendor->display_name }} sites"></div>
            <button type="button" class="vs-btn vs-btn-g vm-all" id="vmAll" aria-label="Show all sites" title="Show all sites"><i class="fa-solid fa-expand" aria-hidden="true"></i><span>Show all sites</span></button>
            <div class="vm-none" id="vmNone" role="status" @if ($onMap->isNotEmpty()) hidden @endif>No site matches.</div>
            <div class="vs-legend" aria-label="Legend">
                @foreach (\App\Support\MediaAvailability::LABELS as $k => $l)<span><i style="background:{{ $colours[$k] }}"></i>{{ $l }}</span>@endforeach
            </div>
        </div>
    </main>
@endsection

@section('scripts')
    @include('website.vendor-site.partials.map-kit')
    <style>[hidden] { display: none !important; }</style>
    <script>
        (function () {
            const head = document.querySelector('.vs-head');
            const fitHead = function () { document.documentElement.style.setProperty('--hh', head.offsetHeight + 'px'); };
            fitHead();

            const points = @json($onMap);
            const kit = VsMap.create('vmMap', points, { wheel: window.matchMedia('(min-width: 961px)').matches, onPin: select });
            const form = document.getElementById('vmForm'), status = document.getElementById('vmStatus');
            const searchUrl = @json(route('vendor-site.map.search', $vendor->slug));
            let shown = points, selected = null, matchIds = null, ctrl = null;

            function fit() {
                if (shown.length) kit.map.fitBounds(shown.map(function (p) { return [p.lat, p.lng]; }), { padding: [60, 60], maxZoom: 15 });
            }
            function clear() {
                if (selected) { selected.setIcon(selected._plain); selected.setZIndexOffset(0); }
                selected = null;
            }
            function select(id) {
                clear();
                selected = kit.markers[id];
                selected.setIcon(selected._big); selected.setZIndexOffset(1000);
            }
            // Show the points the server matched (matchIds; null = no filter) and the Status dropdown allows.
            function render() {
                clear(); kit.map.closePopup();
                const keep = matchIds ? new Set(matchIds) : null;
                shown = points.filter(function (p) {
                    return (!keep || keep.has(p.id)) && (!status.value || p.status === status.value);
                });
                kit.cluster.clearLayers();
                kit.cluster.addLayers(shown.map(function (p) { return kit.markers[p.id]; }));
                document.getElementById('vmCount').textContent = shown.length;
                document.getElementById('vmNone').hidden = shown.length > 0;
                fit();
            }

            // Ask the server (same search as /explore) which of this vendor's media match.
            function search() {
                const params = new URLSearchParams();
                new FormData(form).forEach(function (v, k) { if (String(v).trim() !== '' && !(k === 'radius_id' && +v === 0)) params.append(k, v); });
                if (!Array.from(params.keys()).length) { matchIds = null; render(); return; }
                if (ctrl) ctrl.abort();
                ctrl = new AbortController();
                fetch(searchUrl + '?' + params, { headers: { 'Accept': 'application/json' }, signal: ctrl.signal, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (d) { matchIds = d.ids || []; render(); })
                    .catch(function (e) { if (e.name !== 'AbortError') console.warn('Map search failed', e); });
            }
            let wait;
            const later = function () { clearTimeout(wait); wait = setTimeout(search, 300); };

            // Search box with its clear (×) button, as on /explore.
            const q = document.getElementById('vmQ'), qWrap = document.getElementById('vmQWrap');
            const syncQ = function () { qWrap.classList.toggle('has-text', q.value.length > 0); };
            document.getElementById('vmQClear').addEventListener('click', function () { q.value = ''; syncQ(); later(); });

            // Per-group "Search town..." boxes narrow the checkbox lists.
            form.querySelectorAll('.grp-filter').forEach(function (box) {
                box.addEventListener('input', function () {
                    const t = box.value.trim().toLowerCase();
                    box.nextElementSibling.querySelectorAll('.exp-check').forEach(function (l) {
                        l.classList.toggle('hidden', !!t && l.textContent.toLowerCase().indexOf(t) < 0);
                    });
                });
            });

            // Radius measures out from the ticked towns, as on /explore.
            const radius = document.getElementById('vmRadius'), radiusBox = document.getElementById('vmRadiusBox');
            function syncRadius() {
                const hasTown = form.querySelectorAll('input[name="city_id[]"]:checked').length > 0;
                radius.disabled = !hasTown;
                radiusBox.classList.toggle('is-disabled', !hasTown);
                document.getElementById('vmRadiusHint').hidden = hasTown;
                document.getElementById('vmRadiusLabel').textContent = +radius.value ? radius.value + ' KM' : 'Any';
                document.getElementById('vmRadiusFill').style.width = (+radius.value / +radius.max * 100) + '%';
            }
            radius.addEventListener('input', syncRadius);

            // Two-handle sliders: hidden min/max are sent only once a handle leaves its end.
            form.querySelectorAll('.vm-dual').forEach(function (box) {
                const min = +box.dataset.min, max = +box.dataset.max, kind = box.dataset.kind;
                const lo = box.querySelector('.lo'), hi = box.querySelector('.hi'), hid = box.querySelectorAll('input[type=hidden]');
                const fill = box.querySelector('.exp-slider-fill');
                [lo, hi].forEach(function (r) { r.min = min; r.max = max; r.step = 1; });
                const fmt = function (v) { return kind === 'price' ? '₹' + Number(v).toLocaleString('en-IN') : Number(v).toLocaleString('en-IN') + ' sqft'; };
                // Every media has the same value (e.g. one media): nothing to range over, so show it and lock the slider.
                if (max <= min) {
                    lo.disabled = hi.disabled = true;
                    box.querySelector('.exp-slider').style.opacity = '.45';
                    fill.style.left = '0%'; fill.style.width = '100%';
                    box.querySelector('.lo-v').textContent = fmt(min); box.querySelector('.hi-v').textContent = '';
                    box.reset = function () {};
                    return;
                }
                function update(e) {
                    if (+lo.value > +hi.value) { if (e && e.target === lo) lo.value = hi.value; else hi.value = lo.value; }
                    const a = (+lo.value - min) / (max - min) * 100, b = (+hi.value - min) / (max - min) * 100;
                    fill.style.left = a + '%'; fill.style.width = (b - a) + '%';
                    box.querySelector('.lo-v').textContent = fmt(lo.value); box.querySelector('.hi-v').textContent = fmt(hi.value);
                    hid[0].value = +lo.value > min ? lo.value : '';
                    hid[1].value = +hi.value < max ? hi.value : '';
                    lo.style.zIndex = (+lo.value >= max - 1) ? 4 : 3;
                }
                lo.addEventListener('input', update); hi.addEventListener('input', update);
                box.reset = function () { lo.value = min; hi.value = max; update(); };
                box.reset();
            });
            syncRadius();

            // To Date never before From Date.
            const from = document.getElementById('vmFrom'), to = document.getElementById('vmTo');
            from.addEventListener('change', function () { to.min = from.value || to.getAttribute('min'); if (to.value && from.value && to.value < from.value) to.value = from.value; });

            form.addEventListener('input', function (e) {
                if (e.target.classList.contains('grp-filter')) return;
                if (e.target === q) syncQ();
                if (e.target.name === 'city_id[]') syncRadius();
                later();
            });
            form.addEventListener('change', function (e) {
                if (e.target === status) { render(); return; }
                if (e.target.classList.contains('grp-filter')) return;
                if (e.target.name === 'city_id[]') syncRadius();
                later();
            });

            document.getElementById('vmClear').addEventListener('click', function () {
                form.reset();
                form.querySelectorAll('.exp-check').forEach(function (l) { l.classList.remove('hidden'); });
                form.querySelectorAll('.vm-dual').forEach(function (b) { b.reset(); });
                radius.value = 0; syncRadius(); syncQ();
                to.min = to.getAttribute('min');
                matchIds = null; render();
            });
            document.getElementById('vmAll').addEventListener('click', function () { clear(); kit.map.closePopup(); fit(); });

            fit();
            let first = true;
            new ResizeObserver(function () { kit.map.invalidateSize(); if (first) { first = false; fit(); } }).observe(document.getElementById('vmMap'));
            window.addEventListener('resize', fitHead);
        })();
    </script>
@endsection

@extends('website.layout')

@section('title', 'Explore Hoardings')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

    <style>
        /* lock the explore page to the viewport so only the sidebar/map scroll
           internally — no outer page scrollbar below the map */
        html,
        body {
            height: 100%;
            overflow: hidden;
        }

        /* make the explore area fill the viewport below the header exactly,
           regardless of the header's real height (no leftover white space) */
        main.flex-fill {
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .explore-wrap {
            display: flex;
            gap: 0;
            flex: 1 1 auto;
            min-height: 0;
            height: auto;
            overflow: hidden;
        }

        /* ---------------- LEFT FILTER SIDEBAR ---------------- */
        .explore-sidebar {
            width: 320px;
            min-width: 320px;
            background: #fff;
            border-right: 1px solid #e8e8e8;
            display: flex;
            flex-direction: column;
        }

        .explore-sidebar-head {
            padding: 16px 18px 10px;
            border-bottom: 1px solid #eee;
        }

        .explore-sidebar-head .title {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 2px;
            color: #f28123;
            text-transform: uppercase;
        }

        .explore-showing {
            margin-top: 8px;
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .explore-showing b {
            font-size: 18px;
            color: #222;
        }

        .explore-sidebar-body {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 6px 18px 80px;
        }

        .exp-group {
            padding: 14px 0 6px;
            border-bottom: 1px solid #f0f0f0;
        }

        .exp-group-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .grp-filter,
        .exp-search-input {
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 7px 10px;
            font-size: 13px;
            margin-bottom: 8px;
            outline: none;
        }

        .grp-filter:focus,
        .exp-search-input:focus {
            border-color: #f28123;
        }

        .exp-options {
            max-height: 168px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .exp-options::-webkit-scrollbar {
            width: 6px;
        }

        .exp-options::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 3px;
        }

        .exp-check {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 5px 4px;
            margin: 0;
            font-size: 14px;
            color: #333;
            font-weight: 400;
            cursor: pointer;
            border-radius: 5px;
        }

        .exp-check:hover {
            background: #fff7f0;
        }

        /* force native checkbox to render (some global CSS hides them) */
        .exp-check input[type="checkbox"] {
            -webkit-appearance: checkbox !important;
            -moz-appearance: checkbox !important;
            appearance: auto !important;
            width: 16px !important;
            height: 16px !important;
            min-width: 16px;
            flex: 0 0 16px;
            margin: 0 !important;
            position: static !important;
            opacity: 1 !important;
            accent-color: #f28123;
            cursor: pointer;
        }

        .exp-check.hidden {
            display: none;
        }

        .exp-empty-opt {
            font-size: 12px;
            color: #aaa;
            padding: 4px;
        }

        /* range / date filter rows */
        .exp-range {
            display: flex;
            gap: 8px;
        }

        .exp-range-field {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .exp-range-field label {
            font-size: 11px;
            color: #999;
            font-weight: 600;
            margin: 0;
        }

        .exp-range-input {
            width: 100%;
            box-sizing: border-box;
            max-width: 100%;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 7px 9px;
            font-size: 13px;
            outline: none;
        }

        .exp-range-input:focus {
            border-color: #f28123;
        }

        /* dual-handle range slider (budget + media size) */
        .exp-slider-labels {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }

        .exp-slider {
            position: relative;
            width: 100%;
            height: 26px;
        }

        .exp-slider input[type=range] {
            -webkit-appearance: none;
            appearance: none;
            position: absolute;
            width: 100%;
            height: 6px;
            top: 8px;
            margin: 0;
            background: none;
            pointer-events: none;
        }

        .exp-slider input[type=range]::-webkit-slider-runnable-track {
            height: 6px;
            background: #d7d7d7;
            border-radius: 3px;
        }

        .exp-slider input[type=range]::-moz-range-track {
            height: 6px;
            background: #d7d7d7;
            border-radius: 3px;
        }

        .exp-slider input[type=range]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            pointer-events: auto;
            width: 16px;
            height: 16px;
            background: #f28123;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #fff;
            box-shadow: 0 0 4px rgba(0, 0, 0, .3);
            margin-top: -5px;
            position: relative;
            z-index: 5;
        }

        .exp-slider input[type=range]::-moz-range-thumb {
            pointer-events: auto;
            width: 16px;
            height: 16px;
            background: #f28123;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #fff;
            box-shadow: 0 0 4px rgba(0, 0, 0, .3);
        }

        .exp-slider-fill {
            position: absolute;
            height: 6px;
            background: #f28123;
            top: 8px;
            border-radius: 3px;
            z-index: 2;
        }

        /* sticky footer buttons */
        .explore-sidebar-foot {
            position: sticky;
            bottom: 0;
            background: #fff;
            border-top: 1px solid #eee;
            padding: 10px 18px;
            display: flex;
            gap: 8px;
        }

        .btn-clear-all {
            flex: 1;
            background: #f1f1f1;
            border: none;
            padding: 9px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            color: #444;
        }

        .btn-clear-all:hover {
            background: #e6e6e6;
        }

        .explore-spinner {
            font-size: 11px;
            color: #f28123;
            align-self: center;
            display: none;
        }

        /* ---------------- RIGHT MAP ---------------- */
        .explore-map-area {
            flex: 1;
            position: relative;
        }

        #exploreMap {
            width: 100%;
            height: 100%;
        }

        /* +/- zoom buttons — vertically centered on the map's right edge */
        .explore-map-area .leaflet-top.leaflet-right {
            top: 50%;
            transform: translateY(-50%);
            right: 0;
        }

        .explore-map-area .leaflet-control-zoom {
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .25);
            margin-right: 14px;
        }

        .explore-map-area .leaflet-control-zoom a {
            width: 38px;
            height: 38px;
            line-height: 38px;
            font-size: 22px;
            color: #333;
            background: #fff;
        }

        .explore-map-area .leaflet-control-zoom a:hover {
            background: #fff7f0;
            color: #f28123;
        }

        /* orange cluster bubbles (override Leaflet's default green/yellow) */
        .exp-cluster {
            background: transparent;
        }

        .exp-cluster .cluster-bubble {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: #f28123;
            border: 3px solid #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .35);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            font-family: "Outfit", sans-serif;
        }

        /* selected map marker */
        .exp-pin {
            width: 28px;
            height: 40px;
            background: #f28123;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            box-shadow: 0 2px 6px rgba(0, 0, 0, .35);
        }

        .exp-pin.selected {
            background: #1971c2;
            width: 36px;
            height: 50px;
            z-index: 9999;
        }

        @@media (max-width: 991px) {
            .explore-wrap {
                flex-direction: column;
                height: auto;
            }

            .explore-sidebar {
                width: 100%;
                min-width: 0;
                max-height: 50vh;
            }

            .explore-map-area {
                height: 60vh;
            }
        }
    </style>

    @php
        $groups = [
            [
                'key' => 'state_id',
                'title' => 'State',
                'rows' => $states,
                'id' => 'id',
                'name' => 'state_name',
                'search' => false,
            ],
            [
                'key' => 'district_id',
                'title' => 'District',
                'rows' => $districts,
                'id' => 'id',
                'name' => 'district_name',
                'search' => true,
            ],
            [
                'key' => 'city_id',
                'title' => 'Town',
                'rows' => $cities,
                'id' => 'id',
                'name' => 'city_name',
                'search' => true,
            ],
            [
                'key' => 'area_id',
                'title' => 'Area',
                'rows' => $areas,
                'id' => 'id',
                'name' => 'area_name',
                'search' => true,
            ],
            [
                'key' => 'category_id',
                'title' => 'Media Type',
                'rows' => $categories,
                'id' => 'id',
                'name' => 'category_name',
                'search' => false,
            ],
            [
                'key' => 'areatype_id',
                'title' => 'Area Type',
                'rows' => $areaTypes,
                'id' => 'id',
                'name' => 'areatype_name',
                'search' => false,
            ],
            [
                'key' => 'highway_id',
                'title' => 'Highway',
                'rows' => $highways,
                'id' => 'id',
                'name' => 'highway_name',
                'search' => true,
            ],
            [
                'key' => 'landmark_ids',
                'title' => 'Landmarks',
                'rows' => $landmarks,
                'id' => 'id',
                'name' => 'landmark_name',
                'search' => true,
            ],
        ];
    @endphp

    <div class="explore-wrap">
        {{-- ================= LEFT : FILTER SIDEBAR ================= --}}
        <aside class="explore-sidebar">
            <div class="explore-sidebar-head">
                <div class="title">Filters</div>
                <div class="explore-showing">
                    <span>Showing</span>
                    <span><b id="exploreCount">{{ $mediaList->total() }}</b> / {{ $grandTotal }}</span>
                </div>
            </div>

            <form id="exploreForm" class="explore-sidebar-body">
                {{-- SEARCH --}}
                <div class="exp-group" style="border-bottom:none;padding-top:10px;">
                    <div class="exp-group-title">Search</div>
                    <input type="text" name="q" id="exp_q" class="exp-search-input"
                        placeholder="Asset ID, location, area...">
                </div>

                {{-- CHECKBOX GROUPS --}}
                @foreach ($groups as $g)
                    <div class="exp-group">
                        <div class="exp-group-title">{{ $g['title'] }}</div>
                        @if ($g['search'])
                            <input type="text" class="grp-filter" data-target="opts_{{ $g['key'] }}"
                                placeholder="Search {{ strtolower($g['title']) }}...">
                        @endif
                        <div class="exp-options" id="opts_{{ $g['key'] }}">
                            @forelse ($g['rows'] as $row)
                                <label class="exp-check">
                                    <input type="checkbox" name="{{ $g['key'] }}[]" value="{{ $row->{$g['id']} }}">
                                    <span>{{ $row->{$g['name']} }}</span>
                                </label>
                            @empty
                                <div class="exp-empty-opt">No options</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach

                {{-- AVAILABILITY (FROM / TO DATE) --}}
                <div class="exp-group">
                    <div class="exp-group-title">Availability</div>
                    <div class="exp-range">
                        <div class="exp-range-field">
                            <label>From</label>
                            <input type="date" name="from_date" id="exp_from_date" class="exp-range-input">
                        </div>
                        <div class="exp-range-field">
                            <label>To</label>
                            <input type="date" name="to_date" id="exp_to_date" class="exp-range-input">
                        </div>
                    </div>
                </div>

                {{-- BUDGET (PRICE RANGE SLIDER) --}}
                <div class="exp-group">
                    <div class="exp-group-title">Budget (₹)</div>
                    <div class="exp-slider-labels">
                        <span id="expMinPriceLabel">₹{{ number_format($priceRange->min_price) }}</span>
                        <span id="expMaxPriceLabel">₹{{ number_format($priceRange->max_price) }}</span>
                    </div>
                    <div class="exp-slider">
                        <input type="hidden" name="min_price" id="exp_min_price"
                            value="{{ $priceRange->min_price }}">
                        <input type="hidden" name="max_price" id="exp_max_price"
                            value="{{ $priceRange->max_price }}">
                        <div class="exp-slider-fill" id="expPriceFill"></div>
                        <input type="range" id="expMinPriceRange" min="{{ $priceRange->min_price }}"
                            max="{{ $priceRange->max_price }}" step="1000" value="{{ $priceRange->min_price }}">
                        <input type="range" id="expMaxPriceRange" min="{{ $priceRange->min_price }}"
                            max="{{ $priceRange->max_price }}" step="1000" value="{{ $priceRange->max_price }}">
                    </div>
                </div>

                {{-- MEDIA SIZE (SQ.FT RANGE SLIDER) --}}
                <div class="exp-group">
                    <div class="exp-group-title">Media Size (sq.ft)</div>
                    <div class="exp-slider-labels">
                        <span id="expMinAreaLabel">{{ number_format($areaRange->min_area) }} sqft</span>
                        <span id="expMaxAreaLabel">{{ number_format($areaRange->max_area) }} sqft</span>
                    </div>
                    <div class="exp-slider">
                        <input type="hidden" name="min_area" id="exp_min_area"
                            value="{{ $areaRange->min_area }}">
                        <input type="hidden" name="max_area" id="exp_max_area"
                            value="{{ $areaRange->max_area }}">
                        <div class="exp-slider-fill" id="expAreaFill"></div>
                        <input type="range" id="expMinAreaRange" min="{{ $areaRange->min_area }}"
                            max="{{ $areaRange->max_area }}" step="1" value="{{ $areaRange->min_area }}">
                        <input type="range" id="expMaxAreaRange" min="{{ $areaRange->min_area }}"
                            max="{{ $areaRange->max_area }}" step="1" value="{{ $areaRange->max_area }}">
                    </div>
                </div>
            </form>

            <div class="explore-sidebar-foot">
                <button type="button" class="btn-clear-all" id="exploreClear">Clear All Filters</button>
                <span class="explore-spinner" id="exploreSpinner">Updating…</span>
            </div>
        </aside>

        {{-- ================= RIGHT : MAP ================= --}}
        <div class="explore-map-area">
            <div id="exploreMap"></div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <script>
        const EXPLORE = {
            searchUrl: "{{ route('website.explore.search') }}",
            csrf: "{{ csrf_token() }}",
            initialMarkers: @json($mapMarkers),
            mediaDetailsBase: "{{ url('media-details') }}",
        };
    </script>

    <script>
        $(function() {

            /* ============ MAP ============ */
            // Lock the map to India so other countries are not shown.
            const INDIA_MAX_BOUNDS = L.latLngBounds([6.0, 68.0], [37.5, 97.5]);

            let map = L.map('exploreMap', {
                zoomControl: false,
                minZoom: 5,
                maxBounds: INDIA_MAX_BOUNDS,
                maxBoundsViscosity: 1.0
            }).setView([19.997453, 73.789803], 11);
            map.setMaxBounds(INDIA_MAX_BOUNDS);

            // place the +/- zoom buttons on the right so they don't overlap the header
            L.control.zoom({
                position: 'topright'
            }).addTo(map);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            let cluster = L.markerClusterGroup({
                maxClusterRadius: 45,
                showCoverageOnHover: false,
                iconCreateFunction: function(c) {
                    let count = c.getChildCount();
                    let size = count < 10 ? 34 : (count < 100 ? 40 : 48);
                    return L.divIcon({
                        html: '<div class="cluster-bubble">' + count + '</div>',
                        className: 'exp-cluster',
                        iconSize: L.point(size, size)
                    });
                }
            });
            map.addLayer(cluster);

            let markersById = {};
            let selectedId = null;

            function pinIcon(selected) {
                return L.divIcon({
                    className: '',
                    html: '<div class="exp-pin' + (selected ? ' selected' : '') + '"></div>',
                    iconSize: selected ? [36, 50] : [28, 40],
                    iconAnchor: selected ? [18, 50] : [14, 40],
                    popupAnchor: [0, -38],
                });
            }

            function popupHtml(m) {
                let img = m.image ?
                    '<img src="' + m.image +
                    '" style="width:100%;height:90px;object-fit:cover;border-radius:6px;margin-bottom:6px;">' :
                    '';
                let code = m.hoarding_code ? '<div style="font-size:11px;color:#666;">' + m.hoarding_code +
                    '</div>' : '';
                let hw = m.highway_name ? '<div style="font-size:11px;color:#666;">🛣 ' + m.highway_name +
                    '</div>' : '';
                let lm = m.landmarks ? '<div style="font-size:11px;color:#888;">📍 ' + m.landmarks + '</div>' : '';
                return '<div style="width:190px;">' + img + code +
                    '<div style="font-weight:600;">' + (m.title || '') + '</div>' +
                    '<div style="font-size:12px;">' + (m.width || '') + ' × ' + (m.height || '') + ' ft</div>' +
                    hw + lm +
                    '<div style="color:#f28123;font-weight:700;margin-top:2px;">₹ ' + Number(m.price)
                    .toLocaleString() + '</div>' +
                    '<a href="' + EXPLORE.mediaDetailsBase + '/' + m.eid +
                    '" style="color:#1971c2;font-size:12px;">View Details →</a>' +
                    '</div>';
            }

            function highlightMarker(id) {
                if (selectedId && markersById[selectedId]) {
                    markersById[selectedId].setIcon(pinIcon(false));
                }
                selectedId = id;
                if (markersById[id]) {
                    markersById[id].setIcon(pinIcon(true));
                }
            }

            // India (+ immediate neighbours) bounding box — used to keep the
            // auto-zoom focused on real data and ignore stray/placeholder
            // coordinates that would otherwise force a whole-world view.
            const INDIA_BBOX = {
                latMin: 5,
                latMax: 38,
                lngMin: 67,
                lngMax: 98
            };

            function inIndia(lat, lng) {
                return lat >= INDIA_BBOX.latMin && lat <= INDIA_BBOX.latMax &&
                    lng >= INDIA_BBOX.lngMin && lng <= INDIA_BBOX.lngMax;
            }

            function renderMarkers(markers, fit) {
                cluster.clearLayers();
                markersById = {};
                selectedId = null;
                let bounds = []; // points used for auto-zoom (in-India only)
                let allBounds = []; // every valid point (fallback)

                markers.forEach(function(m) {
                    if (!m.lat || !m.lng) return;
                    let marker = L.marker([m.lat, m.lng], {
                        icon: pinIcon(false)
                    });
                    marker.bindPopup(popupHtml(m));
                    marker.on('click', function() {
                        highlightMarker(m.id);
                    });
                    cluster.addLayer(marker);
                    markersById[m.id] = marker;
                    allBounds.push([m.lat, m.lng]);
                    if (inIndia(m.lat, m.lng)) bounds.push([m.lat, m.lng]);
                });

                if (fit) {
                    let fitTo = bounds.length ? bounds : allBounds;
                    if (fitTo.length) {
                        map.fitBounds(fitTo, {
                            padding: [40, 40],
                            maxZoom: 13
                        });
                    }
                }
            }

            /* ============ AJAX SEARCH (live) ============ */
            let reqToken = 0;

            function runSearch() {
                let myToken = ++reqToken;
                $('#exploreSpinner').show();

                $.ajax({
                    url: EXPLORE.searchUrl,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': EXPLORE.csrf
                    },
                    data: $('#exploreForm').serialize(),
                    success: function(res) {
                        if (myToken !== reqToken) return; // ignore stale response
                        $('#exploreCount').text(res.total_count);
                        renderMarkers(res.markers, true);
                    },
                    complete: function() {
                        if (myToken === reqToken) $('#exploreSpinner').hide();
                    }
                });
            }

            // live update on any checkbox change
            $(document).on('change', '#exploreForm input[type="checkbox"]', runSearch);

            // debounced text search
            let qTimer = null;
            $('#exp_q').on('input', function() {
                clearTimeout(qTimer);
                qTimer = setTimeout(runSearch, 400);
            });

            // date pickers — keep the window valid, then search immediately
            $('#exp_from_date').on('change', function() {
                $('#exp_to_date').attr('min', this.value || null);
                runSearch();
            });
            $('#exp_to_date').on('change', function() {
                $('#exp_from_date').attr('max', this.value || null);
                runSearch();
            });

            /* ---- dual-handle range sliders (budget + media size) ---- */
            let rangeTimer = null;

            function initRangeSlider(cfg) {
                const $min = $('#' + cfg.minRange);
                const $max = $('#' + cfg.maxRange);
                const $fill = $('#' + cfg.fill);
                const $minLabel = $('#' + cfg.minLabel);
                const $maxLabel = $('#' + cfg.maxLabel);
                const $minHidden = $('#' + cfg.minHidden);
                const $maxHidden = $('#' + cfg.maxHidden);

                const lo = Number($min.attr('min'));
                const hi = Number($min.attr('max'));
                const gap = cfg.gap || 1;
                const span = (hi - lo) || 1;

                function fmt(v) {
                    return cfg.prefix + Number(v).toLocaleString('en-IN') + cfg.suffix;
                }

                function update(commit) {
                    let minVal = parseInt($min.val(), 10);
                    let maxVal = parseInt($max.val(), 10);

                    if (minVal > maxVal - gap) {
                        minVal = Math.max(lo, maxVal - gap);
                        $min.val(minVal);
                    }

                    const minPct = ((minVal - lo) / span) * 100;
                    const maxPct = ((maxVal - lo) / span) * 100;
                    $fill.css({
                        left: minPct + '%',
                        width: (maxPct - minPct) + '%'
                    });

                    $minLabel.text(fmt(minVal));
                    $maxLabel.text(fmt(maxVal));
                    $minHidden.val(minVal);
                    $maxHidden.val(maxVal);
                }

                cfg.update = update;
                update(false);

                $min.add($max).on('input', function() {
                    update(true);
                });
                $min.add($max).on('change', function() {
                    update(true);
                    clearTimeout(rangeTimer);
                    rangeTimer = setTimeout(runSearch, 300);
                });

                return cfg;
            }

            const priceSlider = initRangeSlider({
                minRange: 'expMinPriceRange',
                maxRange: 'expMaxPriceRange',
                fill: 'expPriceFill',
                minLabel: 'expMinPriceLabel',
                maxLabel: 'expMaxPriceLabel',
                minHidden: 'exp_min_price',
                maxHidden: 'exp_max_price',
                prefix: '₹',
                suffix: '',
                gap: 1000
            });

            const areaSlider = initRangeSlider({
                minRange: 'expMinAreaRange',
                maxRange: 'expMaxAreaRange',
                fill: 'expAreaFill',
                minLabel: 'expMinAreaLabel',
                maxLabel: 'expMaxAreaLabel',
                minHidden: 'exp_min_area',
                maxHidden: 'exp_max_area',
                prefix: '',
                suffix: ' sqft',
                gap: 1
            });

            // type-to-filter inside a checkbox group
            $('.grp-filter').on('input', function() {
                let term = $(this).val().toLowerCase();
                let $box = $('#' + $(this).data('target'));
                $box.find('.exp-check').each(function() {
                    let txt = $(this).find('span').text().toLowerCase();
                    $(this).toggleClass('hidden', txt.indexOf(term) === -1);
                });
            });

            // clear all
            $('#exploreClear').on('click', function() {
                $('#exploreForm')[0].reset();
                $('#exp_from_date').removeAttr('max');
                $('#exp_to_date').removeAttr('min');
                // restore sliders to their full range + repaint fill/labels
                priceSlider.update(false);
                areaSlider.update(false);
                $('.grp-filter').trigger('input');
                runSearch();
            });

            /* ============ INITIAL RENDER ============ */
            renderMarkers(EXPLORE.initialMarkers, true);
        });
    </script>
@endsection

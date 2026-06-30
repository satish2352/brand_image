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

        /* On this locked-viewport page, keep the site header in normal flow
           (it is position:fixed globally) so the layout becomes a clean column:
           menu → filter row → sidebar+map. This makes the selected-filter row
           sit directly BELOW the menu, with the menu always visible. */
        .top-header-area {
            position: relative !important;
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
            /* soft brand-warm theme tint instead of plain white */
            background: #fdf4ec;
            border-right: 1px solid #f0e2d4;
            display: flex;
            flex-direction: column;
        }

        .explore-sidebar-head {
            padding: 16px 18px 10px;
            background: #fdf4ec;
            border-bottom: 1px solid #f0e2d4;
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

        /* each menu = a clean white card on the warm sidebar (no divider lines) */
        .exp-group {
            background: #fff;
            border: 1px solid #f0e2d4;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 10px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .03);
        }

        .exp-group-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #f28123;
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

        /* clear (cancel) icon inside the search bar */
        .exp-search-wrap {
            position: relative;
        }

        .exp-search-wrap .exp-search-input {
            padding-right: 30px;
        }

        .exp-search-clear {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            margin-top: -4px;
            /* offset the input's 8px bottom margin */
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #f28123;
            color: #fff;
            font-size: 14px;
            line-height: 18px;
            text-align: center;
            cursor: pointer;
            display: none;
        }

        .exp-search-clear:hover {
            background: #d96f15;
        }

        .exp-search-wrap.has-text .exp-search-clear {
            display: block;
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
        /* stack From / To dates so each gets full width (side-by-side was too
           narrow in the sidebar and the date text/calendar icon overlapped) */
        .exp-range {
            display: flex;
            flex-direction: column;
            gap: 10px;
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
            background: #fdf4ec;
            border-top: 1px solid #f0e2d4;
            padding: 10px 18px;
            display: flex;
            gap: 8px;
        }

        .btn-clear-all {
            flex: 1;
            background: #f28123;
            border: none;
            padding: 10px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            color: #fff;
            box-shadow: 0 2px 6px rgba(242, 129, 35, .35);
            transition: background .15s ease;
        }

        .btn-clear-all:hover {
            background: #d96f15;
        }

        /* ---------------- ACTIVE FILTER CHIPS ---------------- */
        .explore-chips {
            padding: 10px 18px;
            border-bottom: 1px solid #f0e2d4;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            flex-wrap: wrap;
        }

        .explore-chips-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            flex: 1;
            min-width: 0;
        }

        .exp-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff3e8;
            color: #d96f15;
            border: 1px solid #f6c79a;
            border-radius: 14px;
            padding: 3px 6px 3px 10px;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.4;
            max-width: 100%;
        }

        .exp-chip .exp-chip-label {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }

        .exp-chip .exp-chip-x {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #f28123;
            color: #fff;
            font-size: 12px;
            line-height: 1;
            cursor: pointer;
            flex: 0 0 16px;
        }

        .exp-chip .exp-chip-x:hover {
            background: #d96f15;
        }

        .explore-chips-clear {
            background: none;
            border: none;
            color: #f28123;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            padding: 4px 2px;
            white-space: nowrap;
        }

        .explore-chips-clear:hover {
            text-decoration: underline;
        }

        /* ---------------- TOP FULL-WIDTH FILTERING BAR (above sidebar + map) ---------------- */
        .explore-topbar {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            background: #fdf4ec;
            border-bottom: 1px solid #f0e2d4;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .06);
            flex-wrap: wrap;
            flex: 0 0 auto;
        }

        .explore-topbar-label {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #f28123;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .explore-topbar .explore-chips-list {
            flex: 1;
        }

        /* CLEAR ALL as a bordered pill (matches the reference look) */
        .explore-topbar .explore-chips-clear {
            border: 1px solid #f28123;
            border-radius: 16px;
            padding: 5px 14px;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .explore-topbar .explore-chips-clear:hover {
            text-decoration: none;
            background: #f28123;
            color: #fff;
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

        /* live result count, pinned to the top of the map */
        .explore-map-count {
            position: absolute;
            top: 14px;
            left: 14px;
            z-index: 1000;
            background: #fff;
            color: #333;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .22);
            padding: 7px 14px;
            font-size: 13px;
            font-weight: 500;
            font-family: "Outfit", sans-serif;
            display: flex;
            align-items: baseline;
            gap: 5px;
            pointer-events: none;
        }

        .explore-map-count b {
            font-size: 17px;
            font-weight: 700;
            color: #f28123;
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
            background: #2b6cb0;
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

    {{-- ===== TOP FULL-WIDTH FILTERING BAR (active filters + clear all) ===== --}}
    <div class="explore-topbar" id="exploreChips" style="display:none;">
        <span class="explore-topbar-label">Filtering</span>
        <div class="explore-chips-list" id="exploreChipsList"></div>
        <button type="button" class="explore-chips-clear" id="exploreChipsClear">Clear all</button>
    </div>

    <div class="explore-wrap">
        {{-- ================= LEFT : FILTER SIDEBAR ================= --}}
        <aside class="explore-sidebar">
            <div class="explore-sidebar-head">
                <div class="title">Filters</div>
                <div class="explore-showing">
                    <span>Showing</span>
                    <span><b id="exploreCount">{{ number_format($mediaList->total()) }}</b> /
                        {{ number_format($grandTotal) }}</span>
                </div>
            </div>

            <form id="exploreForm" class="explore-sidebar-body">
                {{-- SEARCH --}}
                <div class="exp-group">
                    <div class="exp-group-title">Search</div>
                    <div class="exp-search-wrap">
                        <input type="text" name="q" id="exp_q" class="exp-search-input"
                            placeholder="Search location, area...">
                        <span class="exp-search-clear" id="expQClear" title="Clear search">&times;</span>
                    </div>
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
                @php $today = date('Y-m-d'); @endphp
                <div class="exp-group">
                    <div class="exp-group-title">Availability</div>
                    <div class="exp-range">
                        <div class="exp-range-field">
                            <label>From</label>
                            {{-- past dates are not selectable (min = today) --}}
                            <input type="date" name="from_date" id="exp_from_date" class="exp-range-input"
                                min="{{ $today }}">
                        </div>
                        <div class="exp-range-field">
                            <label>To</label>
                            <input type="date" name="to_date" id="exp_to_date" class="exp-range-input"
                                min="{{ $today }}">
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
                const color = selected ? '#2b6cb0' : '#f28123';
                const w = selected ? 40 : 32;
                const h = selected ? 54 : 44;
                const svg =
                    '<svg width="' + w + '" height="' + h + '" viewBox="0 0 32 44" ' +
                    'xmlns="http://www.w3.org/2000/svg" ' +
                    // overflow:visible so the 2.5px stroke + shadow aren't clipped
                    // at the viewBox edges (that was cutting the pin's left/right)
                    'style="overflow:visible;filter:drop-shadow(0 3px 3px rgba(0,0,0,.4));">' +
                    '<path d="M16 0C7.16 0 0 7.16 0 16c0 11.5 16 28 16 28s16-16.5 16-28C32 7.16 24.84 0 16 0z" ' +
                    'fill="' + color + '" stroke="#ffffff" stroke-width="2.5"/>' +
                    '<circle cx="16" cy="16" r="6" fill="#ffffff"/>' +
                    '</svg>';
                return L.divIcon({
                    className: 'exp-pin-icon' + (selected ? ' selected' : ''),
                    html: svg,
                    iconSize: [w, h],
                    iconAnchor: [w / 2, h],
                    popupAnchor: [0, -h + 8],
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
                    '" style="color:#2b6cb0;font-size:12px;">View Details →</a>' +
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
                        // make sure Leaflet knows the real container size before fitting,
                        // otherwise edge pins land outside the (overflow:hidden) map and
                        // their left/right outer halves get clipped.
                        map.invalidateSize();
                        map.fitBounds(fitTo, {
                            // pins are bottom-anchored and ~32-48px wide, so keep extra
                            // room on every side (more on top for the tall pin tip).
                            paddingTopLeft: [55, 65],
                            paddingBottomRight: [55, 25],
                            maxZoom: 13
                        });
                    }
                }
            }

            /* ============ AJAX SEARCH (live) ============ */
            let reqToken = 0;

            // keep the sidebar count and the on-map count badge in sync
            function updateCounts(total) {
                total = Number(total) || 0;
                $('#exploreCount').text(total.toLocaleString('en-IN'));
                $('#exploreMapCountNum').text(total.toLocaleString('en-IN'));
                $('#exploreMapCountLabel').text(total === 1 ? 'result' : 'results');
            }

            function runSearch() {
                let myToken = ++reqToken;
                $('#exploreSpinner').show();

                // Drop the Budget / Media-Size params when a slider is still at its
                // FULL range (user hasn't narrowed it). Otherwise the BETWEEN check
                // silently excludes rows whose price / area_auto is NULL, so an
                // "unfiltered" search would return fewer than the initial total
                // (e.g. 259 instead of 265).
                let params = $('#exploreForm').serializeArray();

                // A range slider whose `step` doesn't evenly divide (max - min)
                // can NEVER set its handle exactly to `max` — the browser snaps it
                // down to the nearest reachable step (e.g. max 53100, step 1000 →
                // 53000). So treat the slider as "full" when each handle is at the
                // furthest value it can actually REACH, not at the raw max attr.
                // Otherwise an untouched slider looks "narrowed", its params are
                // sent, and the BETWEEN check silently drops NULL price/area rows
                // (that was the 259-vs-265 count mismatch).
                function sliderFull(hiddenMinId, hiddenMaxId, rangeMaxId) {
                    const lo = Number($('#' + rangeMaxId).attr('min'));
                    const hi = Number($('#' + rangeMaxId).attr('max'));
                    const step = Number($('#' + rangeMaxId).attr('step')) || 1;
                    const maxReachable = lo + Math.floor((hi - lo) / step) * step;
                    return Number($('#' + hiddenMinId).val()) <= lo &&
                        Number($('#' + hiddenMaxId).val()) >= maxReachable;
                }

                const priceFull = sliderFull('exp_min_price', 'exp_max_price', 'expMaxPriceRange');
                const areaFull = sliderFull('exp_min_area', 'exp_max_area', 'expMaxAreaRange');

                params = params.filter(function(p) {
                    if (priceFull && (p.name === 'min_price' || p.name === 'max_price')) return false;
                    if (areaFull && (p.name === 'min_area' || p.name === 'max_area')) return false;
                    return true;
                });

                $.ajax({
                    url: EXPLORE.searchUrl,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': EXPLORE.csrf
                    },
                    data: $.param(params),
                    success: function(res) {
                        if (myToken !== reqToken) return; // ignore stale response
                        updateCounts(res.total_count);
                        renderMarkers(res.markers, true);
                    },
                    complete: function() {
                        if (myToken === reqToken) $('#exploreSpinner').hide();
                    }
                });
            }

            /* ---- active filter chips (every selected entry, with a cancel icon) ---- */
            function buildChip(label, onRemove) {
                const $x = $('<span class="exp-chip-x" title="Remove">&times;</span>')
                    .on('click', onRemove);
                return $('<span class="exp-chip"></span>')
                    .append($('<span class="exp-chip-label"></span>').text(label))
                    .append($x);
            }

            function renderChips() {
                const $list = $('#exploreChipsList').empty();
                let count = 0;

                // text search term
                const q = $('#exp_q').val().trim();
                if (q) {
                    $list.append(buildChip('Search: "' + q + '"', function() {
                        $('#exp_q').val('');
                        runSearch();
                        renderChips();
                    }));
                    count++;
                }

                // every checked filter option
                $('#exploreForm input[type="checkbox"]:checked').each(function() {
                    const cb = this;
                    const label = $(cb).next('span').text().trim() || cb.value;
                    $list.append(buildChip(label, function() {
                        cb.checked = false;
                        $(cb).trigger('change'); // unchecks → runs search → re-renders chips
                    }));
                    count++;
                });

                // explicit flex/none so the flex top bar always lays out correctly
                $('#exploreChips').css('display', count > 0 ? 'flex' : 'none');
            }

            // live update on any checkbox change
            $(document).on('change', '#exploreForm input[type="checkbox"]', function() {
                runSearch();
                renderChips();
            });

            // "Clear all" inside the chip bar = same as the footer clear button
            $('#exploreChipsClear').on('click', function() {
                $('#exploreClear').trigger('click');
            });

            // show/hide the search-bar cancel icon based on its content
            function toggleQClear() {
                $('.exp-search-wrap').toggleClass('has-text', $('#exp_q').val().length > 0);
            }

            // debounced text search
            let qTimer = null;
            $('#exp_q').on('input', function() {
                toggleQClear();
                clearTimeout(qTimer);
                qTimer = setTimeout(function() {
                    runSearch();
                    renderChips();
                }, 400);
            });

            // cancel icon clears the search bar and refreshes results immediately
            $('#expQClear').on('click', function() {
                $('#exp_q').val('').focus();
                toggleQClear();
                clearTimeout(qTimer);
                runSearch();
                renderChips();
            });

            // date pickers — keep the window valid, then search immediately
            const TODAY = "{{ $today }}"; // no past dates allowed
            $('#exp_from_date').on('change', function() {
                // "To" can never be earlier than the chosen "From" (and never past today)
                $('#exp_to_date').attr('min', this.value || TODAY);
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
                // keep past dates disabled after clearing (min stays at today)
                $('#exp_to_date').attr('min', TODAY);
                // restore sliders to their full range + repaint fill/labels
                priceSlider.update(false);
                areaSlider.update(false);
                $('.grp-filter').trigger('input');
                toggleQClear();
                runSearch();
                renderChips();
            });

            // show any chips / search-clear icon that exist on first load
            toggleQClear();
            renderChips();

            /* ============ INITIAL RENDER ============ */
            // defer one tick so the flex layout (header + sidebar) has settled and
            // the map container reports its real width — prevents edge pins from
            // being clipped on first paint.
            setTimeout(function() {
                map.invalidateSize();
                renderMarkers(EXPLORE.initialMarkers, true);
            }, 0);

            // keep the map sized correctly (and edge pins visible) on resize
            let resizeTimer = null;
            $(window).on('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    map.invalidateSize();
                }, 150);
            });

            // The browser can restore previously-ticked filters on reload WITHOUT
            // firing change events, so the chips bar and the map would stay out of
            // sync with the checked boxes. Re-sync both once the page fully loads.
            $(window).on('load', function() {
                renderChips();
                const hasFilters =
                    $('#exploreForm input[type="checkbox"]:checked').length > 0 ||
                    $('#exp_q').val().trim() !== '';
                if (hasFilters) runSearch();
            });
        });
    </script>
@endsection

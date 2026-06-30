@extends('website.layout')

@section('title', 'Search Media')

@section('content')
    <style>
        .leaflet-container {
            font-family: "Outfit", sans-serif;
        }

        /* Pin with a count badge for stacked / repeated-location media */
        .multi-media-marker,
        .media-pin-marker {
            background: transparent;
            border: none;
        }

        /* gentle bounce to highlight the selected pin */
        .media-pin-marker.selected {
            animation: selectedMarkerBounce 0.7s ease infinite alternate;
            transform-origin: center bottom;
            z-index: 1000 !important;
        }

        .multi-media-pin {
            position: relative;
            width: 30px;
            height: 42px;
            background: #f28123;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .multi-media-count {
            transform: rotate(45deg);
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            font-family: "Outfit", sans-serif;
        }

        /* white centre dot for a single-location orange pin */
        .single-media-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #fff;
            transform: rotate(45deg);
        }

        /* ⭐ Highlighted (selected) marker — stands out via colour + bounce */
        .selected-media-marker {
            background: transparent;
            border: none;
            z-index: 1000 !important;
        }

        .selected-media-pin {
            position: relative;
            width: 38px;
            height: 52px;
            /* darker orange so the selected pin stays on-brand (no red) */
            background: #d96f15;
            border: 3px solid #fff;
            border-radius: 50% 50% 50% 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            transform-origin: center bottom;
            animation: selectedMarkerBounce 0.7s ease infinite alternate;
        }

        .selected-media-pin .multi-media-count {
            color: #fff;
        }

        @keyframes selectedMarkerBounce {
            from {
                transform: translateY(0);
            }

            to {
                transform: translateY(-7px);
            }
        }

        /* Cluster circle showing total record count */
        .media-cluster {
            background: transparent;
        }

        .media-cluster .cluster-bubble {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: #f28123;
            border: 3px solid #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            font-family: "Outfit", sans-serif;
        }

        .leaflet-popup-content {
            width: auto !important;
            max-width: 500px;
            margin: 10px 12px !important;
        }

        .leaflet-popup-content-wrapper {
            border-radius: 12px !important;
            padding: 0 !important;
            overflow: hidden;
        }

        .leaflet-popup-content-wrapper .leaflet-popup-content {
            margin: 0 !important;
            padding: 10px !important;
        }

        .single-latest-news {
            height: 100%;
            display: flex;
            flex-direction: row;
        }

        .news-text-box {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 10px 10px 0px 10px;
        }

        .news-text-box h3 {
            font-size: 1.7rem;
            font-weight: 600;
            line-height: 1.4;
        }

        .blog-meta {
            font-size: 16px;
            margin-bottom: 8px;
            color: #6c757d
        }

        .media-price {
            font-size: 16px;
            font-weight: 700;
            color: #f28123;
            /* margin: 6px 0 10px; */
        }

        .card-actions {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-btn {
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 30px;
            font-weight: 600;
        }

        .card-btn.cart {
            background: #F28123;
            color: #fff;
        }

        .card-btn.contact {
            background: #ffb100;
            color: #000;
        }

        .card-btn.read {
            background: transparent;
            color: #f28123;
        }

        .pricepermonth {
            color: #a0a0a0;
            font-weight: 400;
        }
    </style>

    {{-- SEARCH FORM --}}
    <div class="mt-3">
        @include('website.search-form')
    </div>
    @if (session('info'))
        <div class="alert alert-warning mt-2">{{ session('info') }}</div>
    @endif

    @if (session('success'))
        <div class="alert alert-success mt-2">{{ session('success') }}</div>
    @endif
    @if ($mediaList->count())
        <div class="container-fluid mt-4">
            <div class="row">

                {{-- LEFT: Media Cards --}}
                <div class="col-lg-6 col-md-6 col-sm-12" style="height:78vh; overflow-y:auto;">
                    <div class="row" id="media-container">
                        @include('website.media-home-list', ['mediaList' => $mediaList])
                    </div>
                </div>


                {{-- RIGHT: Google Map --}}
                <div class="col-lg-6 col-md-6 col-sm-12">
                    <div id="map" style="height:78vh; width:100%; border-radius:10px;"></div>
                </div>

            </div>
        </div>

        {{-- Lazy Loader --}}
        <div class="text-center my-4 d-none" id="lazy-loader">
            <span class="spinner-border text-warning"></span>
        </div>
    @endif

    <script>
        let page = 1;
        let loading = false;
        let noMoreData = false;
        let lazyTriggered = false; // ⭐ NEW

        $(window).on('scroll', function() {

            if (loading || noMoreData) return;

            if ($(window).scrollTop() + $(window).height() >= $(document).height() - 300) {

                loading = true;
                lazyTriggered = true; // ⭐ user actually scrolled
                page++;

                let loader = $('#lazy-loader');
                loader.removeClass('d-none').html(
                    '<span class="spinner-border text-warning"></span>'
                );

                $.ajax({
                    // url: "{{ route('website.search') }}?page=" + page,
                    // type: "POST",
                    // data: $('#searchForm').serialize(),
                    url: "{{ route('website.search') }}",
                    type: "POST",
                    data: $('#searchForm').serialize() + '&page=' + page,

                    success: function(html) {

                        if ($.trim(html) === '') {

                            if (lazyTriggered) {
                                loader.html(''); //No more media
                            } else {
                                loader.addClass('d-none');
                            }

                            noMoreData = true;
                            return;
                        }

                        $('#media-container').append(html);
                        loader.addClass('d-none');
                        loading = false;
                    },
                    error: function() {
                        loader.addClass('d-none');
                        loading = false;
                    }
                });
            }
        });
    </script>


@endsection
@if ($mediaList->count())
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <script>
        let mediaDetailsRoute = "{{ route('website.media-details', 'ID_PLACEHOLDER') }}";

        function initLeafletMap() {
            console.log('%c[SEARCH MAP] v10 — bound-popup + stale-pin fix LOADED', 'background:#2b6cb0;color:#fff;padding:2px 8px;font-weight:bold;');
            let defaultLat = {{ optional($mapMedia->first())->latitude ?? 19.997453 }};
            let defaultLng = {{ optional($mapMedia->first())->longitude ?? 73.789803 }};

            let map = L.map('map').setView([defaultLat, defaultLng], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            let mediaList = @json($mapMedia->values());

            // Cluster group: overlapping / very-close pins collapse into one
            // numbered circle showing the TOTAL record count, and split apart
            // as the user zooms in. Markers at the exact same point fan out
            // (spiderfy) on click.
            let clusterGroup = L.markerClusterGroup({
                showCoverageOnHover: false,
                spiderfyOnMaxZoom: true,
                maxClusterRadius: 40,
                iconCreateFunction: function(cluster) {
                    // Sum the record count of every child marker so the badge
                    // reflects records, not just unique locations.
                    let total = 0;
                    cluster.getAllChildMarkers().forEach(function(mk) {
                        total += (mk.recordCount || 1);
                    });
                    let size = total < 10 ? 34 : (total < 100 ? 40 : 48);
                    return L.divIcon({
                        html: '<div class="cluster-bubble"><span>' + total + '</span></div>',
                        className: 'media-cluster',
                        iconSize: L.point(size, size)
                    });
                }
            });

            let grouped = {};

            // ⭐ id → marker index + currently highlighted marker, for two-way
            // synchronisation between the left-side list and the map.
            let markersByMediaId = {};
            let currentSelectedMarker = null;

            // Clean SVG teardrop pin (crisp at any zoom). When `count` > 1 it shows
            // a white badge with the number; otherwise a small white centre dot.
            function buildPinIcon(opts) {
                const w = opts.w,
                    h = opts.h,
                    color = opts.color,
                    count = opts.count || 0,
                    cls = opts.className || 'media-pin-marker';
                const inner = count > 1 ?
                    '<circle cx="16" cy="15" r="9.5" fill="#fff"/>' +
                    '<text x="16" y="19.5" text-anchor="middle" font-size="12.5" font-weight="700" ' +
                    'fill="' + color + '" font-family="Outfit, sans-serif">' + count + '</text>' :
                    '<circle cx="16" cy="16" r="6" fill="#fff"/>';
                const svg =
                    '<svg width="' + w + '" height="' + h + '" viewBox="0 0 32 44" ' +
                    'xmlns="http://www.w3.org/2000/svg" ' +
                    // overflow:visible so the 2.5px stroke + shadow aren't clipped
                    // at the viewBox edges (that was cutting the pin's left/right)
                    'style="overflow:visible;filter:drop-shadow(0 3px 3px rgba(0,0,0,.4));">' +
                    '<path d="M16 0C7.16 0 0 7.16 0 16c0 11.5 16 28 16 28s16-16.5 16-28C32 7.16 24.84 0 16 0z" ' +
                    'fill="' + color + '" stroke="#ffffff" stroke-width="2.5"/>' + inner + '</svg>';
                return L.divIcon({
                    className: cls,
                    html: svg,
                    iconSize: [w, h],
                    iconAnchor: [w / 2, h],
                    popupAnchor: [0, -h + 8]
                });
            }

            // A distinct, larger BLUE pin used to highlight the selected marker —
            // same blue (#2b6cb0) as the Map/explore page's active pin.
            function buildSelectedIcon(count) {
                return buildPinIcon({
                    w: 40,
                    h: 54,
                    color: '#2b6cb0',
                    count: count,
                    className: 'media-pin-marker selected'
                });
            }

            function selectMarker(marker) {
                if (currentSelectedMarker && currentSelectedMarker !== marker) {
                    currentSelectedMarker.setIcon(currentSelectedMarker._defaultIcon2);
                }
                marker.setIcon(marker._selectedIcon);
                marker.setZIndexOffset(1000);
                currentSelectedMarker = marker;
            }

            function highlightCards(ids) {
                document.querySelectorAll('.media-card.selected')
                    .forEach(c => c.classList.remove('selected'));
                ids.forEach(id => {
                    let card = document.getElementById('media-card-' + id);
                    if (card) card.classList.add('selected');
                });
            }

            function scrollCardIntoView(id) {
                let card = document.getElementById('media-card-' + id);
                if (card) card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            // Group records that share the EXACT same coordinate (6 decimals)
            // into a single marker whose popup lists all of them.
            mediaList.forEach(m => {
                if (m.latitude == null || m.longitude == null) return;
                // Group by exact stored coordinate (6 decimals ≈ 0.1m) so the
                // marker count matches the repeated lat/long count exactly and
                // every record at that point shows in the popup.
                let roundedLat = parseFloat(m.latitude).toFixed(6);
                let roundedLng = parseFloat(m.longitude).toFixed(6);
                let key = roundedLat + ',' + roundedLng;
                if (!grouped[key]) grouped[key] = [];
                grouped[key].push(m);
            });

            for (let key in grouped) {
                let items = grouped[key];
                let lat = parseFloat(items[0].latitude);
                let lng = parseFloat(items[0].longitude);

                // Clean orange SVG pin — a count badge when several records share
                // the spot, otherwise a single pin with a white centre dot.
                let defaultIcon = buildPinIcon({
                    w: items.length > 1 ? 34 : 32,
                    h: items.length > 1 ? 46 : 44,
                    color: '#f28123',
                    count: items.length
                });
                let marker = L.marker([lat, lng], { icon: defaultIcon });

                // Remember how many records this marker represents so the
                // cluster badge can sum them correctly.
                marker.recordCount = items.length;

                // Store data needed for list ↔ map synchronisation.
                marker._defaultIcon2 = defaultIcon;
                marker._selectedIcon = buildSelectedIcon(items.length);
                marker._items = items;
                marker._lat = lat;
                marker._lng = lng;
                items.forEach(m => {
                    markersByMediaId[m.id] = marker;
                });

                clusterGroup.addLayer(marker);

                marker.on('click', function() {
                    let cards = '';

                    items.forEach(m => {
                        let url = mediaDetailsRoute.replace('ID_PLACEHOLDER', btoa(m.id));
                        let sqft = (parseFloat(m.width) * parseFloat(m.height)).toFixed(0);
                        let price = '₹' + Number(m.price).toLocaleString('en-IN');
                        cards += `
                        <div style="
                            min-width:160px;
                            max-width:160px;
                            background:#fff;
                            border-radius:10px;
                            overflow:hidden;
                            box-shadow:0 2px 8px rgba(0,0,0,0.12);
                            flex-shrink:0;
                            display:flex;
                            flex-direction:column;
                        ">
                            <div style="width:100%;height:100px;overflow:hidden;">
                                <img src="{{ config('fileConstants.IMAGE_VIEW') }}/${m.first_image}"
                                     style="width:100%;height:100%;object-fit:cover;" />
                            </div>
                            <div style="padding:8px;display:flex;flex-direction:column;gap:4px;flex:1;">
                                <div style="
                                
                                    font-size:12px;
                                    font-weight:700;
                                    color:#222;
                                    font-family: "Outfit", sans-serif;
                                    line-height:1.3;
                                    display:-webkit-box;
                                    -webkit-line-clamp:2;
                                    -webkit-box-orient:vertical;
                                    overflow:hidden;
                                ">${m.media_title} ${m.area_name}</div>
                                ${m.hoarding_code ? `<div style="
                                    font-size:10px;
                                    font-weight:600;
                                    color:#fff;
                                    background:#f28123;
                                    border-radius:4px;
                                    padding:1px 6px;
                                    align-self:flex-start;
                                    letter-spacing:.3px;
                                ">${m.hoarding_code}</div>` : ''}
                                <div style="font-size:11px;color:#888;">${sqft} sqft</div>
                                <div style="font-size:10px;color:#999;display:flex;align-items:center;gap:3px;">
                                    📍 ${parseFloat(m.latitude).toFixed(6)}, ${parseFloat(m.longitude).toFixed(6)}
                                </div>
                                <div style="font-size:13px;font-weight:700;color:#f28123;">${price}</div>
                                <a href="${url}" style="
                                    display:block;
                                    margin-top:auto;
                                    padding:5px 0;
                                    background:#f28123;
                                    color:#fff;
                                    border-radius:20px;
                                    text-align:center;
                                    font-size:11px;
                                    font-weight:600;
                                    text-decoration:none;
                                ">View Details</a>
                            </div>
                        </div>`;
                    });

                    // Show up to 3 cards side-by-side; the rest are reachable by scrolling.
                    let visibleCols = Math.min(items.length, 3);
                    let rowWidth = items.length === 1 ? 180 : (visibleCols * 170 + 20);

                    let header = items.length > 1
                        ? `<div style="
                                font-size:12px;
                                font-weight:700;
                                color:#222;
                                font-family:'Outfit',sans-serif;
                                padding:2px 2px 6px;
                            ">${items.length} media at this location${items.length > 3 ? ' — scroll to see all →' : ''}</div>`
                        : '';

                    let html = `
                    <div style="max-width:${rowWidth}px;">
                        ${header}
                        <div style="
                            display:flex;
                            gap:10px;
                            overflow-x:auto;
                            padding:6px 2px 8px;
                            scrollbar-width:thin;
                        ">${cards}</div>
                    </div>`;

                    // Bind the popup to THIS marker (exactly like the explore/Map
                    // page) so the popup and the blue selected pin are the SAME
                    // object — they can never end up at different locations.
                    marker.bindPopup(html, {
                        maxWidth: 560,
                        className: 'map-media-popup'
                    });

                    // ⭐ Map → list: turn this marker blue, open its popup, and
                    // highlight its card(s).
                    selectMarker(marker);
                    marker.openPopup();
                    let ids = items.map(m => m.id);
                    highlightCards(ids);
                    scrollCardIntoView(items[0].id);
                });
            }

            map.addLayer(clusterGroup);

            // The cluster rebuilds every marker's icon whenever it zooms or
            // un-clusters. Re-assert after each animation that ONLY the currently
            // selected marker is blue — otherwise a previously-selected marker can
            // stay stuck blue (the "stale blue pin" bug) while a new one is chosen.
            clusterGroup.on('animationend', function() {
                let seen = new Set();
                for (let mid in markersByMediaId) {
                    let mk = markersByMediaId[mid];
                    if (seen.has(mk)) continue;
                    seen.add(mk);
                    let wantBlue = (mk === currentSelectedMarker);
                    let isBlue = (mk.options.icon === mk._selectedIcon);
                    if (wantBlue && !isBlue) mk.setIcon(mk._selectedIcon);
                    else if (!wantBlue && isBlue) mk.setIcon(mk._defaultIcon2);
                }
            });

            // Zoom/pan so every marker is visible at once.
            let bounds = clusterGroup.getBounds();
            if (bounds.isValid()) {
                map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
            }

            // ⭐ List → map: clicking a card focuses the map on that hoarding,
            // expands its cluster, highlights the marker and opens its popup.
            // Delegated so lazy-loaded cards work too. Clicks on links/buttons
            // inside the card keep their normal behaviour.
            let mediaContainer = document.getElementById('media-container');
            if (mediaContainer) {
                mediaContainer.addEventListener('click', function(e) {
                    if (e.target.closest('a, button')) return;

                    let card = e.target.closest('.media-card');
                    if (!card) return;

                    let id = card.getAttribute('data-media-id');
                    highlightCards([id]);

                    let marker = markersByMediaId[id];
                    if (!marker) return;

                    // zoomToShowLayer expands the enclosing cluster (if any) so
                    // the marker becomes individually visible, then its own click
                    // handler turns it blue and opens the bound popup. No extra
                    // setView here — a second zoom only triggers another re-cluster.
                    clusterGroup.zoomToShowLayer(marker, function() {
                        marker.fire('click');
                    });
                });
            }
        }

        window.onload = initLeafletMap;
    </script>
@endif

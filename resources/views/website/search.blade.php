@extends('website.layout')

@section('title', 'Search Media')

@section('content')
    <style>
        .leaflet-container {
            font-family: "Outfit", sans-serif;
        }

        /* Pin with a count badge for stacked / repeated-location media */
        .multi-media-marker {
            background: transparent;
            border: none;
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

    <script>
        let mediaDetailsRoute = "{{ route('website.media-details', 'ID_PLACEHOLDER') }}";

        function initLeafletMap() {
            let defaultLat = {{ optional($mapMedia->first())->latitude ?? 19.997453 }};
            let defaultLng = {{ optional($mapMedia->first())->longitude ?? 73.789803 }};

            let map = L.map('map').setView([defaultLat, defaultLng], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            let mediaList = @json($mapMedia->values());

            let grouped = {};

            // Group markers that share the same (or near-identical) location.
            // latitude/longitude come back as decimal strings, so we round to
            // ~5 decimals (~1m) to make sure repeated/overlapping pins merge.
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

                let marker;
                if (items.length > 1) {
                    // Show a count badge so stacked pins are visible on the map.
                    marker = L.marker([lat, lng], {
                        icon: L.divIcon({
                            className: 'multi-media-marker',
                            html: '<div class="multi-media-pin">'
                                + '<span class="multi-media-count">' + items.length + '</span>'
                                + '</div>',
                            iconSize: [30, 42],
                            iconAnchor: [15, 42],
                            popupAnchor: [0, -38]
                        })
                    }).addTo(map);
                } else {
                    marker = L.marker([lat, lng]).addTo(map);
                }

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

                    L.popup({
                            maxWidth: 560,
                            className: 'map-media-popup'
                        })
                        .setLatLng([lat, lng])
                        .setContent(html)
                        .openOn(map);
                });
            }
        }

        window.onload = initLeafletMap;
    </script>
@endif

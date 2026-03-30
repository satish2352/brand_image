@extends('website.layout')

@section('title', 'Search Media')

@section('content')
    <style>
        .leaflet-popup-content {
            width: auto !important;
            max-width: 320px;
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
            let defaultLat = {{ $mediaList[0]->latitude ?? 19.997453 }};
            let defaultLng = {{ $mediaList[0]->longitude ?? 73.789803 }};

            let map = L.map('map').setView([defaultLat, defaultLng], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            let mediaList = @json($mediaList->values());

            let grouped = {};

            mediaList.forEach(m => {
                let key = m.latitude + ',' + m.longitude;
                if (!grouped[key]) grouped[key] = [];
                grouped[key].push(m);
            });

            for (let key in grouped) {
                let items = grouped[key];
                let lat = items[0].latitude;
                let lng = items[0].longitude;

                let marker = L.marker([lat, lng]).addTo(map);

                marker.on('click', function() {
                    let html = `<div style="display:flex;gap:10px;overflow-x:auto;max-width:400px;">`;

                    items.forEach(m => {
                        let url = mediaDetailsRoute.replace('ID_PLACEHOLDER', btoa(m.id));
                        html += `
                        <div style="margin-bottom:10px;">
                            <img src="{{ config('fileConstants.IMAGE_VIEW') }}/${m.first_image}"
                                 style="width:100%;border-radius:6px;padding-bottom: 12px;">
                            <b style="font-size:15px; padding:10px 0px 10px 0px;">${m.media_title} ${m.area_name}</b><br>
                            <a href="${url}" class="btn card-btn cart">View Details</a>
                        </div>`;
                    });

                    html += `</div>`;

                    L.popup()
                        .setLatLng([lat, lng])
                        .setContent(html)
                        .openOn(map);
                });
            }
        }

        window.onload = initLeafletMap;
    </script>
@endif

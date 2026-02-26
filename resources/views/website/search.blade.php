@extends('website.layout')

@section('title', 'Search Media')

@section('content')
    <style>
        .single-latest-news {
            height: 100%;
            display: flex;
            flex-direction: row;
        }

        .news-text-box {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px 20px 0px 20px;
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
            font-size: 18px;
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
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    function initLeafletMap() {
        let defaultLat = {{ $mediaList[0]->latitude ?? 19.997453 }};
        let defaultLng = {{ $mediaList[0]->longitude ?? 73.789803 }};

        let map = L.map('map').setView([defaultLat, defaultLng], 12);

        // Load free OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        @foreach ($mediaList as $m)
            L.marker([{{ $m->latitude }}, {{ $m->longitude }}])
                .addTo(map)
                .bindPopup(`
    <div style="text-align:center;">
        <img src="{{ config('fileConstants.IMAGE_VIEW') . $m->first_image }}" 
             style="width:100%;max-width:150px;border-radius:6px;margin-bottom:5px;">

        <a href="{{ route('website.media-details', base64_encode($m->id)) }}" 
           style="font-weight:bold; text-decoration:none; color:#007bff;"
          >
            {{ $m->media_title }}
        </a><br>

        <span style="color:#555;">{{ $m->area_name }}</span>
    </div>
`);
        @endforeach
    }

    window.onload = initLeafletMap;
</script>

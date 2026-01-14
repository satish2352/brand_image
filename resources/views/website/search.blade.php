@extends('website.layout')

@section('title', 'Search Media')

@section('content')
    <style>
        .single-latest-news {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .news-text-box {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px;
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
            color: #28a745;
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
            background: #28a745;
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

    {{-- SEARCH RESULTS --}}
    @if ($mediaList->count())
        <div class="container mt-4">
            <div class="row" id="media-container">
                @include('website.media-home-list', ['mediaList' => $mediaList])
            </div>
        </div>

        <div class="text-center my-4  d-none" id="lazy-loader">
            <span class="spinner-border text-warning"></span>
        </div>
    @else
        <div class="container mt-4 text-center">
            <h5>No media found for selected filters</h5>
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
                                loader.html('No more media');
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

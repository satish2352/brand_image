@extends('website.layout')

@section('title', 'Home')

@section('content')

    {{-- <div class="container-fluid g-0">
        <div id="carouselExampleCaptions" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                <img src="{{ asset('asset/images/website/banner.webp') }}" class="d-block w-100" alt="...">
                </div>
                <div class="carousel-item">
                <img src="{{ asset('asset/images/website/banner.webp') }}" class="d-block w-100" alt="...">
                </div>
                <div class="carousel-item">
                <img src="{{ asset('asset/images/website/banner.webp') }}" class="d-block w-100" alt="...">
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div> --}}

    {{-- Search Section Include --}}
    {{-- @include('website.search') --}}

    @include('website.index_2')

    {{-- @include('website.search') --}}
    {{-- @include('website.mediacarts') --}}
<div class="row" id="media-container">
    @include('website.media-home-list', ['mediaList' => $mediaList])
</div>

<div class="text-center my-4" id="lazy-loader" style="display:none;">
    <span class="spinner-border text-warning"></span>
</div>

<script>
let page = 1;
let loading = false;

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$(window).on('scroll', function () {

    if (loading) return;

    if ($(window).scrollTop() + $(window).height() >= $('#lazy-loader').offset().top - 200) {

        loading = true;
        page++;

        $('#lazy-loader').show();

        // ✅ ALWAYS serialize search form
        let formData = $('#searchForm').serialize();

        $.ajax({
            url: "{{ route('website.search') }}?page=" + page,
            type: "POST",
            data: formData,
            success: function (html) {

                if ($.trim(html) === '') {
                    $('#lazy-loader').html('No more media');
                    $(window).off('scroll');
                    return;
                }

                $('#media-container').append(html);
                loading = false;
                $('#lazy-loader').hide();
            },
            error: function () {
                loading = false;
                $('#lazy-loader').hide();
            }
        });
    }
});
</script>


    {{-- <script>
let page = 1;
let loading = false;

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$(window).on('scroll', function () {

    if (loading) return;

    if ($(window).scrollTop() + $(window).height() >= $('#lazy-loader').offset().top - 200) {

        loading = true;
        page++;

        $('#lazy-loader').show();

        let formData = $('#searchForm').length
            ? $('#searchForm').serialize()
            : {_token: "{{ csrf_token() }}"};

        $.ajax({
            url: "{{ route('website.search') }}?page=" + page,
            type: "POST",
            data: formData,
            success: function (html) {

                if ($.trim(html) === '') {
                    $('#lazy-loader').html('No more media');
                    $(window).off('scroll');
                    return;
                }

                $('#media-container').append(html);
                loading = false;
                $('#lazy-loader').hide();
            },
            error: function () {
                loading = false;
                $('#lazy-loader').hide();
            }
        });
    }
});
</script> --}}


@endsection

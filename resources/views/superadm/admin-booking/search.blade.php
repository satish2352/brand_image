@extends('superadm.layout.master')

@section('content')

<div class="container-fluid py-4">

    {{-- 🔍 SEARCH FORM (ONLY ONCE) --}}
    @include('superadm.admin-booking.search-form')

    {{-- 📦 SEARCH RESULTS --}}
    <div class="row mt-4" id="media-container">
        @if($mediaList->count())
            @include('superadm.admin-booking.admin-media-home-list', ['mediaList' => $mediaList])
        @else
            <div class="col-12 text-center">
                <h5 class="text-muted">No media found</h5>
            </div>
        @endif
    </div>

    {{-- 🔄 LOADER --}}
    <div class="text-center my-4 d-none" id="lazy-loader">
        <span class="spinner-border text-warning"></span>
    </div>

</div>

@endsection


{{-- ✅ ADD SCRIPT HERE --}}
@section('scripts')

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
let page = 1;
let loading = false;
let noMoreData = false;

$(window).on('scroll', function () {

    if (loading || noMoreData) return;

    if ($(window).scrollTop() + $(window).height() >= $(document).height() - 300) {

        loading = true;
        page++;

        $('#lazy-loader').removeClass('d-none');

        $.ajax({
            url: "{{ route('admin-booking.search') }}",
            type: "POST",
            data: $('#searchForm').serialize() + '&page=' + page,

            success: function (html) {

                if ($.trim(html) === '') {
                    noMoreData = true;
                    $('#lazy-loader').html('No more media');
                    return;
                }

                $('#media-container').append(html);
                $('#lazy-loader').addClass('d-none');
                loading = false;
            },
            error: function () {
                $('#lazy-loader').addClass('d-none');
                loading = false;
            }
        });
    }
});
</script>

@endsection

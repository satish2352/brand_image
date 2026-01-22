@extends('superadm.layout.master')

@section('content')
    <style>
        .pagination {
            justify-content: center;
        }

        .pagination .page-link {
            border-radius: 6px;
            padding: 6px 12px;
        }

        .pagination .page-item.active .page-link {
            background-color: #dc3545;
            border-color: #dc3545;
        }
    </style>

    <div class="container-fluid py-4">

        {{-- SEARCH --}}
        @include('superadm.admin-booking.search-form')

        {{-- RESULTS --}}
        {{-- <div class="row mt-4" id="media-container">
            @includeWhen($mediaList->count(), 'superadm.admin-booking.admin-media-home-list', [
                'mediaList' => $mediaList,
            ])
        </div> --}}
        {{-- EMPTY RESULT MESSAGE --}}
        @if (request()->isMethod('post') && $mediaList->isEmpty())
            <div class="alert alert-light text-center mt-4 ">
                <b>
                    <h4>No media found for the selected filters.</h4>
                </b>
            </div>
        @endif

        {{-- RESULTS --}}
        <div class="row mt-4" id="media-container">
            @includeWhen($mediaList->count(), 'superadm.admin-booking.admin-media-home-list', [
                'mediaList' => $mediaList,
            ])
        </div>
        {{-- FIX PAGINATION ALWAYS VISIBLE --}}
        <div class="mt-4 text-center" id="pagination-links">
            {{ $mediaList->appends(request()->all())->links() }}
        </div>

        {{-- LOADER --}}
        <div class="text-center my-4 d-none" id="lazy-loader">
            <span class="spinner-border text-warning"></span>
        </div>
    </div>
@endsection
@section('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // start from current pagination number
        let page = {{ $mediaList->currentPage() }};
        let lastPage = {{ $mediaList->lastPage() }};
        let loading = false;

        $(window).on('scroll', function() {

            if (loading) return;
            if (page >= lastPage) return;

            if ($(window).scrollTop() + $(window).height() >= $(document).height() - 300) {

                loading = true;
                page++;

                $('#lazy-loader').removeClass('d-none');

                $.ajax({
                    url: "{{ route('admin-booking.search') }}?page=" + page,
                    type: "POST",
                    data: $('#searchForm').serialize(),

                    success: function(html) {
                        html = html.trim();
                        if (html.length) {
                            $('#media-container').append(html);
                        }

                        $('#lazy-loader').addClass('d-none');
                        loading = false;
                    },
                    error: function() {
                        $('#lazy-loader').addClass('d-none');
                        loading = false;
                    }
                });
            }
        });
    </script>

    <script>
        $(document).ready(function() {
            $(".pagination .page-item:first-child .page-link").html('<i class="fa fa-angle-left"></i> Prev');
            $(".pagination .page-item:last-child .page-link").html('Next <i class="fa fa-angle-right"></i>');
        });
    </script>
@endsection

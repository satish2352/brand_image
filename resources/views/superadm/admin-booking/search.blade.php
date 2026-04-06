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
        {{-- EMPTY RESULT MESSAGE --}}
        <div id="empty-result-msg" class="alert alert-light text-center mt-4 d-none">
            <b><h4>No media found for the selected filters.</h4></b>
        </div>

        {{-- RESULTS --}}
        <div class="row mt-4" id="media-container">
            @includeWhen($mediaList->count(), 'superadm.admin-booking.admin-media-home-list', [
                'mediaList' => $mediaList,
            ])
        </div>
        {{-- PAGINATION --}}
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
        let page     = {{ $mediaList->currentPage() }};
        let lastPage = {{ $mediaList->lastPage() }};
        let loading  = false;

        // ── AJAX SEARCH (no full page reload) ──────────────────────────
        $('#searchForm').on('submit', function (e) {
            e.preventDefault();

            // reset lazy-scroll state
            page     = 1;
            lastPage = 1;
            loading  = false;

            $('#media-container').html('');
            $('#pagination-links').html('');
            $('#empty-result-msg').addClass('d-none');
            $('#lazy-loader').removeClass('d-none');

            $.ajax({
                url:  "{{ route('admin-booking.search') }}",
                type: 'POST',
                data: $(this).serialize() + '&_search=1',
                success: function (res) {
                    page     = res.current_page;
                    lastPage = res.last_page;

                    $('#media-container').html(res.html);
                    $('#pagination-links').html(res.pagination);
                    $('.result-badge .count').text(res.total_count + ' Results');

                    if (res.is_empty) {
                        $('#empty-result-msg').removeClass('d-none');
                    }
                },
                complete: function () {
                    $('#lazy-loader').addClass('d-none');
                }
            });
        });

        // ── LAZY SCROLL (append next page) ─────────────────────────────
        $(window).on('scroll', function () {
            if (loading || page >= lastPage) return;

            if ($(window).scrollTop() + $(window).height() >= $(document).height() - 300) {
                loading = true;
                page++;
                $('#lazy-loader').removeClass('d-none');

                $.ajax({
                    url:  "{{ route('admin-booking.search') }}?page=" + page,
                    type: 'POST',
                    data: $('#searchForm').serialize(),
                    success: function (html) {
                        html = html.trim();
                        if (html.length) {
                            $('#media-container').append(html);
                        }
                    },
                    complete: function () {
                        $('#lazy-loader').addClass('d-none');
                        loading = false;
                    }
                });
            }
        });
    </script>

    <script>
        $(document).ready(function () {
            $(".pagination .page-item:first-child .page-link").html('<i class="fa fa-angle-left"></i> Prev');
            $(".pagination .page-item:last-child .page-link").html('Next <i class="fa fa-angle-right"></i>');
        });
    </script>
@endsection

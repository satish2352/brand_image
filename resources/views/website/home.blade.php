@extends('website.layout')

@section('title', 'Home')

@section('content')

    @include('website.index')

@endsection

@section('scripts')
    {{-- Feature 4: the first search from the home page lands on the new Explore page.
         Scoped to the home page only — the existing /search page is untouched. --}}
    <script>
        $(function () {
            $('#searchForm').on('submit', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                const params = $(this).serialize();
                window.location.href = "{{ route('website.explore') }}" + (params ? '?' + params : '');
            });
        });
    </script>
@endsection

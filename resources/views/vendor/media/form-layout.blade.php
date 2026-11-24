{{-- Wraps the shared admin Media Management form (create / edit views) in the
     vendor panel chrome. Those views extend $formLayout, which is this file. --}}
@extends('vendor.layout')
@section('title', $formHeading ?? 'Media')
@section('crumb')
    <a href="{{ route('vendor.inventory') }}">Inventory</a> / {{ $formHeading ?? 'Media' }}
@endsection
@section('heading', $formHeading ?? 'Media')

{{-- Vendor-only look for the shared form: numbered sections, panel-style fields,
     photo drop areas and a sticky action bar. The admin form is unchanged. --}}
@push('vendor-head')
    <link rel="stylesheet" href="{{ asset('asset/css/vendor-media-form.css') }}?v={{ @filemtime(public_path('asset/css/vendor-media-form.css')) }}">
@endpush
@push('vendor-scripts')
    <script src="{{ asset('asset/js/vendor-media-form.js') }}?v={{ @filemtime(public_path('asset/js/vendor-media-form.js')) }}"></script>
@endpush

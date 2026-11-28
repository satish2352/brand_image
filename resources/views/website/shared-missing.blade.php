@extends('website.layout')

@section('title', 'Link not found')

@section('content')
    {{-- The person holding a dead link is a client, not a developer, so this is
         a plain page rather than an exception trace. --}}
    <div class="container-fluid about-banner-img g-0">
        <div class="row g-0">
            <div class="col-md-12 d-none d-md-block">
                <img src="{{ asset('assets/img/media_desk.png') }}" alt="Brand Adda" class="img-fluid w-100">
            </div>
            <div class="col-md-12 d-block d-md-none">
                <img src="{{ asset('assets/img/media_mobile.png') }}" alt="Brand Adda" class="img-fluid w-100">
            </div>
        </div>
    </div>

    <div class="container-fluid mt-5 mb-5">
        <div class="bi-media-empty">
            <i class="bi bi-link-45deg" aria-hidden="true"></i>
            <h4>This link is no longer available</h4>
            <p>
                The shortlist you are looking for has been removed, or the link was
                copied incompletely. Please ask our team to send it again.
            </p>
        </div>
    </div>
@endsection

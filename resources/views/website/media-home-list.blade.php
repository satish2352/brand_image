@foreach ($mediaList as $media)
@php
$isBillboard = (int) $media->category_id === 1;
$isBooked = (int) ($media->is_booked ?? 0);
$width = (float) ($media->width ?? 0);
$height = (float) ($media->height ?? 0);
$sqft = $width * $height;
@endphp
<?php

// dd($mediaList);
// die();
?>
{{-- <div class="col-lg-4 col-md-6 mb-5"> --}}
<div class="col-12 col-md-6 mb-4">
    <div class="single-latest-news">
        <div class="latest-news-bg"
            style="background-image:url('{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}')" class="card-img-fit">
            @if ($isBillboard)
            @if ($isBooked === 1)
            <span class="media-badge booked">Booked</span>
            @else
            <span class="media-badge available">Available</span>
            @endif
            @endif
        </div>
        <div class="news-text-box">
            <h3 style="font-size: 21px;">
                <a href="{{ route('website.media-details', base64_encode($media->id)) }}">
                    {{ $media->area_name ?? $media->category_name }} {{ $media->facing }}
                </a>
            </h3>
            {{-- <p class="blog-meta">
                    <strong>Media : </strong>
                    {{ $media->category_name }}
            </p> --}}
            {{-- <p class="blog-meta">
                    <i class="fas fa-map-marker-alt"></i>
                    {{ $media->common_area_name }}, {{ $media->city_name }}
            </p> --}}
            <div class="col-6 mb-2 d-flex">
                <strong>Size : </strong>
                {{ number_format($media->width, 2) }} x {{ number_format($media->height, 2) }} ft

            </div>
            <div class="col-6 mb-2 d-flex">
                <strong>Area : </strong>
                {{ number_format($sqft, 2) }} SQFT
            </div>
            <div class="media-price">
                ₹ {{ number_format($media->price, 2) }}
            </div>
            <div class="media-map mt-4 d-flex align-items-center justify-content-between gap-3">

                {{-- View on Map --}}
                <a href="https://www.google.com/maps?q={{ $media->latitude }},{{ $media->longitude }}"
                    target="_blank" class="text-muted d-inline-flex align-items-center gap-1">
                    <img src="{{ asset('assets/img/105.png') }}" width="30">
                    <span>View on Map</span>
                </a>
                @if (!empty($media->video_link))
                <a href="{{ $media->video_link }}" target="_blank"
                    class="text-muted d-inline-flex align-items-center gap-1">
                    <img src="{{ asset('assets/img/360view.png') }}" width="30">
                    <span>360° View</span>
                </a>
                @endif

            </div>
            @php
            $isBillboard = $media->category_id == 1;
            @endphp
            <div class="card-actions">
                @if ($isBillboard)
                @if ($isBooked === 0)
                <a href="{{ route('website.media-details', base64_encode($media->id)) }}#media-details"
                    class="card-btn read">
                    Read More →
                </a>
                @auth('website')
                <a href="{{ route('cart.add', base64_encode($media->id)) }}" class="btn card-btn cart">
                    Add to Cart
                </a>
                @else
                <button class="btn card-btn cart" data-bs-toggle="modal" data-bs-target="#authModal">
                    Add to Cart
                </button>
                @endauth
                @else
                <a href="{{ route('website.media-details', base64_encode($media->id)) }}"
                    class="card-btn read">
                    Read More →
                </a>
                @endif
                @else
                <a href="{{ route('contact.create', ['media' => base64_encode($media->id)]) }}#contact-form"
                    class="card-btn contact">
                    Contact Us
                </a>

                <a href="{{ route('website.media-details', base64_encode($media->id)) }}"
                    class="card-btn read">
                    Read More →
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ==================================
<div class="col-lg-4 col-md-6 mb-5">
    <div class="single-latest-news">
        <div class="latest-news-bg"
             style="background-image:url('{{ asset('assets/img/static1.jpg') }}')">
<span class="media-badge available">Available</span>
</div>

<div class="news-text-box">
    <h3>Static Hoarding 1</h3>

    <p class="blog-meta">
        <i class="fas fa-map-marker-alt"></i>
        Shivaji Nagar, Pune
    </p>

    <div class="media-map mt-4 d-flex align-items-center gap-3">
        <a href="https://www.google.com/maps/search/?api=1&query=Pune+Bus+Stand"
            target="_blank"
            class="text-muted d-inline-flex align-items-center gap-1">
            <img src="{{ asset('assets/img/105.png') }}" width="30">
            <span>View on Map</span>
        </a>

        <a href="https://view.sivaraa360studio.com/tours/2EZX1xSI6"
            target="_blank"
            class="text-muted d-inline-flex align-items-center gap-1">
            🔁 <span>360° View</span>
        </a>
    </div>


    <div class="card-actions">
        <a href="{{ route('website.media-details', base64_encode($media->id)) }}"
            class="card-btn read">
            Read More →
        </a>

        <a href="{{ route('cart.add', base64_encode($media->id)) }}"
            class="btn card-btn cart">
            Add to Cart
        </a>
    </div>
</div>
</div>
</div>
<div class="col-lg-4 col-md-6 mb-5">
    <div class="single-latest-news">
        <div class="latest-news-bg"
            style="background-image:url('{{ asset('assets/img/static2.jpg') }}')">
            <span class="media-badge available">Available</span>
        </div>

        <div class="news-text-box">
            <h3>Static Hoarding 2</h3>

            <p class="blog-meta">
                <i class="fas fa-map-marker-alt"></i>
                MG Road, Nashik
            </p>

            <div class="media-map mt-4 d-flex align-items-center gap-3">
                <a href="https://www.google.com/maps/search/?api=1&query=CBS+Bus+Stand+Nashik"
                    target="_blank"
                    class="text-muted d-inline-flex align-items-center gap-1">
                    <img src="{{ asset('assets/img/map.png') }}" width="30">
                    <span>View on Map</span>
                </a>
            </div>


            <div class="card-actions">
                <a href="{{ route('website.media-details', base64_encode($media->id)) }}"
                    class="card-btn read">
                    Read More →
                </a>

                <a href="{{ route('cart.add', base64_encode($media->id)) }}"
                    class="btn card-btn cart">
                    Add to Cart
                </a>
            </div>
        </div>
    </div>
</div>
================================================= --}}

@endforeach
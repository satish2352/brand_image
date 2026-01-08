

@foreach($mediaList as $media)
@php
    $isBillboard = ((int) $media->category_id === 1);
    $isBooked = (int) ($media->is_booked ?? 0);
@endphp
<div class="col-lg-4 col-md-6 mb-5">
    <div class="single-latest-news">

        {{-- <div class="latest-news-bg"
             style="background-image:url('{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}')">
        </div> --}}
        <div class="latest-news-bg"
            {{-- style="background-image:url('{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}')">

            @if($isBooked === 1)
                <span class="media-badge booked">Booked</span>
            @else
                <span class="media-badge available">Available</span>
            @endif --}}
            style="background-image:url('{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}')">

            @if($isBillboard)
                @if($isBooked === 1)
                    <span class="media-badge booked">Booked</span>
                @else
                    <span class="media-badge available">Available</span>
                @endif
            @endif

        </div>
        <div class="news-text-box">

            <h3>
                <a href="{{ route('website.media-details', base64_encode($media->id)) }}">
                    {{ $media->media_title ?? $media->category_name }}
                </a>
            </h3>

            <p class="blog-meta">
                <i class="fas fa-map-marker-alt"></i>
                {{ $media->area_name }}, {{ $media->city_name }}
            </p>

            <div class="media-price">
                ₹ {{ number_format($media->price, 2) }}
            </div>

            {{-- href="https://www.google.com/maps/search/?api=1&query={{ urlencode($media->area_name . ', ' . $media->city_name) }}" --}}
            <div class="media-map mt-4">
                {{-- <a href="https://www.google.com/maps" --}}
                <a href="https://www.google.com/maps?q={{ $media->latitude }},{{ $media->longitude }}"
                target="_blank"
                class="text-muted d-inline-flex align-items-center gap-1">
                    <img src="{{ asset('assets/img/map.png') }}" width="30">
                    <span>View on Map</span>
                </a>
            </div>

          @php
    $isBillboard = ($media->category_id == 1); // Hoardings / Billboards
@endphp

<div class="card-actions">

   
    {{-- @if($isBillboard)

       
        <a href="{{ route('website.media-details', base64_encode($media->id)) }}"
           class="card-btn read">
            Read More →
        </a>

        
        @if(!$media->is_booked)
            @auth('website')
                <a href="{{ route('cart.add', base64_encode($media->id)) }}"
                   class="card-btn cart">
                    Add to Cart
                </a>
            @else
                <button class="card-btn cart"
                        data-bs-toggle="modal"
                        data-bs-target="#authModal">
                    Add to Cart
                </button>
            @endauth
        @endif

   
    @else
        <a href="{{ route('contact.create') }}"
           class="card-btn contact w-100 text-center">
            Contact Us
        </a>
    @endif --}}


     @if($isBillboard)

        @if($isBooked === 0)
            
               <a href="{{ route('website.media-details', base64_encode($media->id)) }}"
           class="card-btn read">
            Read More →
        </a>
@auth('website')
                <a href="{{ route('cart.add', base64_encode($media->id)) }}"
                   class="btn card-btn cart">
                    Add to Cart
                </a>
            @else
                <button class="btn card-btn cart"
                        data-bs-toggle="modal"
                        data-bs-target="#authModal">
                    Add to Cart
                </button>
            @endauth
        @else
           <a href="{{ route('website.media-details', base64_encode($media->id)) }}"
           class="card-btn read">
            Read More →
        </a>


            {{-- <span class="badge bg-danger">Booked</span> --}}
        @endif

    @else
    <a href="{{ route('contact.create', ['media' => base64_encode($media->id)]) }}"
   class="card-btn contact">
    Contact Us
</a>

        {{-- <a href="{{ route('contact.create') }}"
           class="card-btn contact">
            Contact Us
        </a> --}}
    @endif
</div>
        </div>
    </div>
</div>

@endforeach
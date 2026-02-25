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
<style>
.open-panorama{
    cursor: pointer;
    position: relative;
    z-index: 9999;
    pointer-events: auto;
}
#media-container{
    padding: 10px;
}
.card-shadow{
       box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);

    transition: all 0.3s ease;
   margin-bottom: 22px;
   margin-left: 10px;
}
.latest-news-bg{
    width:100%;
    height:220px;      /* control image height */
    position:relative;
    overflow:hidden;
    border-radius:8px;
}

.media-img{
    width:100%;
    height:100%;

    object-fit:cover;   /* ⭐ fills area perfectly */
    object-position:center;
}
.single-latest-news{
    background:#fff;
    border-radius:10px;
    box-shadow:0 4px 12px rgba(0,0,0,0.12);
    padding:15px;
    transition:0.3s;
}

.single-latest-news:hover{
    transform:translateY(-3px);
    box-shadow:0 8px 20px rgba(0,0,0,0.18);
}
.media-title a{
    font-size:21px;
    font-weight:600;
    color:#f28123;   /* ⭐ ORANGE */
    text-decoration:none;
}

.media-title a:hover{
    color:#f28123;
}
.card-btn.cart{
    background:#f28123 !important;
    border:none !important;
    color:#fff !important;
    border-radius:30px;
    padding:8px 18px;
    font-weight:600;
    transition:0.3s;
}

.card-btn.cart:hover{
    background:#f28123 !important;
    transform:scale(1.05);
}
.card-actions{
        padding-top: 22px;
}

.media-title a {
    display: -webkit-box;
    -webkit-line-clamp: 1;   /* number of lines */
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
{{-- <div class="col-lg-4 col-md-6 mb-5"> --}}
{{-- <div class="col-12 col-md-12 mb-4"> --}}
    {{-- <div class="single-latest-news"> --}}
          <div class="row card-shadow px-0">
        <div class="col-lg-5 col-md-5 col-sm-5 px-0">
        {{-- <div class="latest-news-bg"
            style="background-image:url('{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}')" class="card-img-fit">
            @if ($isBillboard)
            @if ($isBooked === 1)
            <span class="media-badge booked">Booked</span>
            @else
            <span class="media-badge available">Available</span>
            @endif
            @endif
        </div> --}}
        <div class="latest-news-bg">
    <img src="{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}"
         class="media-img">

    @if ($isBillboard)
        @if ($isBooked === 1)
            <span class="media-badge booked">Booked</span>
        @else
            <span class="media-badge available">Available</span>
        @endif
    @endif
</div>
        </div>
        <div class="col-lg-7 col-md-7 col-sm-7">
        <div class="news-text-box">
            <h3 class="media-title">
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
            <div class="col-12 mb-2 d-flex">
                <strong>Size : </strong>
                {{ number_format($media->width, 2) }} x {{ number_format($media->height, 2) }} ft
 &nbsp;&nbsp;&nbsp;<strong>Area : </strong>
                {{ number_format($sqft, 2) }} SQFT
            </div>
            {{-- <div class="col-6 mb-2 d-flex">
               
            </div> --}}
            <div class="media-price">
                ₹ {{ number_format($media->price, 2) }}
            </div>
            <div class="media-map my-2 d-flex align-items-center justify-content-between gap-3">

                {{-- View on Map --}}
                <a href="https://www.google.com/maps?q={{ $media->latitude }},{{ $media->longitude }}"
                    target="_blank" class="text-muted d-inline-flex align-items-center gap-1">
                    <img src="{{ asset('assets/img/105.png') }}" width="30">
                    <span>View on Map</span>
                </a>
                {{-- @if (!empty($media->video_link))
                <a href="{{ $media->video_link }}" target="_blank"
                    class="text-muted d-inline-flex align-items-center gap-1">
                    <img src="{{ asset('assets/img/360view.png') }}" width="30">
                    <span>360° View</span>
                </a>
                @endif --}}

<?php
// dd($media);
// die();
?>
                {{-- @if(!empty($media->panorama_image))

<a href="#"
   class="text-muted d-inline-flex align-items-center gap-1 open-panorama"
   data-image="{{ config('fileConstants.IMAGE_VIEW').$media->panorama_image }}">
    <img src="{{ asset('assets/img/360view.png') }}" width="30">
    <span>360° View</span>
</a>

@endif --}}


@if(!empty($media->panorama_image))

<a href="{{ url('./panorama.html?img=' . urlencode(config('fileConstants.IMAGE_VIEW').$media->panorama_image)) }}"
   target="_blank"
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
    {{-- </div> --}}
{{-- </div> --}}

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
<!-- Panorama Modal -->
<div class="modal fade" id="panoramaModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">360° View</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-0">
        <div id="panoramaContainer"
             style="width:100%; height:500px;">
        </div>
      </div>

    </div>
  </div>
</div>
<script>
let viewer = null;
const panoramaModal = document.getElementById('panoramaModal');

document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll('.open-panorama').forEach(function(btn){

        btn.addEventListener('click', function(e){

            e.preventDefault();

            let image = this.dataset.image;

            let modal = new bootstrap.Modal(panoramaModal);
            modal.show();

            setTimeout(() => {

                if(viewer){
                    viewer.destroy();
                }

                viewer = pannellum.viewer('panoramaContainer', {
                    type: "equirectangular",
                    panorama: image,
                    autoLoad: true,

                    // ⭐ MOTION ANIMATION
                    autoRotate: 3,
                    autoRotateInactivityDelay: 2000,
                    pitch: -5,
                    hfov: 110,
                    showControls: true
                });

            }, 400);

        });

    });

});

/* destroy viewer when modal closes */
panoramaModal.addEventListener('hidden.bs.modal', function () {
    if(viewer){
        viewer.destroy();
        viewer = null;
    }
});
</script>
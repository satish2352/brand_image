@foreach($mediaList as $media)
@php $isBooked = (int) ($media->is_booked ?? 0); @endphp

<div class="col-xl-3 col-lg-4 col-md-6 mb-4">
    <div class="card h-100 shadow-sm">

        <img src="{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}"
             class="card-img-top"
             style="height:180px; object-fit:cover;">

        <div class="card-body">
            <h6 class="fw-bold">{{ $media->media_title }}</h6>

            <p class="text-muted small">
                <i class="fas fa-map-marker-alt"></i>
                {{ $media->area_name }}, {{ $media->city_name }}
            </p>

            <p class="fw-bold">₹ {{ number_format($media->price, 2) }}</p>

              @if($media->from_date && $media->to_date)
                <h6 class="fw-bold text-danger">
                    {{ \Carbon\Carbon::parse($media->from_date)->format('d M Y') }}
                    -
                    {{ \Carbon\Carbon::parse($media->to_date)->format('d M Y') }}
                </h6>
            @else
                <h6 class="fw-bold text-primary">Available</h6>
            @endif


            <div class="d-flex justify-content-between">
               
                   {{-- ==================== --}}

@php
    $isBillboard = ($media->category_id == 1); // Hoardings / Billboards
@endphp
 @if($isBillboard)

        @if($isBooked === 0)
            
                <a href="{{ route('admin-booking.admin-media-details', base64_encode($media->id)) }}"
                   class="btn btn-sm btn-outline-primary">View</a>


        @else
             <a href="{{ route('admin-booking.admin-media-details', base64_encode($media->id)) }}"
                   class="btn btn-sm btn-outline-primary">View</a>



            <span class="badge bg-danger text-white px-2 py-2 rounded">Booked</span>
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
                   {{-- =========================== --}}

                {{-- @if(!$isBooked)
                    <button class="btn btn-sm btn-success">Book</button>
                @else
                    <span class="text-danger small fw-bold">Booked</span>
                @endif --}}
            </div>
        </div>

    </div>
</div>
@endforeach

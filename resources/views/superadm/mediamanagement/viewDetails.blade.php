@extends('superadm.layout.master')

@php
    use Illuminate\Support\Str;

    // Create slug safely from category_name
    $categorySlug = Str::slug($media->category_name ?? '');
@endphp

@section('content')

    <style>

    .info-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        margin-bottom: 24px;
        overflow: hidden;
    }
    .info-card-header {
        background: #00929c;
        color: #fff;
        padding: 12px 20px;
        font-weight: 600;
        font-size: 16px;
    }
    .info-card-body {
        padding: 20px;
    }
    .info-row {
        display: flex;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }
    .info-col {
        width: 50%;
        padding: 6px 0;
    }
    .info-label {
        font-size: 13px;
        color: #777;
    }
    .info-value {
        font-size: 15px;
        font-weight: 500;
        color: #222;
    }
    @media(max-width:768px){
        .info-col { width:100%; }
    }
    .image-card img {
        height: 150px;
        object-fit: cover;
        border-radius: 10px;
        transition: 0.3s;
    }
    .image-card img:hover {
        transform: scale(1.05);
    }

        #billboardsBasic,
        #billboardsId,
        #mallMedia,
        #airportBranding,
        #transmitMedia,
        #officeBranding,
        #wallWrap {
            display: none;
        }
    </style>

<input type="hidden" id="category_slug" value="{{ $categorySlug }}">

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-lg-12 col-md-12 mx-auto">

            <a href="{{ route('media.list') }}" class="btn btn-outline-secondary mb-4">
                ← Back to Media Management List
            </a>

            {{-- ================= BASIC DETAILS ================= --}}
            <div class="info-card">
                <div class="info-card-header">Basic Details</div>
                <div class="info-card-body">

                    <div class="info-row">
                        <div class="info-col">
                            <div class="info-label">Category</div>
                            <div class="info-value">{{ $media->category_name ?? '-' }}</div>
                        </div>
                        <div class="info-col">
                            <div class="info-label">Vendor Name</div>
                            <div class="info-value">{{ $media->vendor_name ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-col">
                            <div class="info-label">Price</div>
                            <div class="info-value">
                                {{ $media->price ? '₹ '.number_format($media->price,2) : '-' }}
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ================= MEDIA DETAILS (BILLBOARD BASIC) ================= --}}
            <div class="info-card" id="billboardsBasic">
                <div class="info-card-header">Media Details</div>
                <div class="info-card-body">

                    <div class="info-row">
                        <div class="info-col">
                            <div class="info-label">Media Code</div>
                            <div class="info-value">{{ $media->media_code ?? '-' }}</div>
                        </div>
                        <div class="info-col">
                            <div class="info-label">Media Title</div>
                            <div class="info-value">{{ $media->media_title ?? '-' }}</div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ================= LOCATION DETAILS ================= --}}
            <div class="info-card">
                <div class="info-card-header">Location Details</div>
                <div class="info-card-body">

                    <div class="info-row">
                        <div class="info-col">
                            <div class="info-label">State</div>
                            <div class="info-value">{{ $media->state_name ?? '-' }}</div>
                        </div>
                        <div class="info-col">
                            <div class="info-label">District</div>
                            <div class="info-value">{{ $media->district_name ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-col">
                            <div class="info-label">City</div>
                            <div class="info-value">{{ $media->city_name ?? '-' }}</div>
                        </div>
                        <div class="info-col">
                            <div class="info-label">Area</div>
                            <div class="info-value">{{ $media->area_name ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-col">
                            <div class="info-label">Latitude</div>
                            <div class="info-value">{{ $media->latitude ?? '-' }}</div>
                        </div>
                        <div class="info-col">
                            <div class="info-label">Longitude</div>
                            <div class="info-value">{{ $media->longitude ?? '-' }}</div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ================= DIMENSIONS ================= --}}
            <div class="info-card">
                <div class="info-card-header">Dimensions</div>
                <div class="info-card-body">

                    <div class="info-row">
                        <div class="info-col">
                            <div class="info-label">Width (ft)</div>
                            <div class="info-value">{{ $media->width ?? '-' }}</div>
                        </div>
                        <div class="info-col">
                            <div class="info-label">Height (ft)</div>
                            <div class="info-value">{{ $media->height ?? '-' }}</div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ================= BILLBOARD DETAILS ================= --}}
            <div class="info-card" id="billboardsId">
                <div class="info-card-header">Billboard Details</div>
                <div class="info-card-body">

                    <div class="info-row">
                        <div class="info-col">
                            <div class="info-label">Facing</div>
                            <div class="info-value">{{ $media->facing ?? '-' }}</div>
                        </div>
                        <div class="info-col">
                            <div class="info-label">Illumination</div>
                            <div class="info-value">{{ $media->illumination_name ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-col">
                            <div class="info-label">Area Type</div>
                            <div class="info-value">{{ ucfirst($media->area_type ?? '-') }}</div>
                        </div>
                        <div class="info-col">
                            <div class="info-label">Address</div>
                            <div class="info-value">{{ $media->address ?? '-' }}</div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ================= IMAGES ================= --}}
            <div class="info-card">
                <div class="info-card-header">Images</div>
                <div class="info-card-body">
                    <div class="row">
                        @forelse($media->images as $img)
                            <div class="col-md-3 col-sm-6 mb-3 image-card">
                                <img src="{{ config('fileConstants.IMAGE_VIEW') . $img['image'] }}"
                                     class="img-fluid">
                            </div>
                        @empty
                            <p class="text-muted">No images available</p>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection


@section('scripts')
    <script>
        $(document).ready(function() {

            const category = $('#category_slug').val();

            function hideAll() {
                $('#billboardsBasic, #billboardsId, #mallMedia, #airportBranding, #transmitMedia, #officeBranding, #wallWrap')
                    .hide();
            }

            hideAll();

            // OTHER → only common fields
            if (!category || category === 'other') {
                return;
            }

            if (category.includes('hoardings')) {
                $('#billboardsBasic').show();
                $('#billboardsId').show();
            }
            if (category.includes('mall')) $('#mallMedia').show();
            if (category.includes('airport')) $('#airportBranding').show();
            if (category.includes('transmit')) $('#transmitMedia').show();
            if (category.includes('office')) $('#officeBranding').show();
            if (category.includes('wall')) $('#wallWrap').show();
        });
    </script>
@endsection

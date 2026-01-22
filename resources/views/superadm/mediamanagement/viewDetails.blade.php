@extends('superadm.layout.master')

@php
    use Illuminate\Support\Str;

    // Create slug safely from category_name
    $categorySlug = Str::slug($media->category_name ?? '');
@endphp

@section('styles')
    <style>
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
@endsection

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">

            <h4 class="mb-4">Media Details</h4>

            {{-- Hidden category slug --}}
            <input type="hidden" id="category_slug" value="{{ $categorySlug }}">

            {{-- ================= COMMON BASIC DETAILS (FOR ALL) ================= --}}
            <h6>Basic Details</h6>
            <table class="table table-bordered">
                <tr>
                    <th>Category</th>
                    <td>{{ $media->category_name ?? '-' }}</td>

                    <th>Vendor Name</th>
                    <td>{{ $media->vendor_name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Price</th>
                    <td>₹ {{ $media->price ? number_format($media->price, 2) : '-' }}</td>
                    <th></th>
                    <td></td>
                </tr>
            </table>

            {{-- ================= BILLBOARD BASIC ================= --}}
            <table class="table table-bordered" id="billboardsBasic">
                <tr>
                    <th>Media Code</th>
                    <td>{{ $media->media_code ?? '-' }}</td>

                    <th>Media Title</th>
                    <td>{{ $media->media_title ?? '-' }}</td>
                </tr>
            </table>

            {{-- ================= LOCATION (COMMON) ================= --}}
            <h6 class="mt-4">Location Details</h6>
            <table class="table table-bordered">
                <tr>
                    <th>State</th>
                    <td>{{ $media->state_name ?? '-' }}</td>

                    <th>District</th>
                    <td>{{ $media->district_name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>City</th>
                    <td>{{ $media->city_name ?? '-' }}</td>

                    <th>Area</th>
                    <td>{{ $media->area_name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Latitude</th>
                    <td>{{ $media->latitude ?? '-' }}</td>

                    <th>Longitude</th>
                    <td>{{ $media->longitude ?? '-' }}</td>
                </tr>
            </table>

            {{-- ================= DIMENSIONS (COMMON) ================= --}}
            <h6 class="mt-4">Dimensions</h6>
            <table class="table table-bordered">
                <tr>
                    <th>Width (ft)</th>
                    <td>{{ $media->width ?? '-' }}</td>

                    <th>Height (ft)</th>
                    <td>{{ $media->height ?? '-' }}</td>
                </tr>
            </table>

            {{-- ================= HOARDINGS ================= --}}
            <div id="billboardsId">
                <h6 class="mt-4">Billboard Details</h6>
                <table class="table table-bordered">
                    <tr>
                        <th>Facing</th>
                        <td>{{ $media->facing ?? '-' }}</td>

                        <th>Illumination</th>
                        <td>{{ $media->illumination_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Area Type</th>
                        <td>{{ ucfirst($media->area_type ?? '-') }}</td>

                        <th>Address</th>
                        <td>{{ $media->address ?? '-' }}</td>
                    </tr>
                </table>
            </div>

            {{-- ================= MALL MEDIA ================= --}}
            <div id="mallMedia">
                <h6 class="mt-4">Mall Media</h6>
                <table class="table table-bordered">
                    <tr>
                        <th>Mall Name</th>
                        <td>{{ $media->mall_name ?? '-' }}</td>

                        <th>Media Format</th>
                        <td>{{ $media->media_format ?? '-' }}</td>
                    </tr>
                </table>
            </div>

            {{-- ================= AIRPORT ================= --}}
            <div id="airportBranding">
                <h6 class="mt-4">Airport Branding</h6>
                <table class="table table-bordered">
                    <tr>
                        <th>Airport Name</th>
                        <td>{{ $media->airport_name ?? '-' }}</td>

                        <th>Zone</th>
                        <td>{{ $media->zone_type ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Media Type</th>
                        <td colspan="3">{{ $media->media_type ?? '-' }}</td>
                    </tr>
                </table>
            </div>

            {{-- ================= TRANSIT ================= --}}
            <div id="transmitMedia">
                <h6 class="mt-4">Transit Media</h6>
                <table class="table table-bordered">
                    <tr>
                        <th>Transit Type</th>
                        <td>{{ $media->transit_type ?? '-' }}</td>

                        <th>Vehicle Count</th>
                        <td>{{ $media->vehicle_count ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Branding Type</th>
                        <td colspan="3">{{ $media->branding_type ?? '-' }}</td>
                    </tr>
                </table>
            </div>

            {{-- ================= OFFICE ================= --}}
            <div id="officeBranding">
                <h6 class="mt-4">Office Branding</h6>
                <table class="table table-bordered">
                    <tr>
                        <th>Building Name</th>
                        <td>{{ $media->building_name ?? '-' }}</td>

                        <th>Branding Type</th>
                        <td>{{ $media->wall_length ?? '-' }}</td>
                    </tr>
                </table>
            </div>

            {{-- ================= WALL WRAP ================= --}}
            <div id="wallWrap">
                <h6 class="mt-4">Wall Wrap</h6>
                <table class="table table-bordered">
                    <tr>
                        <th>Area (sq.ft)</th>
                        <td>{{ $media->area_auto ?? '-' }}</td>
                    </tr>
                </table>
            </div>

            {{-- ================= IMAGES ================= --}}
            <h6 class="mt-4">Images</h6>
            <div class="row">
                @forelse($media->images as $img)
                    <div class="col-md-2 mb-3">
                        <img src="{{ config('fileConstants.IMAGE_VIEW') . $img['image'] }}" class="img-fluid rounded"
                            style="height:150px; object-fit:cover;">
                    </div>
                @empty
                    <p class="text-muted ms-2">No images available.</p>
                @endforelse
            </div>

            <a href="{{ route('media.list') }}" class="btn btn-secondary mt-3">Back</a>

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

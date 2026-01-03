@extends('superadm.layout.master')

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

        {{-- CATEGORY SLUG --}}
        <input type="hidden" id="category_id" value="{{ $media->category_id }}">

        {{-- ================= COMMON BASIC DETAILS ================= --}}
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
                <td>₹ {{ $media->price ? number_format($media->price,2) : '-' }}</td>

                <th>Minimum Booking Days</th>
                {{-- <td>{{ $media->minimum_booking_days ?? '-' }}</td> --}}
            </tr>
        </table>

        {{-- ================= BILLBOARD BASIC (MEDIA CODE / TITLE) ================= --}}
        <table class="table table-bordered" id="billboardsBasic">
            <tr>
                <th>Media Code</th>
                <td>{{ $media->media_code ?? '-' }}</td>

                <th>Media Title</th>
                <td>{{ $media->media_title ?? '-' }}</td>
            </tr>
        </table>

        {{-- ================= LOCATION DETAILS ================= --}}
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

        {{-- ================= DIMENSIONS ================= --}}
        <h6 class="mt-4">Dimensions</h6>
        <table class="table table-bordered">
            <tr>
                <th>Width (ft)</th>
                <td>{{ $media->width ?? '-' }}</td>

                <th>Height (ft)</th>
                <td>{{ $media->height ?? '-' }}</td>
            </tr>
        </table>

        {{-- ================= BILLBOARD DETAILS ================= --}}
        <div id="billboardsId">
            <h6 class="mt-4">Billboard Details</h6>
            <table class="table table-bordered">
                <tr>
                    <th>Facing</th>
                    <td>{{ $media->facing_name ?? '-' }}</td>

                    <th>Illumination</th>
                    <td>{{ $media->illumination_name ?? '-' }}</td>
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

        {{-- ================= AIRPORT BRANDING ================= --}}
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

        {{-- ================= TRANSIT MEDIA ================= --}}
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

        {{-- ================= OFFICE BRANDING ================= --}}
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
                <div class="col-md-3 mb-3">
                    <img src="{{ config('fileConstants.IMAGE_VIEW') . $img['image'] }}"
                         class="img-fluid rounded"
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
$(document).ready(function () {

    const category = $('#category_id').val();

    function hideAll() {
        $('#billboardsBasic').hide();
        $('#billboardsId').hide();
        $('#mallMedia').hide();
        $('#airportBranding').hide();
        $('#transmitMedia').hide();
        $('#officeBranding').hide();
        $('#wallWrap').hide();
    }

    hideAll();

    if (category === 'hoardings') {
        $('#billboardsBasic').show();
        $('#billboardsId').show();
        return;
    }

    if (category === 'digital-wall') {
        return; // ONLY COMMON DETAILS
    }

    if (category === 'mall-media') {
      
        $('#mallMedia').show();
        return;
    }

    if (category === 'airport-branding') {
       
        $('#airportBranding').show();
        return;
    }

    if (category === 'transmit-media') {
        $('#transmitMedia').show();
        return;
    }

    if (category === 'office-branding') {
        $('#officeBranding').show();
        return;
    }

    if (category === 'wall-wrap') {
        $('#wallWrap').show();
        return;
    }

});
</script>
@endsection

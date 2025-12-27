@extends('superadm.layout.master')

@section('styles')
<style>
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
        <h4 class="mb-4">Edit Media</h4>

        <form method="POST"
              action="{{ route('media.update', $encodedId) }}"
              enctype="multipart/form-data">
            @csrf
<input type="hidden"
       id="category_slug"
       value="{{ $categories->where('id', $media->category_id)->first()->slug }}">

            {{-- ================= HIDDEN LOCATION FIELDS ================= --}}
            <input type="hidden" name="state_id" value="{{ $media->state_id }}">
            <input type="hidden" name="district_id" value="{{ $media->district_id }}">
            <input type="hidden" name="city_id" value="{{ $media->city_id }}">

            {{-- category disabled → keep value --}}
            <input type="hidden" name="category_id" value="{{ $media->category_id }}">

            <div class="row">
                {{-- AREA --}}
                <div class="col-md-6 mb-3">
                    <label>Area <span class="text-danger">*</span></label>
                    <select name="area_id"
                            class="form-control @error('area_id') is-invalid @enderror">
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}"
                                {{ old('area_id', $media->area_id) == $area->id ? 'selected' : '' }}>
                                {{ $area->common_stdiciar_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('area_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- CATEGORY --}}
                <div class="col-md-6 mb-3">
                    <label>Category</label>
                    <select class="form-control" disabled>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}"
                                data-category="{{ $cat->slug }}"
                                {{ $media->category_id == $cat->id ? 'selected' : '' }}>
                                {{ $cat->category_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- ================= BILLBOARD ================= --}}
            <div class="row" id="billboardsId">
                <div class="col-md-4 mb-3">
                    <label>Media Code *</label>
                    <input type="text" name="media_code"
                        value="{{ old('media_code', $media->media_code) }}"
                        class="form-control @error('media_code') is-invalid @enderror">
                </div>

                <div class="col-md-4 mb-3">
                    <label>Media Title *</label>
                    <input type="text" name="media_title"
                        value="{{ old('media_title', $media->media_title) }}"
                        class="form-control @error('media_title') is-invalid @enderror">
                </div>

                <div class="col-md-4 mb-3">
                    <label>Facing *</label>
                    <select name="facing_id"
                        class="form-control @error('facing_id') is-invalid @enderror">
                        @foreach($facings as $face)
                            <option value="{{ $face->id }}"
                                {{ old('facing_id', $media->facing_id) == $face->id ? 'selected' : '' }}>
                                {{ $face->facing_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label>Illumination *</label>
                    <select name="illumination_id"
                        class="form-control @error('illumination_id') is-invalid @enderror">
                        @foreach($illuminations as $ill)
                            <option value="{{ $ill->id }}"
                                {{ old('illumination_id', $media->illumination_id) == $ill->id ? 'selected' : '' }}>
                                {{ $ill->illumination_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label>Min Booking Days *</label>
                    <input type="number" name="minimum_booking_days"
                        value="{{ old('minimum_booking_days', $media->minimum_booking_days) }}"
                        class="form-control">
                </div>

                <div class="col-md-4 mb-3">
                    <label>Address *</label>
                    <textarea name="address"
                        class="form-control">{{ old('address', $media->address) }}</textarea>
                </div>
            </div>
            {{-- ================= MALL MEDIA ================= --}}
            <div class="row" id="mallMedia">
                <div class="col-md-6 mb-3">
                    <label>Mall Name <span class="text-danger">*</span></label>
                    <input type="text" name="mall_name"
                        value="{{ old('mall_name', $media->mall_name) }}"
                        class="form-control @error('mall_name') is-invalid @enderror">
                    @error('mall_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label>Media Format <span class="text-danger">*</span></label>
                    <select name="media_format"
                            class="form-control @error('media_format') is-invalid @enderror">
                        <option value="">Select Media Format</option>
                        @foreach(['Standee','Backlit Panel','LED','Banner'] as $format)
                            <option value="{{ $format }}"
                                {{ old('media_format', $media->media_format) == $format ? 'selected' : '' }}>
                                {{ $format }}
                            </option>
                        @endforeach
                    </select>
                    @error('media_format') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            {{-- ================= AIRPORT BRANDING ================= --}}
            <div class="row" id="airportBranding">
                <div class="col-md-4 mb-3">
                    <label>Airport Name *</label>
                    <input type="text" name="airport_name"
                        value="{{ old('airport_name', $media->airport_name) }}"
                        class="form-control">
                </div>

                <div class="col-md-4 mb-3">
                    <label>Zone *</label>
                    <select name="zone_type" class="form-control">
                        <option value="">Select Zone</option>
                        @foreach(['Arrival','Departure'] as $zone)
                            <option value="{{ $zone }}"
                                {{ old('zone_type', $media->zone_type) == $zone ? 'selected' : '' }}>
                                {{ $zone }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label>Media Type *</label>
                    <select name="media_type" class="form-control">
                        @foreach(['Backlit','LED','Standee'] as $type)
                            <option value="{{ $type }}"
                                {{ old('media_type', $media->media_type) == $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- ================= OFFICE BRANDING ================= --}}
            <div class="row" id="officeBranding">
                <div class="col-md-6 mb-3">
                    <label>Building Name <span class="text-danger">*</span></label>
                    <input type="text"
                        name="building_name"
                        value="{{ old('building_name', $media->building_name) }}"
                        class="form-control @error('building_name') is-invalid @enderror">
                    @error('building_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label>Branding Type <span class="text-danger">*</span></label>
                    <select name="wall_length"
                            class="form-control @error('wall_length') is-invalid @enderror">
                        <option value="">Select Branding Type</option>
                        <option value="Wall Wrap"
                            {{ old('wall_length', $media->wall_length) == 'Wall Wrap' ? 'selected' : '' }}>
                            Wall Wrap
                        </option>
                        <option value="Standee"
                            {{ old('wall_length', $media->wall_length) == 'Standee' ? 'selected' : '' }}>
                            Standee
                        </option>
                        <option value="LED"
                            {{ old('wall_length', $media->wall_length) == 'LED' ? 'selected' : '' }}>
                            LED
                        </option>
                    </select>
                    @error('wall_length')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            {{-- ================= TRANSIT MEDIA ================= --}}
            <div class="row" id="transmitMedia">
                <div class="col-md-4 mb-3">
                    <label>Transit Type <span class="text-danger">*</span></label>
                    <select name="transit_type" class="form-control">
                        @foreach(['Auto','Bus','Cab','Metro'] as $t)
                            <option value="{{ $t }}"
                                {{ old('transit_type', $media->transit_type) == $t ? 'selected' : '' }}>
                                {{ $t }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label>Branding Type <span class="text-danger">*</span></label>
                    <select name="branding_type" class="form-control">
                        @foreach(['Full Wrap','Partial Wrap','Back Panel'] as $b)
                            <option value="{{ $b }}"
                                {{ old('branding_type', $media->branding_type) == $b ? 'selected' : '' }}>
                                {{ $b }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label>Vehicle Count <span class="text-danger">*</span></label>
                    <input type="number" name="vehicle_count"
                        value="{{ old('vehicle_count', $media->vehicle_count) }}"
                        class="form-control">
                </div>
            </div>

            {{-- ================= COMMON ================= --}}
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label>Width <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" id="width"
                        name="width"
                        value="{{ old('width', $media->width) }}"
                        class="form-control">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Height <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" id="height"
                        name="height"
                        value="{{ old('height', $media->height) }}"
                        class="form-control">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Latitude <span class="text-danger">*</span></label>
                    <input type="text" name="latitude"
                        value="{{ old('latitude', $media->latitude) }}"
                        class="form-control">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Longitude <span class="text-danger">*</span></label>
                    <input type="text" name="longitude"
                        value="{{ old('longitude', $media->longitude) }}"
                        class="form-control">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Price <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="price"
                        value="{{ old('price', $media->price) }}"
                        class="form-control">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Vendor <span class="text-danger">*</span></label>
                    <input type="text" name="vendor_name"
                        value="{{ old('vendor_name', $media->vendor_name) }}"
                        class="form-control">
                </div>

                {{-- IMAGES --}}
                <div class="col-md-6 mb-4">
                    <label>Replace Images</label>
                    <input type="file" name="images[]" id="images"
                           multiple class="form-control">
                    {{-- <small class="text-muted">Uploading will replace old images</small> --}}
                    <div id="imagePreview" class="d-flex mt-2"></div>
                </div>
            </div>
            {{-- ================= WALL WRAP ================= --}}
            <div class="row" id="wallWrap">
                <div class="col-md-3 mb-3">
                    <label>Area (sq.ft) <span class="text-danger">*</span></label>
                    <input type="text" name="area_auto"
                        value="{{ old('area_auto', $media->area_auto) }}"
                        class="form-control" readonly>
                </div>
            </div>

            <div class="text-end">
                <a href="{{ route('media.list') }}" class="btn btn-secondary me-2">Cancel</a>
                <button class="btn btn-success">Update Media</button>
            </div>

        </form>
    </div>
</div>
@section('scripts')
<script>
$(document).ready(function () {

    function hideAll() {
        $('#billboardsId,#mallMedia,#airportBranding,#transmitMedia,#officeBranding,#wallWrap').hide();
    }

    hideAll();

    // ✅ get category safely
    let category = $('#category_slug').val();

    console.log('Edit category:', category); // DEBUG

    if (category === 'hoardings') {
        $('#billboardsId').show();
    }
    else if (category === 'mall-media') {
        $('#mallMedia').show();
    }
    else if (category === 'airport-branding') {
        $('#airportBranding').show();
    }
    else if (category === 'transmit-media') {
        $('#transmitMedia').show();
    }
    else if (category === 'office-branding') {
        $('#officeBranding').show();
    }
    else if (category === 'wall-wrap') {
        $('#wallWrap').show();
    }

    // Image preview
    $('#images').on('change', function () {
        $('#imagePreview').empty();
        [...this.files].forEach(file => {
            let reader = new FileReader();
            reader.onload = e =>
                $('#imagePreview').append(
                    `<img src="${e.target.result}" width="80" class="me-2 mb-2">`
                );
            reader.readAsDataURL(file);
        });
    });

});
</script>
@endsection


@endsection

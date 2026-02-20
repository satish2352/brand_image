@extends('superadm.layout.master')

@section('styles')
    <style>
        #billboardsId,
        #mallMedia,
        #airportBranding,
        #transmitMedia,
        #officeBranding,
        /* #wallWrap {
                                display: none;
                            } */
        #wallWrap,
        #radiusSection {
            display: none;
        }
    </style>
@endsection

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="mb-4">Edit Media</h4>

            <form method="POST" action="{{ route('media.update', $encodedId) }}" enctype="multipart/form-data">
                @csrf
                {{-- <input type="hidden"
                    id="category_slug"
                    value="{{ $categories->where('id', $media->category_id)->first()->slug }}"> --}}
                @php
                    $category = $categories->where('id', $media->category_id)->first();
                    $slug = \Illuminate\Support\Str::slug($category->slug ?? $category->category_name);
                @endphp

                <input type="hidden" id="category_slug" value="{{ $slug }}">

                {{-- ================= HIDDEN LOCATION FIELDS ================= --}}
                <input type="hidden" name="state_id" id="state_id" value="{{ $media->state_id }}">
                <input type="hidden" name="city_id" id="city_id" value="{{ $media->city_id }}">
                {{-- <input type="hidden" name="city_id" value="{{ $media->city_id }}"> --}}

                {{-- category disabled → keep value --}}
                <input type="hidden" name="category_id" value="{{ $media->category_id }}">

                <div class="row">

                    {{-- CATEGORY --}}
                    <div class="col-md-4 mb-3">
                        <label>Category</label>
                        <select class="form-control" disabled>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" data-category="{{ $cat->slug }}"
                                    {{ $media->category_id == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    {{-- AREA --}}
                    <div class="col-md-4 mb-3">
                        <label>Area <span class="text-danger">*</span></label>
                        {{-- <select name="area_id" class="form-control @error('area_id') is-invalid @enderror"> --}}

                            <select name="area_id" id="area_id"
    class="form-control @error('area_id') is-invalid @enderror">
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}"
                                    {{ old('area_id', $media->area_id) == $area->id ? 'selected' : '' }}>
                                    {{ $area->common_stdiciar_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('area_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Vendor <span class="text-danger">*</span></label>

                        {{-- <select name="vendor_id"
                        class="form-control @error('vendor_id') is-invalid @enderror">

                        <option value="">Select Vendor</option>

                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}"
                                {{ old('vendor_id', $media->vendor_id ?? '') == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->vendor_name }} - {{ $vendor->vendor_code }}
                            </option>
                        @endforeach

                    </select> --}}
                        <select name="vendor_id" id="vendor_id"
                            class="form-control @error('vendor_id') is-invalid @enderror">

                            <option value="">Select Vendor</option>

                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" data-vendor-code="{{ $vendor->vendor_code }}"
                                    {{ old('vendor_id', $media->vendor_id ?? '') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->vendor_name }} - {{ $vendor->vendor_code }}
                                </option>
                            @endforeach
                        </select>

                        @error('vendor_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- ================= BILLBOARD ================= --}}
                <div class="row" id="billboardsId">
                    {{-- <div class="col-md-4 mb-3">
                    <label>Media Code *</label>
                    <input type="text" name="media_code"
                        value="{{ old('media_code', $media->media_code) }}"
                        class="form-control @error('media_code') is-invalid @enderror">
                </div> --}}

                    <div class="col-md-4 mb-3">
                        <label>Media Code</label>

                        {{-- Display only --}}
                        <input type="text" id="media_code" class="form-control"
                            value="{{ old('media_code', $media->media_code) }}" disabled>

                        {{-- Actual value that will be submitted --}}
                        <input type="hidden" name="media_code" id="media_code_hidden"
                            value="{{ old('media_code', $media->media_code) }}">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Media Title <span class="text-danger">*</span></label>
                        <input type="text" name="media_title" value="{{ old('media_title', $media->media_title) }}"
                            class="form-control @error('media_title') is-invalid @enderror">
                    </div>

                    {{-- <div class="col-md-4 mb-3">
                    <label>Facing *</label>
                    <select name="facing_id"
                        class="form-control @error('facing_id') is-invalid @enderror">
                        @foreach ($facings as $face)
                            <option value="{{ $face->id }}"
                                {{ old('facing_id', $media->facing_id) == $face->id ? 'selected' : '' }}>
                                {{ $face->facing_name }}
                            </option>
                        @endforeach
                    </select>
                </div> --}}

                    <div class="col-md-4 mb-3">
                        <label>Facing <span class="text-danger">*</span></label>
                        <input type="text" name="facing" class="form-control @error('facing') is-invalid @enderror"
                            value="{{ old('facing', $media->facing) }}" placeholder="Enter facing">

                        @error('facing')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Area Type <span class="text-danger">*</span></label>
                        <select name="area_type" class="form-control @error('area_type') is-invalid @enderror">
                            <option value="">Select Area Type</option>
                            <option value="rural" {{ old('area_type', $media->area_type) == 'rural' ? 'selected' : '' }}>
                                Rural
                            </option>

                            <option value="urban" {{ old('area_type', $media->area_type) == 'urban' ? 'selected' : '' }}>
                                Urban
                            </option>

                        </select>

                        @error('area_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>


                    <div class="col-md-4 mb-3">
                        <label>Illumination <span class="text-danger">*</span></label>
                        <select name="illumination_id" class="form-control @error('illumination_id') is-invalid @enderror">
                            @foreach ($illuminations as $ill)
                                <option value="{{ $ill->id }}"
                                    {{ old('illumination_id', $media->illumination_id) == $ill->id ? 'selected' : '' }}>
                                    {{ $ill->illumination_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- <div class="col-md-4 mb-3">
                    <label>Min Booking Days *</label>
                    <input type="number" name="minimum_booking_days"
                        value="{{ old('minimum_booking_days', $media->minimum_booking_days) }}"
                        class="form-control">
                </div> --}}

                    <div class="col-md-4 mb-3">
                        <label>Address <span class="text-danger">*</span></label>
                        <textarea name="address" class="form-control">{{ old('address', $media->address) }}</textarea>
                    </div>
                </div>
                {{-- ================= MALL MEDIA ================= --}}
                <div class="row" id="mallMedia">
                    <div class="col-md-6 mb-3">
                        <label>Mall Name <span class="text-danger">*</span></label>
                        <input type="text" name="mall_name" value="{{ old('mall_name', $media->mall_name) }}"
                            class="form-control @error('mall_name') is-invalid @enderror">
                        @error('mall_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Media Format <span class="text-danger">*</span></label>
                        <select name="media_format" class="form-control @error('media_format') is-invalid @enderror">
                            <option value="">Select Media Format</option>
                            @foreach (['Standee', 'Backlit Panel', 'LED', 'Banner'] as $format)
                                <option value="{{ $format }}"
                                    {{ old('media_format', $media->media_format) == $format ? 'selected' : '' }}>
                                    {{ $format }}
                                </option>
                            @endforeach
                        </select>
                        @error('media_format')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                {{-- ================= AIRPORT BRANDING ================= --}}
                <div class="row" id="airportBranding">
                    <div class="col-md-4 mb-3">
                        <label>Airport Name *</label>
                        <input type="text" name="airport_name"
                            value="{{ old('airport_name', $media->airport_name) }}" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Zone *</label>
                        <select name="zone_type" class="form-control">
                            <option value="">Select Zone</option>
                            @foreach (['Arrival', 'Departure'] as $zone)
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
                            @foreach (['Backlit', 'LED', 'Standee'] as $type)
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
                        <input type="text" name="building_name"
                            value="{{ old('building_name', $media->building_name) }}"
                            class="form-control @error('building_name') is-invalid @enderror">
                        @error('building_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Branding Type <span class="text-danger">*</span></label>
                        <select name="wall_length" class="form-control @error('wall_length') is-invalid @enderror">
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
                            @foreach (['Auto', 'Bus', 'Cab', 'Metro'] as $t)
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
                            @foreach (['Full Wrap', 'Partial Wrap', 'Back Panel'] as $b)
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
                            value="{{ old('vehicle_count', $media->vehicle_count) }}" class="form-control">
                    </div>
                </div>

                {{-- ================= COMMON ================= --}}
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label>Width <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="width" name="width"
                            value="{{ old('width', $media->width) }}" class="form-control">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>Height <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="height" name="height"
                            value="{{ old('height', $media->height) }}" class="form-control">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>Latitude <span class="text-danger">*</span></label>
                        <input type="text" name="latitude" value="{{ old('latitude', $media->latitude) }}"
                            class="form-control">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>Longitude <span class="text-danger">*</span></label>
                        <input type="text" name="longitude" value="{{ old('longitude', $media->longitude) }}"
                            class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Price <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="price" value="{{ old('price', $media->price) }}"
                            class="form-control">
                    </div>

                    {{-- <div class="col-md-4 mb-3">
                    <label>Vendor Name<span class="text-danger">*</span></label>
                    <input type="text" name="vendor_name"
                        value="{{ old('vendor_name', $media->vendor_name) }}"
                        class="form-control">
                </div> --}}

                    <div class="col-md-3 mb-3">
                        <label>360 View Link </label>
                        <input type="text" name="video_link" value="{{ old('video_link', $media->video_link) }}"
                            class="form-control @error('video_link') is-invalid @enderror">
                        @error('video_link')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
                {{-- ================= WALL WRAP ================= --}}
                <div class="row" id="wallWrap">
                    <div class="col-md-3 mb-3">
                        <label>Area (sq.ft) <span class="text-danger">*</span></label>
                        <input type="text" name="area_auto" value="{{ old('area_auto', $media->area_auto) }}"
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // ORIGINAL VALUES (EDIT PAGE LOAD TIME)
        let originalVendorId = "{{ $media->vendor_id }}";
        let originalMediaCode = "{{ $media->media_code }}";
    </script>

    <script>
        $(document).ready(function() {

            function hideAllSections() {
                $('#billboardsId, #mallMedia, #airportBranding, #transmitMedia, #officeBranding, #wallWrap')
                    .hide();
            }

            function showSection(category) {
                hideAllSections();
                if (!category) return;

                //  HOARDINGS
                if (category.includes('hoardings')) {
                    $('#billboardsId').show();
                    $('#radiusSection').show();
                }

                //  DIGITAL WALL / WALL PAINTING
                if (category.includes('wall')) {
                    $('#wallWrap').show();
                    $('#radiusSection').show();
                }

                // if (category.includes('hoardings')) $('#billboardsId').show();
                if (category.includes('mall')) $('#mallMedia').show();
                if (category.includes('airport')) $('#airportBranding').show();
                if (category.includes('transit') || category.includes('transmit')) $('#transmitMedia').show();
                if (category.includes('office')) $('#officeBranding').show();
                // if (category.includes('wall')) $('#wallWrap').show();
            }

            let selectedCategory = ($('#category_slug').val() || '').toLowerCase();
            console.log('Category:', selectedCategory); // debug

            showSection(selectedCategory);

        });
    </script>

    <script>
        $(document).ready(function() {

            function calculateArea() {
                let width = parseFloat($('#width').val()) || 0;
                let height = parseFloat($('#height').val()) || 0;

                if (width > 0 && height > 0) {
                    $('input[name="area_auto"]').val((width * height).toFixed(2));
                }
            }

            $('#width, #height').on('input', calculateArea);
            calculateArea(); // run on load

            /* =========================
               MEDIA CODE LOGIC (EDIT)
            ========================= */

            // safety sync on load
            $('#media_code_hidden').val($('#media_code').val());

            // $('#vendor_id').on('change', function() {

            //     let selectedVendorId = $(this).val();

            //     // vendor cleared
            //     if (!selectedVendorId) {
            //         $('#media_code').val('');
            //         $('#media_code_hidden').val('');
            //         return;
            //     }

            //     //  SAME vendor → restore original code (NO increment)
            //     if (selectedVendorId == originalVendorId) {
            //         $('#media_code').val(originalMediaCode);
            //         $('#media_code_hidden').val(originalMediaCode);
            //         return;
            //     }

            //     //  DIFFERENT vendor → generate NEW code
            //     $.get("{{ url('media/next-code') }}/" + selectedVendorId, function(res) {
            //         $('#media_code').val(res.media_code);
            //         $('#media_code_hidden').val(res.media_code);
            //     });
            // });

        

            let categorySlug = ($('#category_slug').val() || '').toLowerCase();

            if (!categorySlug.includes('hoardings')) {
                $('#media_code').val('');
                $('#media_code_hidden').val('');
            }

        });








         function generateEditMediaCode() {

        let selectedVendorId = $('#vendor_id').val();
        let categorySlug = ($('#category_slug').val() || '').toLowerCase();

        // NOT HOARDINGS
        if (!categorySlug.includes('hoardings')) {
            $('#media_code').val('');
            $('#media_code_hidden').val('');
            return;
        }

        // NO vendor
        if (!selectedVendorId) {
            $('#media_code').val('');
            $('#media_code_hidden').val('');
            return;
        }

        // SAME vendor → keep original
        if (selectedVendorId == originalVendorId) {
            $('#media_code').val(originalMediaCode);
            $('#media_code_hidden').val(originalMediaCode);
            return;
        }

        // DIFFERENT vendor → generate NEW code
        $.get("{{ route('media.next.code') }}", {
            vendor_id: selectedVendorId,
            state_id: $('#state_id').val(),
            city_id: $('#city_id').val()
        }, function (res) {

            $('#media_code').val(res.media_code);
            $('#media_code_hidden').val(res.media_code);

        });
    }



    $('#area_id').on('change', function () {

    let areaId = $(this).val();

    if (!areaId) return;

    // get parent location (state/city)
    $.get("{{ url('get-area-parents') }}/" + areaId, function (res) {

        // update hidden fields
        $('#state_id').val(res.state_id);
        $('#city_id').val(res.city_id);

        // regenerate media code
        generateEditMediaCode();

    });

});

    // 🔥 RUN ON PAGE LOAD (VERY IMPORTANT)
    generateEditMediaCode();

    // vendor change
    $('#vendor_id').on('change', generateEditMediaCode);

    </script>
@endsection
@endsection

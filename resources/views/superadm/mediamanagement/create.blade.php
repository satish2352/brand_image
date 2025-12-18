@extends('superadm.layout.master')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <h4 class="mb-4">Add Media</h4>

        <form method="POST"
              action="{{ route('media.store') }}"
              enctype="multipart/form-data">
            @csrf

            {{-- ================= HIDDEN LOCATION FIELDS ================= --}}
            <input type="hidden" name="state_id" id="state_id">
            <input type="hidden" name="district_id" id="district_id">
            <input type="hidden" name="city_id" id="city_id">

            <div class="row">

                {{-- ================= AREA (ONLY ONE DROPDOWN) ================= --}}
                <div class="col-md-3 mb-3">
                    <label>Area <span class="text-danger">*</span></label>
                    <select id="area" name="area_id"
                            class="form-control @error('area_id') is-invalid @enderror">
                        <option value="">Select Area</option>
                    </select>
                    @error('area_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- ================= BASIC INFO ================= --}}
                <div class="col-md-3 mb-3">
                    <label>Category <span class="text-danger">*</span></label>
                    <select name="category_id"
                            class="form-control @error('category_id') is-invalid @enderror">
                        <option value="">Select Category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}"
                                {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->category_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label>Media Code <span class="text-danger">*</span></label>
                    <input type="text" name="media_code"
                           value="{{ old('media_code') }}"
                           class="form-control @error('media_code') is-invalid @enderror">
                    @error('media_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label>Media Title <span class="text-danger">*</span></label>
                    <input type="text" name="media_title"
                           value="{{ old('media_title') }}"
                           class="form-control @error('media_title') is-invalid @enderror">
                    @error('media_title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                {{-- ================= DIMENSIONS ================= --}}
                <div class="col-md-3 mb-3">
                    <label>Width (ft) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="width"
                           value="{{ old('width') }}"
                           class="form-control @error('width') is-invalid @enderror">
                            @error('width')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label>Height (ft) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="height"
                           value="{{ old('height') }}"
                           class="form-control @error('height') is-invalid @enderror">
                            @error('height')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label>Facing <span class="text-danger">*</span></label>
                    <select name="facing_id" class="form-control @error('facing_id') is-invalid @enderror">
                        <option value="">Select Facing</option>
                        @foreach($facings as $face)
                            <option value="{{ $face->id }}"
                                {{ old('facing_id') == $face->id ? 'selected' : '' }}>
                                {{ $face->facing_name }}
                            </option>
                        @endforeach
                    </select>
                     @error('facing_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label>Illumination <span class="text-danger">*</span></label>
                    <select name="illumination_id" class="form-control @error('illumination_id') is-invalid @enderror">
                        <option value="">Select</option>
                        @foreach($illuminations as $ill)
                            <option value="{{ $ill->id }}"
                                {{ old('illumination_id') == $ill->id ? 'selected' : '' }}>
                                {{ $ill->illumination_name }}
                            </option>
                        @endforeach
                    </select>
                     @error('illumination_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- ================= GEO ================= --}}
                <div class="col-md-3 mb-3">
                    <label>Latitude <span class="text-danger">*</span></label>
                    <input type="text" name="latitude"
                           value="{{ old('latitude') }}"
                           class="form-control @error('latitude') is-invalid @enderror">
                            @error('latitude')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label>Longitude <span class="text-danger">*</span></label>
                    <input type="text" name="longitude"
                           value="{{ old('longitude') }}"
                           class="form-control @error('longitude') is-invalid @enderror">
                            @error('longitude')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- ================= PRICING ================= --}}
                <div class="col-md-3 mb-3">
                    <label>Minimum Booking Days <span class="text-danger">*</span></label>
                    <input type="number" name="minimum_booking_days"
                           value="{{ old('minimum_booking_days',1) }}"
                           class="form-control @error('minimum_booking_days') is-invalid @enderror">
                            @error('minimum_booking_days')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label>Price <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="price"
                           value="{{ old('price') }}"
                           class="form-control @error('price') is-invalid @enderror">
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label>Vendor Name</label>
                    <input type="text" name="vendor_name"
                           value="{{ old('vendor_name') }}"
                           class="form-control @error('vendor_name') is-invalid @enderror">
                           @error('vendor_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                {{-- ================= ADDRESS ================= --}}
                <div class="col-md-3 mb-3">
                    <label>Address <span class="text-danger">*</span></label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                     @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                {{-- ================= IMAGES ================= --}}
                <div class="col-md-6 mb-4">
    <label>Images</label>
<input type="file"
       name="images[]"
       id="images"
       multiple
       class="form-control">

<div id="imagePreview" class="d-flex flex-wrap mt-2"></div>


    @error('images.*')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror

    {{-- IMAGE PREVIEW --}}
    <div id="imagePreview" class="d-flex flex-wrap mt-2"></div>
</div>

                {{-- <div class="col-md-6 mb-4">
                    <label>Images</label>
                    <input type="file" name="images[]" multiple
                           class="form-control @error('images.*') is-invalid @enderror">
                    @error('images.*')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div> --}}

            </div>

            <div class="text-end">
                <a href="{{ route('media.list') }}" class="btn btn-secondary me-2">Cancel</a>
                <button class="btn btn-success">Save Media</button>
            </div>

        </form>
    </div>
</div>
@endsection

{{-- ================= SCRIPTS ================= --}}
@section('scripts')
<script>
$(function () {

    console.log('Loading areas...');

    $.get("{{ url('get-all-areas') }}", function (areas) {

        console.log('Areas response:', areas);

        areas.forEach(area => {
            $('#area').append(`
                <option 
                    value="${area.id}"
                    data-state="${area.state_id}"
                    data-district="${area.district_id}"
                    data-city="${area.city_id}">
                    ${area.common_stdiciar_name}
                </option>
            `);
        });
    });

    $('#area').on('change', function () {

        let selected = $(this).find(':selected');

        $('#state_id').val(selected.data('state'));
        $('#district_id').val(selected.data('district'));
        $('#city_id').val(selected.data('city'));

        console.log({
            area_id: $(this).val(),
            state_id: selected.data('state'),
            district_id: selected.data('district'),
            city_id: selected.data('city')
        });
    });

});
</script>
<script>
$(document).ready(function () {

    $('#images').on('change', function () {

        $('#imagePreview').empty(); // clear previous previews

        let files = this.files;

        if (files.length > 0) {

            $.each(files, function (index, file) {

                if (!file.type.startsWith('image/')) return;

                let reader = new FileReader();

                reader.onload = function (e) {
                    $('#imagePreview').append(`
                        <div class="me-2 mb-2 border rounded">
                            <img src="${e.target.result}"
                                 width="100"
                                 height="100"
                                 style="object-fit:cover;">
                        </div>
                    `);
                };

                reader.readAsDataURL(file);
            });
        }
    });

});
</script>


@endsection

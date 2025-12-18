{{-- ===============================
    EXTEND MASTER LAYOUT
    This loads header, sidebar, footer
================================ --}}
@extends('superadm.layout.master')

{{-- ===============================
    MAIN CONTENT SECTION
================================ --}}
@section('content')

<div class="row">
    <div class="col-lg-12 mx-auto">
        <div class="card shadow-sm">
            <div class="card-body">

                {{-- PAGE TITLE --}}
                <h4 class="mb-4">Edit Media</h4>

                {{-- ===============================
                    FORM START
                    - POST method
                    - Update route with encoded ID
                    - multipart for image upload
                ================================ --}}
                <form action="{{ route('media.update', $encodedId) }}"
                      method="POST"
                      enctype="multipart/form-data">
                    @csrf

                    {{-- ===============================
                        HIDDEN LOCATION FIELDS
                        (Already saved, no dropdown here)
                    ================================ --}}
                    <input type="hidden" name="state_id" value="{{ $media->state_id }}">
                    <input type="hidden" name="district_id" value="{{ $media->district_id }}">
                    <input type="hidden" name="city_id" value="{{ $media->city_id }}">
                    <input type="hidden" name="area_id" value="{{ $media->area_id }}">

                    <div class="row">

                        {{-- ===============================
                            CATEGORY DROPDOWN
                        ================================ --}}
                        <div class="col-md-3 mb-3">
                            <label>Category <span class="text-danger">*</span></label>
                            <select name="category_id"
                                    class="form-control @error('category_id') is-invalid @enderror" disabled>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ old('category_id', $media->category_id) == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->category_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>


                        <div class="col-md-3 mb-3">
                            <label>Area <span class="text-danger">*</span></label>

                            <select name="area_id"
                                    class="form-control @error('area_id') is-invalid @enderror">
                                <option value="">Select Area</option>

                                @foreach($areas as $area)
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

                        {{-- ===============================
                            MEDIA CODE
                        ================================ --}}
                        <div class="col-md-3 mb-3">
                            <label>Media Code <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="media_code"
                                   value="{{ old('media_code', $media->media_code) }}"
                                   class="form-control @error('media_code') is-invalid @enderror">
                            @error('media_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ===============================
                            MEDIA TITLE
                        ================================ --}}
                        <div class="col-md-3 mb-3">
                            <label>Media Title <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="media_title"
                                   value="{{ old('media_title', $media->media_title) }}"
                                   class="form-control @error('media_title') is-invalid @enderror">
                            @error('media_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- ===============================
                            WIDTH
                        ================================ --}}
                        <div class="col-md-3 mb-3">
                            <label>Width (ft) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01"
                                   name="width"
                                   value="{{ old('width', $media->width) }}"
                                   class="form-control @error('width') is-invalid @enderror">
                            @error('width')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ===============================
                            HEIGHT
                        ================================ --}}
                        <div class="col-md-3 mb-3">
                            <label>Height (ft) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01"
                                   name="height"
                                   value="{{ old('height', $media->height) }}"
                                   class="form-control @error('height') is-invalid @enderror">
                            @error('height')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ===============================
                            FACING
                        ================================ --}}
                        <div class="col-md-3 mb-3">
                            <label>Facing <span class="text-danger">*</span></label>
                            <select name="facing_id"
                                    class="form-control @error('facing_id') is-invalid @enderror">
                                <option value="">Select Facing</option>
                                @foreach($facings as $face)
                                    <option value="{{ $face->id }}"
                                        {{ old('facing_id', $media->facing_id) == $face->id ? 'selected' : '' }}>
                                        {{ $face->facing_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('facing_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ===============================
                            ILLUMINATION
                        ================================ --}}
                        <div class="col-md-3 mb-3">
                            <label>Illumination <span class="text-danger">*</span></label>
                            <select name="illumination_id"
                                    class="form-control @error('illumination_id') is-invalid @enderror">
                                <option value="">Select</option>
                                @foreach($illuminations as $ill)
                                    <option value="{{ $ill->id }}"
                                        {{ old('illumination_id', $media->illumination_id) == $ill->id ? 'selected' : '' }}>
                                        {{ $ill->illumination_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('illumination_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ===============================
                            GEO LOCATION
                        ================================ --}}
                        <div class="col-md-3 mb-3">
                            <label>Latitude <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="latitude"
                                   value="{{ old('latitude', $media->latitude) }}"
                                   class="form-control @error('latitude') is-invalid @enderror">
                            @error('latitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-3">
                            <label>Longitude <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="longitude"
                                   value="{{ old('longitude', $media->longitude) }}"
                                   class="form-control @error('longitude') is-invalid @enderror">
                            @error('longitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ===============================
                            BOOKING & PRICE
                        ================================ --}}
                        <div class="col-md-3 mb-3">
                            <label>Minimum Booking Days <span class="text-danger">*</span></label>
                            <input type="number"
                                   name="minimum_booking_days"
                                   value="{{ old('minimum_booking_days', $media->minimum_booking_days) }}"
                                   class="form-control @error('minimum_booking_days') is-invalid @enderror">
                            @error('minimum_booking_days')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-3">
                            <label>Price <span class="text-danger">*</span></label>
                            <input type="number" step="0.01"
                                   name="price"
                                   value="{{ old('price', $media->price) }}"
                                   class="form-control @error('price') is-invalid @enderror">
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ===============================
                            VENDOR NAME
                        ================================ --}}
                        <div class="col-md-3 mb-3">
                            <label>Vendor Name <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="vendor_name"
                                   value="{{ old('vendor_name', $media->vendor_name) }}"
                                   class="form-control @error('vendor_name') is-invalid @enderror">
                            @error('vendor_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                       {{-- ===============================
                            ADDRESS
                        ================================ --}}
                        <div class="col-md-3 mb-3">
                            <label>Address <span class="text-danger">*</span></label>
                            <textarea name="address"
                                      class="form-control @error('address') is-invalid @enderror"
                                      rows="2">{{ old('address', $media->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- ===============================
                            IMAGE UPLOAD
                        ================================ --}}
                        <div class="col-md-6 mb-4">
                            <label>Replace Images (Optional)</label>
                            <input type="file" name="images[]" multiple class="form-control">
                            <small class="text-muted">
                                Uploading new images will replace existing ones.
                            </small>
                        </div>

                    </div>

                    {{-- ===============================
                        FORM BUTTONS
                    ================================ --}}
                    <div class="text-end">
                        <a href="{{ route('media.list') }}" class="btn btn-secondary me-2">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            Update Media
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

@endsection

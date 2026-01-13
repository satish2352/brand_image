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
     #wallWrapSection,
#radiusSection {
    display: none;
}
</style>
@endsection
@section('content')


<div class="card shadow-sm">
    <div class="card-body">
        <h4 class="mb-4">Add Media</h4>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

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
                <div class="col-md-6 mb-3">
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
                <div class="col-md-6 mb-3">
                    <label>Category <span class="text-danger">*</span></label>
                   @php use Illuminate\Support\Str; @endphp

                   <select name="category_id" id="category_id"
        class="form-control @error('category_id') is-invalid @enderror">
    <option value="">Select Category</option>

    @foreach($categories as $cat)
        @php
            $slug = \Illuminate\Support\Str::slug($cat->slug ?? $cat->category_name);
        @endphp

        <option value="{{ $cat->id }}"
            data-category="{{ $slug }}"
            {{ old('category_id') == $cat->id ? 'selected' : '' }}>
            {{ $cat->category_name }}
        </option>
    @endforeach
</select>


                   {{-- <select name="category_id" id="category_id"
                                class="form-control @error('category_id') is-invalid @enderror">
                            <option value="">Select Category</option>

                            @foreach($categories as $cat)
                               
                               <option value="{{ $cat->id }}"
    data-category="{{ \Illuminate\Support\Str::slug($cat->slug ?? $cat->category_name) }}">
    {{ $cat->category_name }}
</option>


                            @endforeach
                        </select> --}}
                    @error('category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                {{-- ================= DIMENSIONS ================= --}}
            </div>
            <div class="row" id="billboardsId">
                {{-- <div class="col-md-4 mb-3">
                    <label>Media Code <span class="text-danger">*</span></label>
                    <input type="text" name="media_code"
                           value="{{ old('media_code') }}"
                           class="form-control @error('media_code') is-invalid @enderror">
                    @error('media_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div> --}}

                <div class="col-md-4 mb-3">
                    <label>Media Code</label>
                    <input type="text"
                        id="media_code"
                        class="form-control"
                        value="{{ old('media_code', $media->media_code ?? '') }}"
                        disabled>

                    {{-- hidden field (ACTUAL value submit होईल) --}}
                    <input type="hidden" name="media_code" id="media_code_hidden">
                </div>

                <div class="col-md-4 mb-3">
                    <label>Media Title <span class="text-danger">*</span></label>
                    <input type="text" name="media_title"
                           value="{{ old('media_title') }}"
                           class="form-control @error('media_title') is-invalid @enderror">
                    @error('media_title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                {{-- <div class="col-md-4 mb-3">
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
                </div> --}}
                <div class="col-md-4 mb-3">
                    <label>Facing <span class="text-danger">*</span></label>
                    <input 
                        type="text"
                        name="facing"
                        class="form-control @error('facing') is-invalid @enderror"
                        value="{{ old('facing') }}"
                        placeholder="Enter facing"
                    >

                    @error('facing')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
               <div class="col-md-3 mb-3">
                <label>Area Type <span class="text-danger">*</span></label>
                <select name="area_type" class="form-control @error('area_type') is-invalid @enderror">
                    <option value="">Select Area Type</option>
                    <option value="rural" {{ old('area_type') == 'rural' ? 'selected' : '' }}>Rural</option>
                    <option value="urban" {{ old('area_type') == 'urban' ? 'selected' : '' }}>Urban</option>
                </select>

                @error('area_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>


                    {{-- <div class="col-md-3 mb-3">
                    <label>Radius <span class="text-danger">*</span></label>
                    <select name="radius_id" class="form-control @error('radius_id') is-invalid @enderror">
                        <option value="">Select</option>
                        @foreach($radius as $radiusdata)
                            <option value="{{ $radiusdata->id }}"
                                {{ old('radius_id') == $radiusdata->id ? 'selected' : '' }}>
                                {{ $radiusdata->radius }}
                            </option>
                        @endforeach
                    </select>
                     @error('radius_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div> --}}
       

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
                {{-- <div class="col-md-3 mb-3">
                    <label>Minimum Booking Days <span class="text-danger">*</span></label>
                    <input type="number" name="minimum_booking_days"
                           value="{{ old('minimum_booking_days',1) }}"
                           class="form-control @error('minimum_booking_days') is-invalid @enderror">
                            @error('minimum_booking_days')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div> --}}
                <div class="col-md-6 mb-3">
                    <label>Address <span class="text-danger">*</span></label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                     @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
             <div class="row" id="mallMedia">
                  <div class="col-md-6 mb-3">
                        <label>Mall Name <span class="text-danger">*</span></label>
                        <input type="text" name="mall_name"
                            value="{{ old('mall_name') }}"
                            class="form-control @error('mall_name') is-invalid @enderror">
                        @error('mall_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                            <label>Media Format <span class="text-danger">*</span></label>
                            <select name="media_format"
                                    class="form-control @error('media_format') is-invalid @enderror">
                                <option value="">Select Media Format</option>
                                <option value="Standee" {{ old('media_format') == 'Standee' ? 'selected' : '' }}>Standee</option>
                                <option value="Backlit Panel" {{ old('media_format') == 'Backlit Panel' ? 'selected' : '' }}>Backlit Panel</option>
                                <option value="LED" {{ old('media_format') == 'LED' ? 'selected' : '' }}>LED</option>
                                <option value="Banner" {{ old('media_format') == 'Banner' ? 'selected' : '' }}>Banner</option>
                            </select>

                            @error('media_format')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
             </div>             
            <div class="row" id="airportBranding">
                    {{-- Airport Name --}}
                    <div class="col-md-4 mb-4">
                        <label>Airport Name <span class="text-danger">*</span></label>
                        <input type="text"
                            name="airport_name"
                            value="{{ old('airport_name') }}"
                            class="form-control @error('airport_name') is-invalid @enderror">
                        @error('airport_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Zone Type --}}
                    <div class="col-md-4 mb-4">
                        <label>Zone <span class="text-danger">*</span></label>
                        <select name="zone_type"
                                class="form-control @error('zone_type') is-invalid @enderror">
                            <option value="">Select Zone</option>
                            <option value="Arrival" {{ old('zone_type') == 'Arrival' ? 'selected' : '' }}>Arrival</option>
                            <option value="Departure" {{ old('zone_type') == 'Departure' ? 'selected' : '' }}>Departure</option>
                        </select>
                        @error('zone_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Media Type --}}
                    <div class="col-md-4 mb-4">
                        <label>Media Type <span class="text-danger">*</span></label>
                        <select name="media_type"
                                class="form-control @error('media_type') is-invalid @enderror">
                            <option value="">Select Media Type</option>
                            <option value="Backlit" {{ old('media_type') == 'Backlit' ? 'selected' : '' }}>Backlit</option>
                            <option value="LED" {{ old('media_type') == 'LED' ? 'selected' : '' }}>LED</option>
                            <option value="Standee" {{ old('media_type') == 'Standee' ? 'selected' : '' }}>Standee</option>
                        </select>
                        @error('media_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
            </div>
            <div class="row" id="transmitMedia">
                  {{-- Transit Type --}}
                    <div class="col-md-4 mb-4">
                        <label>Transit Type <span class="text-danger">*</span></label>
                        <select name="transit_type"
                                class="form-control @error('transit_type') is-invalid @enderror">
                            <option value="">Select Transit Type</option>
                            <option value="Auto" {{ old('transit_type') == 'Auto' ? 'selected' : '' }}>Auto</option>
                            <option value="Bus" {{ old('transit_type') == 'Bus' ? 'selected' : '' }}>Bus</option>
                            <option value="Cab" {{ old('transit_type') == 'Cab' ? 'selected' : '' }}>Cab</option>
                            <option value="Metro" {{ old('transit_type') == 'Metro' ? 'selected' : '' }}>Metro</option>
                        </select>
                        @error('transit_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Branding Type --}}
                    <div class="col-md-4 mb-4">
                        <label>Branding Type <span class="text-danger">*</span></label>
                        <select name="branding_type"
                                class="form-control @error('branding_type') is-invalid @enderror">
                            <option value="">Select Branding Type</option>
                            <option value="Full Wrap" {{ old('branding_type') == 'Full Wrap' ? 'selected' : '' }}>Full Wrap</option>
                            <option value="Partial Wrap" {{ old('branding_type') == 'Partial Wrap' ? 'selected' : '' }}>Partial Wrap</option>
                            <option value="Back Panel" {{ old('branding_type') == 'Back Panel' ? 'selected' : '' }}>Back Panel</option>
                        </select>
                        @error('branding_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Vehicle Count --}}
                    <div class="col-md-4 mb-4">
                        <label>Vehicle Count <span class="text-danger">*</span></label>
                        <input type="number"
                            name="vehicle_count"
                            min="1"
                            value="{{ old('vehicle_count') }}"
                            class="form-control @error('vehicle_count') is-invalid @enderror">
                        @error('vehicle_count')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
            </div>
            <div class="row" id="officeBranding">
                 {{-- Building Name --}}
                    <div class="col-md-6 mb-6">
                        <label>Building Name <span class="text-danger">*</span></label>
                        <input type="text"
                            name="building_name"
                            value="{{ old('building_name') }}"
                            class="form-control @error('building_name') is-invalid @enderror">
                        @error('building_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Branding Type --}}
                    <div class="col-md-6 mb-6">
                        <label>Branding Type <span class="text-danger">*</span></label>
                        <select name="wall_length"
                                class="form-control @error('wall_length') is-invalid @enderror">
                            <option value="">Select Branding Type</option>
                            <option value="Wall Wrap" {{ old('wall_length') == 'Wall Wrap' ? 'selected' : '' }}>Wall Wrap</option>
                            <option value="Standee" {{ old('wall_length') == 'Standee' ? 'selected' : '' }}>Standee</option>
                            <option value="LED" {{ old('wall_length') == 'LED' ? 'selected' : '' }}>LED</option>
                        </select>
                        @error('wall_length')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
            </div>
                        
            <div class="row">
                   <div class="col-md-3 mb-3">
                    <label>Width (ft) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="width" id="width"
                           value="{{ old('width') }}"
                           class="form-control @error('width') is-invalid @enderror">
                            @error('width')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label>Height (ft) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="height"  id="height"
                           value="{{ old('height') }}"
                           class="form-control @error('height') is-invalid @enderror">
                            @error('height')
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
                 <div class="col-md-3 mb-3">
                    <label>Monthly Price <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="price"
                           value="{{ old('price') }}"
                           class="form-control @error('price') is-invalid @enderror">
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                 {{-- <div class="col-md-3 mb-3">
                    <label>Vendor Name <span class="text-danger">*</span></label>
                    <input type="text" name="vendor_name"
                           value="{{ old('vendor_name') }}"
                           class="form-control @error('vendor_name') is-invalid @enderror">
                           @error('vendor_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div> --}}
                <div class="col-md-3 mb-3">
                    <label>Vendor <span class="text-danger">*</span></label>

                    {{-- <select name="vendor_id"
                        class="form-control @error('vendor_id') is-invalid @enderror">

                        <option value="">Select Vendor</option>

                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}"
                                {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->vendor_name }} - {{ $vendor->vendor_code }}
                            </option>
                        @endforeach

                    </select> --}}
                    <select name="vendor_id"
                            id="vendor_id"
                            class="form-control @error('vendor_id') is-invalid @enderror">

                        <option value="">Select Vendor</option>

                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}"
                                data-vendor-code="{{ $vendor->vendor_code }}"
                                {{ old('vendor_id', $media->vendor_id ?? '') == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->vendor_name }} - {{ $vendor->vendor_code }}
                            </option>
                        @endforeach
                    </select>

                    @error('vendor_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                 {{-- <div class="col-md-3 mb-3" id="radiusSection">
    <label>Radius <span class="text-danger">*</span></label>
    <select name="radius_id" class="form-control @error('radius_id') is-invalid @enderror">
        <option value="">Select</option>
        @foreach($radius as $radiusdata)
            <option value="{{ $radiusdata->id }}"
                {{ old('radius_id') == $radiusdata->id ? 'selected' : '' }}>
                {{ $radiusdata->radius }}
            </option>
        @endforeach
    </select>

    @error('radius_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div> --}}
              <div class="col-md-3 mb-4">
    <label>Images</label>

    <input type="file"
        name="images[]"
        id="images"
        multiple
        class="form-control
            @error('images') is-invalid @enderror
            @error('images.*') is-invalid @enderror">

    {{-- TOO MANY FILES --}}
    @error('images')
        <div class="invalid-feedback d-block">
            {{ $message }}
        </div>
    @enderror

    {{-- INDIVIDUAL FILE ERROR --}}
    @error('images.*')
        <div class="invalid-feedback d-block">
            {{ $message }}
        </div>
    @enderror

    <div id="imagePreview" class="d-flex flex-wrap mt-2"></div>
</div>
  <div class="col-md-3 mb-3">
                    <label>360 View Link </label>
                    <input type="text" name="video_link"
                           value="{{ old('video_link') }}"
                           class="form-control @error('video_link') is-invalid @enderror">
                    @error('video_link')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>


            </div>
             <div class="row" id="wallWrapSection">
                 {{-- <div class="col-md-3 mb-3">
                    <label>Radius <span class="text-danger">*</span></label>
                    <select name="radius_id" class="form-control @error('radius_id') is-invalid @enderror">
                        <option value="">Select</option>
                        @foreach($radius as $radiusdata)
                            <option value="{{ $radiusdata->id }}"
                                {{ old('radius_id') == $radiusdata->id ? 'selected' : '' }}>
                                {{ $radiusdata->radius }}
                            </option>
                        @endforeach
                    </select>
                     @error('radius_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div> --}}
                <div class="col-md-3 mb-3">
                    <label>Area (sq.ft)</label>
                    <input type="text"
                        name="area_auto"
                        id="area_auto"
                        value="{{ old('area_auto') }}"
                        class="form-control @error('area_auto') is-invalid @enderror"
                        readonly>
                    @error('area_auto')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>


            </div>
            <div class="text-end d-flex justify-content-end">
                <a href="{{ route('media.list') }}" class="btn btn-secondary mr-2">Cancel</a>
                <button class="btn btn-success">Save Media</button>
            </div>

        </form>
    </div>
</div>
@endsection

{{-- ================= SCRIPTS ================= --}}
@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

<script>
    $(document).ready(function () {

    function hideAllSections() {
        $('#billboardsId, #mallMedia, #airportBranding, #transmitMedia, #officeBranding, #wallWrapSection')
            .hide();
    }

    function showSection(category) {
        hideAllSections();
    // $('#radiusSection').hide(); // default hidden
        if (!category) return;

        // if (category.includes('hoardings')) $('#billboardsId').show();
         //  HOARDINGS
    if (category.includes('hoardings')) {
        $('#billboardsId').show();
        
    }

    //  DIGITAL WALL / WALL PAINTING
    if (category.includes('wall')) {
        $('#wallWrapSection').show();
       
    }
        if (category.includes('mall')) $('#mallMedia').show();
        if (category.includes('airport')) $('#airportBranding').show();
        if (category.includes('transit') || category.includes('transmit')) $('#transmitMedia').show();
        if (category.includes('office')) $('#officeBranding').show();
        if (category.includes('wall')) $('#wallWrapSection').show();
    }

    let selectedCategory = $('#category_id').find(':selected').data('category');

    //  FORCE OPEN SECTION IF VALIDATION ERROR EXISTS
    @if ($errors->any())
        showSection(selectedCategory);
    @else
        showSection(selectedCategory);
    @endif

    $('#category_id').on('change', function () {
        showSection($(this).find(':selected').data('category'));
    });
});

</script>

<script>
$(document).ready(function () {

    function calculateArea() {
        let width = parseFloat($('#width').val()) || 0;
        let height = parseFloat($('#height').val()) || 0;

        if (width > 0 && height > 0) {
            $('#area_auto').val((width * height).toFixed(2));
        } else {
            $('#area_auto').val('');
        }
    }

    $('#width, #height').on('input', calculateArea);
    calculateArea();

    // 

    $('#vendor_id').on('change', function () {

        let vendorId = $(this).val();

        if (!vendorId) {
            $('#media_code').val('');
            $('#media_code_hidden').val('');
            return;
        }

        $.get("{{ url('media/next-code') }}/" + vendorId, function (res) {

            $('#media_code').val(res.media_code);
            $('#media_code_hidden').val(res.media_code);

        });
    });


});
</script>


@endsection

@extends('superadm.layout.master')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow-sm">
            <div class="card-body">

                <h4 class="mb-4">Add Area</h4>
                <form action="{{ route('area.store') }}" method="POST" novalidate>
                    @csrf

                    {{-- STATE --}}
                    <div class="form-group mb-3">
                        <label>State <span class="text-danger">*</span></label>
                        <select id="state" name="state_id"
                                class="form-control @error('state_id') is-invalid @enderror">
                            <option value="">Select State</option>
                        </select>
                        <input type="hidden" id="old_state" value="{{ old('state_id') }}">
                        @error('state_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- DISTRICT --}}
                    <div class="form-group mb-3">
                        <label>District <span class="text-danger">*</span></label>
                        <select id="district" name="district_id"
                                class="form-control @error('district_id') is-invalid @enderror">
                            <option value="">Select District</option>
                        </select>
                        <input type="hidden" id="old_district" value="{{ old('district_id') }}">
                        @error('district_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- CITY --}}
                    <div class="form-group mb-3">
                        <label>City <span class="text-danger">*</span></label>
                        <select id="city" name="city_id"
                                class="form-control @error('city_id') is-invalid @enderror">
                            <option value="">Select City</option>
                        </select>
                        <input type="hidden" id="old_city" value="{{ old('city_id') }}">
                        @error('city_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- AREA NAME --}}
                    <div class="form-group mb-4">
                        <label>Area Name <span class="text-danger">*</span></label>
                        <input type="text"
                            name="area_name"
                            value="{{ old('area_name') }}"
                            class="form-control @error('area_name') is-invalid @enderror">
                        @error('area_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- COMMON STDICIAR NAME --}}
                    <div class="form-group mb-4">
                        <label>Common State District City Area Name <span class="text-danger">*</span></label>
                        <input type="text"
                            name="common_stdiciar_name"
                            value="{{ old('common_stdiciar_name') }}"
                            class="form-control @error('common_stdiciar_name') is-invalid @enderror">
                        @error('common_stdiciar_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- LAT / LNG --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Latitude <span class="text-danger">*</span></label>
                            <input type="text" name="latitude"
                                value="{{ old('latitude') }}"
                                class="form-control @error('latitude') is-invalid @enderror">
                            @error('latitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Longitude <span class="text-danger">*</span></label>
                            <input type="text" name="longitude"
                                value="{{ old('longitude') }}"
                                class="form-control @error('longitude') is-invalid @enderror">
                            @error('longitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('area.list') }}" class="btn btn-secondary mr-3">Cancel</a>
                        <button type="submit" class="btn btn-success">Save Area</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection


@section('scripts')
<script>
$(document).ready(function () {

    /* ================= OLD VALUES ================= */
    let oldState    = $('#old_state').val();
    let oldDistrict = $('#old_district').val();
    let oldCity     = $('#old_city').val();

    /* ================= LOAD STATES ================= */
    $.get("{{ route('ajax.states') }}", function (states) {

        $('#state').html('<option value="">Select State</option>');

        $.each(states, function (i, state) {
            let selected = state.id == oldState ? 'selected' : '';
            $('#state').append(
                `<option value="${state.id}" ${selected}>${state.state_name}</option>`
            );
        });

        if (oldState) loadDistricts(oldState);
    });

    /* ================= DISTRICTS ================= */
    function loadDistricts(stateId) {

        $('#district').html('<option value="">Select District</option>');
        $('#city').html('<option value="">Select City</option>');

        if (!stateId) return;

        $.post("{{ route('ajax.districts') }}", {
            _token: "{{ csrf_token() }}",
            state_id: stateId
        }, function (districts) {

            $.each(districts, function (i, district) {
                let selected = district.id == oldDistrict ? 'selected' : '';
                $('#district').append(
                    `<option value="${district.id}" ${selected}>${district.district_name}</option>`
                );
            });

            if (oldDistrict) loadCities(oldDistrict);
        });
    }

    /* ================= CITIES ================= */
    function loadCities(districtId) {

        $('#city').html('<option value="">Select City</option>');

        if (!districtId) return;

        $.post("{{ route('ajax.cities') }}", {
            _token: "{{ csrf_token() }}",
            district_id: districtId
        }, function (cities) {

            $.each(cities, function (i, city) {
                let selected = city.id == oldCity ? 'selected' : '';
                $('#city').append(
                    `<option value="${city.id}" ${selected}>${city.city_name}</option>`
                );
            });
        });
    }

    /* ================= CHANGE EVENTS ================= */
    $('#state').change(function () {
        oldState = $(this).val();
        oldDistrict = null;
        oldCity = null;
        loadDistricts(oldState);
    });

    $('#district').change(function () {
        oldDistrict = $(this).val();
        oldCity = null;
        loadCities(oldDistrict);
    });

    /* ================= FORM VALIDATION ================= */
    $('form').on('submit', function (e) {

        let valid = true;
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        function error(el, msg) {
            el.addClass('is-invalid');
            el.after(`<div class="invalid-feedback">${msg}</div>`);
            valid = false;
        }

        if (!$('#state').val())    error($('#state'), 'Please select a state.');
        if (!$('#district').val()) error($('#district'), 'Please select a district.');
        if (!$('#city').val())     error($('#city'), 'Please select a city.');

        let area = $('input[name="area_name"]');
        if (!area.val()) error(area, 'Please enter the area name.');
        else if (area.val().length > 255) error(area, 'Area name must not exceed 255 characters.');

        let common = $('input[name="common_stdiciar_name"]');
        if (!common.val()) error(common, 'Please enter the common standard name.');
        else if (common.val().length > 255) error(common, 'Common standard name must not exceed 255 characters.');

        let lat = $('input[name="latitude"]');
        if (!lat.val()) error(lat, 'Latitude is required.');
        else if (isNaN(lat.val())) error(lat, 'Latitude must be numeric.');

        let lng = $('input[name="longitude"]');
        if (!lng.val()) error(lng, 'Longitude is required.');
        else if (isNaN(lng.val())) error(lng, 'Longitude must be numeric.');

        if (!valid) e.preventDefault();
    });

    // 
        function clearError(element) {
        element.removeClass('is-invalid');
        element.next('.invalid-feedback').remove();
    }

    // Text inputs
    $('input[type="text"]').on('input', function () {
        clearError($(this));
    });

    // Select dropdowns
    $('select').on('change', function () {
        clearError($(this));
    });


});
</script>

@endsection

@extends('superadm.layout.master')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow-sm">
            <div class="card-body">

                <h4 class="mb-4">Edit Area</h4>

                <form action="{{ route('area.update', $encodedId) }}" method="POST">
                    @csrf

                    {{-- STATE --}}
                    <div class="form-group mb-3">
                        <label>State *</label>
                        <select id="state" name="state_id" class="form-control">
                            <option value="{{ $area->state_id }}">{{ $area->state_name }}</option>
                        </select>
                    </div>

                    {{-- DISTRICT --}}
                    <div class="form-group mb-3">
                        <label>District *</label>
                        <select id="district" name="district_id" class="form-control">
                            <option value="{{ $area->district_id }}">{{ $area->district_name }}</option>
                        </select>
                    </div>

                    {{-- CITY --}}
                    <div class="form-group mb-3">
                        <label>City *</label>
                        <select id="city" name="city_id" class="form-control">
                            <option value="{{ $area->city_id }}">{{ $area->city_name }}</option>
                        </select>
                    </div>

                    {{-- AREA NAME --}}
                    <div class="form-group mb-3">
                        <label>Area Name *</label>
                        <input type="text"
                               name="area_name"
                               class="form-control"
                               value="{{ old('area_name', $area->area_name) }}">
                    </div>

                    {{-- COMMON NAME --}}
                    <div class="form-group mb-4">
                        <label>Common State District City Area Name *</label>
                        <input type="text"
                               name="common_stdiciar_name"
                               class="form-control"
                               value="{{ old('common_stdiciar_name', $area->common_stdiciar_name) }}">
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('area.list') }}" class="btn btn-secondary mr-3">Cancel</a>
                        <button type="submit" class="btn btn-success">Update Area</button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>
@section('scripts')
<script>
$(document).ready(function () {

    let selectedState    = "{{ $area->state_id }}";
    let selectedDistrict = "{{ $area->district_id }}";
    let selectedCity     = "{{ $area->city_id }}";

    /* ================= LOAD STATES ================= */
    $.get("{{ url('/get-states') }}", function (states) {

        $('#state').html('<option value="">Select State</option>');

        $.each(states, function (i, state) {
            let selected = state.location_id == selectedState ? 'selected' : '';
            $('#state').append(
                `<option value="${state.location_id}" ${selected}>${state.name}</option>`
            );
        });

        if (selectedState) {
            loadDistricts(selectedState);
        }
    });

    /* ================= LOAD DISTRICTS ================= */
    function loadDistricts(stateId) {

        $('#district').html('<option value="">Select District</option>');

        $.get("{{ url('/get-districts') }}/" + stateId, function (districts) {

            $.each(districts, function (i, district) {
                let selected = district.location_id == selectedDistrict ? 'selected' : '';
                $('#district').append(
                    `<option value="${district.location_id}" ${selected}>${district.name}</option>`
                );
            });

            if (selectedDistrict) {
                loadCities(selectedDistrict);
            }
        });
    }

    /* ================= LOAD CITIES ================= */
    function loadCities(districtId) {

        $('#city').html('<option value="">Select City</option>');

        $.get("{{ url('/get-cities') }}/" + districtId, function (cities) {

            $.each(cities, function (i, city) {
                let selected = city.location_id == selectedCity ? 'selected' : '';
                $('#city').append(
                    `<option value="${city.location_id}" ${selected}>${city.name}</option>`
                );
            });
        });
    }

    /* ================= CHANGE EVENTS ================= */
    $('#state').change(function () {
        selectedState = $(this).val();
        selectedDistrict = null;
        selectedCity = null;
        loadDistricts(selectedState);
    });

    $('#district').change(function () {
        selectedDistrict = $(this).val();
        selectedCity = null;
        loadCities(selectedDistrict);
    });

});
</script>
@endsection

@endsection

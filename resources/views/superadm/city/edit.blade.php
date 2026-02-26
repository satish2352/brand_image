@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">

                    <h4 class="mb-4">Edit City</h4>

                    <form action="{{ route('city.update', $encodedId) }}" method="POST">
                        @csrf

                        {{-- STATE --}}
                        <div class="form-group mb-3">
                            <label>State *</label>
                            <select id="state" name="state_id" class="form-control">
                                <option value="{{ $city->state_id }}">{{ $city->state_name }}</option>
                            </select>
                        </div>

                        {{-- DISTRICT --}}
                        <div class="form-group mb-3">
                            <label>District *</label>
                            <select id="district" name="district_id" class="form-control">
                                <option value="{{ $city->district_id }}">{{ $city->district_name }}</option>
                            </select>
                        </div>


                        {{-- AREA NAME --}}
                        <div class="form-group mb-3">
                            <label>city Name *</label>
                            <input type="text" name="city_name" class="form-control"
                                value="{{ old('city_name', $city->city_name) }}">
                        </div>


                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Latitude <span class="text-danger">*</span></label>
                                <input type="text" name="latitude" value="{{ old('latitude', $city->latitude) }}"
                                    class="form-control">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Longitude <span class="text-danger">*</span></label>
                                <input type="text" name="longitude" value="{{ old('longitude', $city->longitude) }}"
                                    class="form-control">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('city.list') }}" class="btn btn-secondary mr-3">Cancel</a>
                            <button type="submit" class="btn btn-success">Update City</button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
@section('scripts')
    <script>
        $(document).ready(function() {

            let selectedState = "{{ $city->state_id }}";
            let selectedDistrict = "{{ $city->district_id }}";


            /* ================= LOAD STATES ================= */
            $.get("{{ url('/area/get-states') }}", function(states) {

                $('#state').html('<option value="">Select State</option>');

                $.each(states, function(i, state) {
                    let selected = state.id == selectedState ? 'selected' : '';
                    $('#state').append(
                        `<option value="${state.id}" ${selected}>${state.state_name}</option>`
                    );
                });

                if (selectedState) {
                    loadDistricts(selectedState);
                }
            });

            /* ================= LOAD DISTRICTS ================= */
            function loadDistricts(stateId) {

                $('#district').html('<option value="">Select District</option>');

                $.get("{{ url('/area/get-districts') }}", {
                    state_id: stateId
                }, function(districts) {

                    $.each(districts, function(i, district) {
                        let selected = district.id == selectedDistrict ? 'selected' : '';
                        $('#district').append(
                            `<option value="${district.id}" ${selected}>${district.district_name}</option>`
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

                $.get("{{ url('/area/get-cities') }}", {
                    district_id: districtId
                }, function(cities) {

                    $.each(cities, function(i, city) {
                        let selected = city.id == selectedCity ? 'selected' : '';
                        $('#city').append(
                            `<option value="${city.id}" ${selected}>${city.city_name}</option>`
                        );
                    });
                });
            }

            /* ================= CHANGE EVENTS ================= */
            $('#state').change(function() {
                selectedState = $(this).val();
                selectedDistrict = null;
                selectedCity = null;
                loadDistricts(selectedState);
            });

            $('#district').change(function() {
                selectedDistrict = $(this).val();
                selectedCity = null;
                loadCities(selectedDistrict);
            });

            const onlyLetters = /[^a-zA-Z\s]/g;
            const onlyNumbersDot = /[^0-9.]/g;

            /* ===== LIVE INPUT RESTRICTION ===== */

            // City Name → letters only
            $('input[name="city_name"]').on('input', function() {
                this.value = this.value.replace(onlyLetters, '');
            });

            // Latitude & Longitude → numbers + dot
            $('input[name="latitude"], input[name="longitude"]').on('input', function() {
                this.value = this.value.replace(onlyNumbersDot, '');
            });

            /* ===== CLEAR ERROR (ONLY CURRENT FIELD) ===== */
            function clearError(el) {
                el.removeClass('is-invalid');
                el.closest('.form-group, .col-md-6')
                    .find('.invalid-feedback').remove();
            }

            $('input, select').on('input change', function() {
                clearError($(this));
            });

            /* ===== FORM SUBMIT VALIDATION ===== */
            $('form').on('submit.cityValidation', function(e) {

                let valid = true;

                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                function error(el, msg) {
                    el.addClass('is-invalid');
                    el.after(`<div class="invalid-feedback">${msg}</div>`);
                    valid = false;
                }

                // State
                if (!$('#state').val()) {
                    error($('#state'), 'Please select a state.');
                }

                // District
                if (!$('#district').val()) {
                    error($('#district'), 'Please select a district.');
                }

                // City Name
                let city = $('input[name="city_name"]');
                if (!city.val()) {
                    error(city, 'Please enter the city name.');
                } else if (city.val().length > 255) {
                    error(city, 'City name must not exceed 255 characters.');
                }

                // Latitude
                let lat = $('input[name="latitude"]');
                if (!lat.val()) {
                    error(lat, 'Latitude is required.');
                }

                // Longitude
                let lng = $('input[name="longitude"]');
                if (!lng.val()) {
                    error(lng, 'Longitude is required.');
                }

                if (!valid) e.preventDefault();
            });

        });
    </script>
@endsection
@endsection

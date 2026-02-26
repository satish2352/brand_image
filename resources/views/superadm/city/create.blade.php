@extends('superadm.layout.master')
@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-4">Add City</h4>
                    <form action="{{ route('city.store') }}" method="POST" novalidate>
                        @csrf
                        {{-- STATE --}}
                        <div class="form-group mb-3">
                            <label>State <span class="text-danger">*</span></label>
                            <select id="state" name="state_id"
                                class="form-control @error('state_id') is-invalid @enderror">
                                <option value="">Select State</option>
                            </select>
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
                            @error('district_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- City NAME --}}
                        <div class="form-group mb-4">
                            <label>City Name <span class="text-danger">*</span></label>
                            <input type="text" name="city_name" value="{{ old('city_name') }}"
                                class="form-control @error('city_name') is-invalid @enderror">
                            @error('city_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- LAT / LNG --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Latitude <span class="text-danger">*</span></label>
                                <input type="text" name="latitude" value="{{ old('latitude') }}"
                                    class="form-control @error('latitude') is-invalid @enderror">
                                @error('latitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Longitude <span class="text-danger">*</span></label>
                                <input type="text" name="longitude" value="{{ old('longitude') }}"
                                    class="form-control @error('longitude') is-invalid @enderror">
                                @error('longitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('city.list') }}" class="btn btn-secondary mr-3">Cancel</a>
                            <button type="submit" class="btn btn-success">Save City</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        let oldState = "{{ old('state_id') }}";
        let oldDistrict = "{{ old('district_id') }}";
        $(document).ready(function() {
            console.log('Area create page loaded');
            // ================= LOAD STATES =================
            $.get("{{ route('ajax.states') }}", function(response) {

                $('#state').html('<option value="">Select State</option>');

                $.each(response, function(i, item) {
                    let selected = item.id == oldState ? 'selected' : '';
                    $('#state').append(
                        `<option value="${item.id}" ${selected}>${item.state_name}</option>`
                    );
                });

                // OLD STATE असल्यास districts auto load
                if (oldState) {
                    loadDistricts(oldState);
                }
            });

            // ================= STATE → DISTRICTS =================
            $('#state').on('change', function() {

                let stateId = $(this).val();
                oldDistrict = null; // new selection
                $('#district').html('<option value="">Select District</option>');

                if (!stateId) return;

                loadDistricts(stateId);
            });
        });

        function loadDistricts(stateId) {
            $('#district').html('<option value="">Select District</option>');
            $.post("{{ route('ajax.districts') }}", {
                _token: "{{ csrf_token() }}",
                state_id: stateId
            }, function(response) {

                $.each(response, function(i, item) {
                    let selected = item.id == oldDistrict ? 'selected' : '';
                    $('#district').append(
                        `<option value="${item.id}" ${selected}>${item.district_name}</option>`
                    );
                });
            });
        }

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
    </script>
@endsection

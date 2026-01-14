@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">

                    <h4 class="mb-4">Edit Vendor</h4>

                    <form action="{{ route('vendor.update', $encodedId) }}" method="POST" novalidate>
                        @csrf

                        {{-- STATE --}}
                        <div class="mb-3">
                            <label>State <span class="text-danger">*</span></label>
                            <select id="state" name="state_id"
                                class="form-control @error('state_id') is-invalid @enderror">
                            </select>
                            @error('state_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- DISTRICT --}}
                        <div class="mb-3">
                            <label>District <span class="text-danger">*</span></label>
                            <select id="district" name="district_id"
                                class="form-control @error('district_id') is-invalid @enderror">
                            </select>
                            @error('district_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- CITY --}}
                        <div class="mb-3">
                            <label>City <span class="text-danger">*</span></label>
                            <select id="city" name="city_id"
                                class="form-control @error('city_id') is-invalid @enderror">
                            </select>
                            @error('city_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- VENDOR NAME --}}
                        <div class="mb-3">
                            <label>Vendor Name <span class="text-danger">*</span></label>
                            <input type="text" name="vendor_name"
                                class="form-control @error('vendor_name') is-invalid @enderror"
                                value="{{ old('vendor_name', $vendor->vendor_name) }}">
                            @error('vendor_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- VENDOR CODE --}}
                        <div class="mb-3">
                            <label>Vendor Code </label>
                            <input name="vendor_code" id="vendor_code"
                                class="form-control @error('vendor_code') is-invalid @enderror"
                                value="{{ old('vendor_code', $vendor->vendor_code ?? '') }}" readonly>
                            @error('vendor_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- MOBILE --}}
                        <div class="mb-3">
                            <label>Mobile <span class="text-danger">*</span></label>
                            <input type="text" name="mobile" maxlength="10"
                                class="form-control @error('mobile') is-invalid @enderror"
                                value="{{ old('mobile', $vendor->mobile) }}">
                            @error('mobile')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- EMAIL --}}
                        <div class="mb-3">
                            <label>Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $vendor->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ADDRESS --}}
                        <div class="mb-3">
                            <label>Address <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror">{{ old('address', $vendor->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('vendor.list') }}" class="btn btn-secondary me-2 mr-3">Cancel</a>
                            <button type="submit" class="btn btn-success">Update Vendor</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {

            let selectedState = "{{ old('state_id', $vendor->state_id) }}";
            let selectedDistrict = "{{ old('district_id', $vendor->district_id) }}";
            let selectedCity = "{{ old('city_id', $vendor->city_id) }}";

            // Load States
            $.get("{{ route('ajax.states') }}", function(states) {
                $('#state').html('<option value="">Select State</option>');
                $.each(states, function(i, s) {
                    let selected = s.id == selectedState ? 'selected' : '';
                    $('#state').append(
                        `<option value="${s.id}" ${selected}>${s.state_name}</option>`);
                });

                if (selectedState) loadDistricts(selectedState);
            });

            // Load Districts
            function loadDistricts(stateId) {
                $.post("{{ route('ajax.districts') }}", {
                        _token: "{{ csrf_token() }}",
                        state_id: stateId
                    },
                    function(districts) {

                        $('#district').html('<option value="">Select District</option>');
                        $.each(districts, function(i, d) {
                            let selected = d.id == selectedDistrict ? 'selected' : '';
                            $('#district').append(
                                `<option value="${d.id}" ${selected}>${d.district_name}</option>`);
                        });

                        if (selectedDistrict) loadCities(selectedDistrict);
                    });
            }

            // Load Cities
            function loadCities(districtId) {
                $.post("{{ route('ajax.cities') }}", {
                        _token: "{{ csrf_token() }}",
                        district_id: districtId
                    },
                    function(cities) {

                        $('#city').html('<option value="">Select City</option>');
                        $.each(cities, function(i, c) {
                            let selected = c.id == selectedCity ? 'selected' : '';
                            $('#city').append(
                                `<option value="${c.id}" ${selected}>${c.city_name}</option>`);
                        });
                    });
            }

            $('#state').change(function() {
                selectedDistrict = "";
                selectedCity = "";
                loadDistricts($(this).val());
            });

            $('#district').change(function() {
                selectedCity = "";
                loadCities($(this).val());
            });

        });
    </script>
@endsection

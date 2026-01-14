@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">

                    <h4 class="mb-4">Add Vendor</h4>
                    <form action="{{ route('vendor.store') }}" method="POST" novalidate>
                        @csrf

                        {{-- STATE --}}
                        <div class="mb-3">
                            <label>State <span class="text-danger">*</span></label>
                            <select id="state" name="state_id"
                                class="form-control @error('state_id') is-invalid @enderror"></select>
                            @error('state_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- DISTRICT --}}
                        <div class="mb-3">
                            <label>District <span class="text-danger">*</span></label>
                            <select id="district" name="district_id"
                                class="form-control @error('district_id') is-invalid @enderror"></select>
                            @error('district_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- CITY --}}
                        <div class="mb-3">
                            <label>City <span class="text-danger">*</span></label>
                            <select id="city" name="city_id"
                                class="form-control @error('city_id') is-invalid @enderror"></select>
                            @error('city_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label>Vendor Name <span class="text-danger">*</span></label>
                            <input name="vendor_name" class="form-control @error('vendor_name') is-invalid @enderror"
                                value="{{ old('vendor_name', $vendor->vendor_name ?? '') }}">
                            @error('vendor_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label>Vendor Code </label>
                            <input name="vendor_code" id="vendor_code"
                                class="form-control @error('vendor_code') is-invalid @enderror"
                                value="{{ old('vendor_code', $vendor->vendor_code ?? '') }}" readonly>
                            @error('vendor_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label>Mobile <span class="text-danger">*</span></label>
                            <input name="mobile" maxlength="10" class="form-control @error('mobile') is-invalid @enderror"
                                value="{{ old('mobile', $vendor->mobile ?? '') }}">
                            @error('mobile')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label>Email <span class="text-danger">*</span></label>
                            <input name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $vendor->email ?? '') }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label>Address <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror">{{ old('address', $vendor->address ?? '') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('vendor.list') }}" class="btn btn-secondary mr-3">Cancel</a>
                            <button type="submit" class="btn btn-success">Save Vendor</button>
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

            let oldState = "{{ old('state_id') }}";
            let oldDistrict = "{{ old('district_id') }}";
            let oldCity = "{{ old('city_id') }}";
            const csrf = "{{ csrf_token() }}";

            /* ============= LOAD STATES ============= */
            $.get("{{ route('ajax.states') }}", function(response) {
                $('#state').html('<option value="">Select State</option>');

                response.forEach(item => {
                    $('#state').append(`
                <option value="${item.id}" ${item.id == oldState ? 'selected' : ''}>
                    ${item.state_name}
                </option>
            `);
                });

                if (oldState) loadDistricts(oldState);
            });

            /* ============= STATE → DISTRICT ============= */
            function loadDistricts(stateId) {
                $('#district').html('<option value="">Select District</option>');
                $('#city').html('<option value="">Select City</option>');

                $.post("{{ route('ajax.districts') }}", {
                        _token: csrf,
                        state_id: stateId
                    },
                    function(response) {
                        response.forEach(item => {
                            $('#district').append(`
                        <option value="${item.id}" ${item.id == oldDistrict ? 'selected' : ''}>
                            ${item.district_name}
                        </option>
                    `);
                        });

                        if (oldDistrict) loadCities(oldDistrict);
                    }
                );
            }

            /* ============= DISTRICT → CITY ============= */
            function loadCities(districtId) {
                $('#city').html('<option value="">Select City</option>');

                $.post("{{ route('ajax.cities') }}", {
                        _token: csrf,
                        district_id: districtId
                    },
                    function(response) {
                        response.forEach(item => {
                            $('#city').append(`
                        <option value="${item.id}" ${item.id == oldCity ? 'selected' : ''}>
                            ${item.city_name}
                        </option>
                    `);
                        });
                    }
                );
            }

            /* ============= CHANGE EVENTS ============= */
            $('#state').on('change', function() {
                oldDistrict = null;
                oldCity = null;
                loadDistricts($(this).val());
            });

            $('#district').on('change', function() {
                oldCity = null;
                loadCities($(this).val());
            });

            /* ============= AUTO GENERATE VENDOR CODE ============= */
            $('input[name="vendor_name"]').on('input', function() {
                let vendorCode = $(this).val()
                    .trim()
                    .toLowerCase()
                    .replace(/[^a-z\s]/g, '')
                    .replace(/\s+/g, '');

                $('#vendor_code').val(vendorCode);
            });

        });
    </script>
@endsection

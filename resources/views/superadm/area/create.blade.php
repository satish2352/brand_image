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

                        {{-- CITY --}}
                        <div class="form-group mb-3">
                            <label>City <span class="text-danger">*</span></label>
                            <select id="city" name="city_id"
                                    class="form-control @error('city_id') is-invalid @enderror">
                                <option value="">Select City</option>
                            </select>
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

    console.log('Area create page loaded');

    // ================= LOAD STATES =================
    $.get("{{ url('/get-states') }}", function (response) {

        $('#state').html('<option value="" disabled selected>Select State</option>');

        if (!response.length) {
            $('#state').append('<option value="" disabled>No states found</option>');
            return;
        }

        $.each(response, function (i, item) {
            $('#state').append(
                `<option value="${item.location_id}">${item.name}</option>`
            );
        });
    });

    // ================= STATE → DISTRICT =================
    $('#state').on('change', function () {

        let stateId = $(this).val();

        $('#district').prop('disabled', true);
        $('#city').prop('disabled', true);

        $('#district').html('<option value="" disabled selected>Select District</option>');
        $('#city').html('<option value="" disabled selected>Select City</option>');

        if (!stateId) return;

        $.get("{{ url('/get-districts') }}/" + stateId, function (response) {

            if (!response.length) return;

            $('#district').prop('disabled', false);

            $.each(response, function (i, item) {
                $('#district').append(
                    `<option value="${item.location_id}">${item.name}</option>`
                );
            });
        });
    });

    // ================= DISTRICT → CITY =================
    $('#district').on('change', function () {

        let districtId = $(this).val();

        $('#city').prop('disabled', true);
        $('#city').html('<option value="" disabled selected>Select City</option>');

        if (!districtId) return;

        $.get("{{ url('/get-cities') }}/" + districtId, function (response) {

            if (!response.length) return;

            $('#city').prop('disabled', false);

            $.each(response, function (i, item) {
                $('#city').append(
                    `<option value="${item.location_id}">${item.name}</option>`
                );
            });
        });
    });

});
</script>
@endsection

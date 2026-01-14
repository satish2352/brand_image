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
        $(document).ready(function() {

            console.log('Area create page loaded');

            // ================= LOAD STATES =================
            $.get("{{ route('ajax.states') }}", function(response) {

                $('#state').html('<option value="" selected>Select State</option>');

                if (!response.length) {
                    $('#state').append('<option value="" disabled>No states found</option>');
                    return;
                }

                $.each(response, function(i, item) {
                    $('#state').append(
                        `<option value="${item.id}">${item.state_name}</option>`
                    );
                });
            });

            // ================= STATE → DISTRICTS =================
            $('#state').on('change', function() {

                let stateId = $(this).val();

                $('#district').prop('disabled', true).html('<option value="">Select District</option>');


                if (!stateId) return;

                $.post("{{ route('ajax.districts') }}", {
                    _token: "{{ csrf_token() }}",
                    state_id: stateId
                }, function(response) {

                    $('#district').prop('disabled', false);

                    $.each(response, function(i, item) {
                        $('#district').append(
                            `<option value="${item.id}">${item.district_name}</option>`
                        );
                    });
                });
            });



        });
    </script>
@endsection

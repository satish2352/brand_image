@extends('superadm.layout.master')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow-sm">
            <div class="card-body">

                <h4 class="mb-4">View Area</h4>

                {{-- STATE --}}
                <div class="form-group mb-3">
                    <label>State</label>
                    <input type="text"
                           class="form-control"
                           value="{{ $area->state_name }}"
                           readonly>
                </div>

                {{-- DISTRICT --}}
                <div class="form-group mb-3">
                    <label>District</label>
                    <input type="text"
                           class="form-control"
                           value="{{ $area->district_name }}"
                           readonly>
                </div>

                {{-- CITY --}}
                <div class="form-group mb-3">
                    <label>City</label>
                    <input type="text"
                           class="form-control"
                           value="{{ $area->city_name }}"
                           readonly>
                </div>

                {{-- AREA NAME --}}
                <div class="form-group mb-3">
                    <label>Area Name</label>
                    <input type="text"
                           class="form-control"
                           value="{{ $area->area_name }}"
                           readonly>
                </div>

                {{-- COMMON NAME --}}
                <div class="form-group mb-3">
                    <label>Common State District City Area Name</label>
                    <input type="text"
                           class="form-control"
                           value="{{ $area->common_stdiciar_name }}"
                           readonly>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Latitude</label>
                        <input type="text"
                               class="form-control"
                               value="{{ $area->latitude }}"
                               readonly>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Longitude</label>
                        <input type="text"
                               class="form-control"
                               value="{{ $area->longitude }}"
                               readonly>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('area.list') }}" class="btn btn-secondary">
                        Back
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

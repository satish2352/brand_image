@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">

                    <h4 class="mb-4">Add Illumination</h4>

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('illumination.store') }}" method="POST" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label>
                                Illumination Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="illumination_name"
                                class="form-control @error('illumination_name') is-invalid @enderror"
                                value="{{ old('illumination_name') }}" placeholder="e.g. non-lit">
                            @error('illumination_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Only letters, space and dash (-) allowed
                            </small>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('illumination.list') }}" class="btn btn-secondary me-2 mr-2">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-success">
                                Save Illumination
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection

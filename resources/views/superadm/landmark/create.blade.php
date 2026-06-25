@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">

                    <h4 class="mb-4">Add Landmark</h4>

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('landmark.store') }}" method="POST" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label>
                                Landmark Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="landmark_name"
                                class="form-control @error('landmark_name') is-invalid @enderror"
                                value="{{ old('landmark_name') }}" placeholder="e.g. Hospital / Railway Station">
                            @error('landmark_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Letters, numbers, space and dash (-) allowed
                            </small>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('landmark.list') }}" class="btn btn-secondary me-2 mr-2">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-success">
                                Save Landmark
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

@section('scripts')
    <script>
        $(document).ready(function() {

            const allowed = /[^A-Za-z0-9\s\-]/g;

            $('input[name="landmark_name"]').on('input', function() {
                this.value = this.value.replace(allowed, '');
            });

            function clearError(el) {
                el.removeClass('is-invalid');
                el.closest('.mb-3, .form-group').find('.invalid-feedback').remove();
            }

            $('input[name="landmark_name"]').on('input', function() {
                clearError($(this));
            });

            $('form').on('submit.landmarkValidation', function(e) {

                let valid = true;
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                function error(el, msg) {
                    el.addClass('is-invalid');
                    el.after(`<div class="invalid-feedback">${msg}</div>`);
                    valid = false;
                }

                let name = $('input[name="landmark_name"]');

                if (!name.val()) {
                    error(name, 'Landmark name is required');
                } else if (name.val().length > 255) {
                    error(name, 'Landmark name must not exceed 255 characters');
                } else if (!/^[A-Za-z0-9\s\-]+$/.test(name.val())) {
                    error(name, 'Only letters, numbers, spaces and dash (-) are allowed');
                }

                if (!valid) e.preventDefault();
            });

        });
    </script>
@endsection
@endsection

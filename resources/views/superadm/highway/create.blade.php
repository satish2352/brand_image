@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">

                    <h4 class="mb-4">Add Highway</h4>

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('highway.store') }}" method="POST" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label>
                                Highway Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="highway_name"
                                class="form-control @error('highway_name') is-invalid @enderror"
                                value="{{ old('highway_name') }}" placeholder="e.g. NH-48 / Mumbai-Agra Highway">
                            @error('highway_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Letters, numbers, space and dash (-) allowed
                            </small>
                        </div>

                        <div class="mb-3">
                            <label>Highway Type</label>
                            <select name="highway_type"
                                class="form-control @error('highway_type') is-invalid @enderror">
                                <option value="">Select</option>
                                @foreach (['National', 'State', 'Expressway', 'Other'] as $type)
                                    <option value="{{ $type }}"
                                        {{ old('highway_type') == $type ? 'selected' : '' }}>
                                        {{ $type }}
                                    </option>
                                @endforeach
                            </select>
                            @error('highway_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('highway.list') }}" class="btn btn-secondary me-2 mr-2">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-success">
                                Save Highway
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

            $('input[name="highway_name"]').on('input', function() {
                this.value = this.value.replace(allowed, '');
            });

            function clearError(el) {
                el.removeClass('is-invalid');
                el.closest('.mb-3, .form-group').find('.invalid-feedback').remove();
            }

            $('input[name="highway_name"]').on('input', function() {
                clearError($(this));
            });

            $('form').on('submit.highwayValidation', function(e) {

                let valid = true;
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                function error(el, msg) {
                    el.addClass('is-invalid');
                    el.after(`<div class="invalid-feedback">${msg}</div>`);
                    valid = false;
                }

                let name = $('input[name="highway_name"]');

                if (!name.val()) {
                    error(name, 'Highway name is required');
                } else if (name.val().length > 255) {
                    error(name, 'Highway name must not exceed 255 characters');
                } else if (!/^[A-Za-z0-9\s\-]+$/.test(name.val())) {
                    error(name, 'Only letters, numbers, spaces and dash (-) are allowed');
                }

                if (!valid) e.preventDefault();
            });

        });
    </script>
@endsection
@endsection

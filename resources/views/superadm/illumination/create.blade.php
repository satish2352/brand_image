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

    @section('scripts')
<script>
$(document).ready(function () {

    /* ================= REGEX ================= */
    const onlyLettersSpaceDash = /[^A-Za-z\s\-]/g;

    /* ================= LIVE INPUT RESTRICTION ================= */
    $('input[name="illumination_name"]').on('input', function () {
        this.value = this.value.replace(onlyLettersSpaceDash, '');
    });

    /* ================= CLEAR ERROR (CURRENT FIELD ONLY) ================= */
    function clearError(el) {
        el.removeClass('is-invalid');
        el.closest('.mb-3, .form-group')
          .find('.invalid-feedback').remove();
    }

    $('input[name="illumination_name"]').on('input', function () {
        clearError($(this));
    });

    /* ================= FORM SUBMIT VALIDATION ================= */
    $('form').on('submit.illuminationValidation', function (e) {

        let valid = true;
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        function error(el, msg) {
            el.addClass('is-invalid');
            el.after(`<div class="invalid-feedback">${msg}</div>`);
            valid = false;
        }

        let name = $('input[name="illumination_name"]');

        if (!name.val()) {
            error(name, 'Illumination name is required');
        }
        else if (name.val().length > 255) {
            error(name, 'Illumination name must not exceed 255 characters');
        }
        else if (!/^[A-Za-z\s\-]+$/.test(name.val())) {
            error(name, 'Only letters, spaces and dash (-) are allowed');
        }

        if (!valid) e.preventDefault();
    });

});
</script>
@endsection

@endsection

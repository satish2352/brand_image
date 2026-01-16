@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">

            <div class="card">
                <div class="card-body">

                    <h4>Edit Radius</h4>

                    <form action="{{ route('radius.update', $encodedId) }}" method="POST">
                        @csrf
                        <input type="hidden" name="id" value="{{ $data->id }}">

                        <div class="form-group">
                            <label>Radius (e.g. 1km) <span class="text-danger">*</span></label>
                            <input type="text" name="radius" class="form-control"
                                value="{{ old('radius', $data->radius) }}">
                            @error('radius')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group d-flex justify-content-end mt-3">
                            <a href="{{ route('radius.list') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button class="btn btn-success">Update</button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>

    @section('scripts')
<script>
$(document).ready(function () {

    /* ================= STRICT NUMERIC INPUT ================= */

    $('input[name="radius"]')
        .on('input', function () {
            // allow ONLY digits 0–9
            this.value = this.value.replace(/[^0-9]/g, '');
            clearError($(this));
        })
        .on('paste', function (e) {
            let pasted = (e.originalEvent || e).clipboardData.getData('text');
            if (!/^\d+$/.test(pasted)) {
                e.preventDefault(); // block paste if non-numeric
            }
        });

    /* ================= CLEAR ERROR ================= */
    function clearError(el) {
        el.removeClass('is-invalid');
        el.closest('.form-group')
          .find('.invalid-feedback, .text-danger')
          .remove();
    }

    /* ================= FORM SUBMIT VALIDATION ================= */
    $('form').on('submit.radiusValidation', function (e) {

        let valid = true;
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback, .text-danger').remove();

        function error(el, msg) {
            el.addClass('is-invalid');
            el.after(`<div class="invalid-feedback">${msg}</div>`);
            valid = false;
        }

        let radius = $('input[name="radius"]');
        let value  = radius.val();
        let maxVal = radius.data('max') ? parseInt(radius.data('max')) : 500;

        if (!value) {
            error(radius, 'Radius is required.');
        }
        else if (parseInt(value) < 1) {
            error(radius, 'Radius must be at least 1.');
        }
        else if (parseInt(value) > maxVal) {
            error(radius, `Radius must not exceed ${maxVal}.`);
        }

        if (!valid) e.preventDefault();
    });

});
</script>
@endsection

@endsection

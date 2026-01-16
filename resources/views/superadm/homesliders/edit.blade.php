@extends('superadm.layout.master')

@section('content')
    <div class="card">
        <div class="card-body">

            <h4>Edit Home Slider</h4>

            <form method="POST" enctype="multipart/form-data"
                action="{{ route('homeslider.update', base64_encode($slider->id)) }}" novalidate>
                @csrf

                {{-- DESKTOP IMAGE --}}
                <div class="mb-3">
                    <label>Desktop Image (1924 × 761) <span class="text-danger">*</span></label><br>

                    <img src="{{ config('fileConstants.IMAGE_VIEW') . $slider->desktop_image }}" class="mb-2 border"
                        style="height:120px">

                    <input type="file" id="desktop_image" name="desktop_image"
                        class="form-control @error('desktop_image') is-invalid @enderror">
                        <div id="desktop_error" class="text-danger mt-1"></div>

                    @error('desktop_image')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- MOBILE IMAGE --}}
                <div class="mb-3">
                    <label>Mobile Image (1360 × 1055) <span class="text-danger">*</span></label><br>

                    <img src="{{ config('fileConstants.IMAGE_VIEW') . $slider->mobile_image }}" class="mb-2 border"
                        style="height:120px">

                    <input type="file" id="mobile_image" name="mobile_image"
                        class="form-control @error('mobile_image') is-invalid @enderror">
                        <div id="mobile_error" class="text-danger mt-1"></div>

                    @error('mobile_image')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <button class="btn btn-primary">Update</button>
                <a href="{{ route('homeslider.list') }}" class="btn btn-secondary">Back</a>
            </form>

        </div>
    </div>

    <script>
$(document).ready(function () {

    function validateImage(input, options) {

        let file = input.files[0];
        let errorBox = $(options.error);
        errorBox.text('');

        if (!file) return true; // edit मध्ये optional

        // mime type
        let allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            errorBox.text('Image must be a jpg, jpeg, png or webp file');
            input.value = '';
            return false;
        }

        // size
        if (file.size > 1024 * 1024) {
            errorBox.text(options.sizeError);
            input.value = '';
            return false;
        }

        // dimension
        let img = new Image();
        img.onload = function () {
            if (this.width !== options.width || this.height !== options.height) {
                errorBox.text(options.dimensionError);
                input.value = '';
            }
        };
        img.src = URL.createObjectURL(file);

        return true;
    }

    // Desktop
    $('#desktop_image').on('change', function () {
        validateImage(this, {
            width: 1924,
            height: 761,
            error: '#desktop_error',
            sizeError: 'Desktop image size must be less than 1 MB',
            dimensionError: 'Desktop image size must be exactly 1924 x 761 pixels'
        });
    });

    // Mobile
    $('#mobile_image').on('change', function () {
        validateImage(this, {
            width: 1360,
            height: 1055,
            error: '#mobile_error',
            sizeError: 'Mobile image size must be less than 1 MB',
            dimensionError: 'Mobile image size must be exactly 1360 x 1055 pixels'
        });
    });

});
</script>

@endsection

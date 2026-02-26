@extends('superadm.layout.master')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">

            <h4 class="mb-4">Media Images</h4>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            <form id="imageUploadForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="media_id" value="{{ $media->id }}">

                {{-- IMAGE INPUT FIELD --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Upload Images (upload image size must be less then 1mb) <span
                                class="text-danger">*</span></label>
                        <input type="file" name="images[]" multiple class="form-control" accept="image/*">
                        <label class="form-label fw-semibold">
                            Upload Images (500×600 px)
                        </label>
                    </div>
                </div>

                {{-- IMAGE LIST --}}
                <div class="row">
                    @forelse($media->images as $img)
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4" id="img-{{ $img->id }}">
                            <div class="border rounded position-relative overflow-hidden">

                                <img src="{{ config('fileConstants.IMAGE_VIEW') . $img->images }}" class="img-fluid w-100"
                                    style="height:170px; object-fit:cover;">

                                <button type="button" class="btn btn-danger btn-sm position-absolute"
                                    style="top:6px; right:6px" onclick="deleteImage({{ $img->id }})">
                                    <i class="fa fa-trash"></i>
                                </button>

                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-4">
                            <p class="text-muted">No images uploaded yet.</p>
                        </div>
                    @endforelse
                </div>

                {{-- BUTTONS BELOW IMAGES --}}
                <div class="mt-3 d-flex gap-2">
                    <a href="{{ route('media.list') }}" class="btn btn-secondary m-1">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>

                    <button type="submit" class="btn btn-success m-1">
                        <i class="fa fa-upload"></i> Submit
                    </button>
                </div>

            </form>
            {{-- ================= IMAGE GALLERY ================= --}}
            <hr>
        </div>
    </div>
@endsection


@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function showAlert(type, message) {
            Swal.fire({
                icon: type, // success | error | warning | info
                text: message,
                confirmButtonText: 'OK'
            });
        }
    </script>

    {{-- ================= UPLOAD IMAGE ================= --}}
    <script>
        $('#imageUploadForm').on('submit', function(e) {
            e.preventDefault();

            let formData = new FormData(this);

            $.ajax({
                url: "{{ route('media.image.upload') }}",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function(res) {
                    if (res.status) {
                        location.reload();
                    } else {
                        // alert(res.message);
                        showAlert('error', res.message);
                    }
                },
                error: function(xhr) {

                    // Laravel validation errors
                    if (xhr.status === 422) {
                        if (xhr.responseJSON.errors) {
                            let firstError = Object.values(xhr.responseJSON.errors)[0][0];
                            showAlert('error', firstError);
                            return;
                        }
                        // CASE 2: normal validation errors
                        if (xhr.responseJSON.errors) {
                            let errorMessage = '';
                            // ONLY FIRST ERROR MESSAGE
                            let firstError = Object.values(xhr.responseJSON.errors)[0][0];

                            // alert(errorMessage);
                            // showAlert('error', errorMessage);
                            showAlert('error', errorMessage);
                            return;
                        }
                    }
                    // Other errors
                    // alert('Image upload failed. Please try again.');
                    showAlert('error', 'Image upload failed. Please try again.');
                }
            });
        });
    </script>

    {{-- ================= DELETE IMAGE ================= --}}
    <script>
        function deleteImage(imageId) {

            Swal.fire({
                title: 'Are you sure?',
                text: 'This image will be deleted',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then((result) => {

                if (!result.isConfirmed) return;

                $.ajax({
                    url: "{{ route('media.image.delete') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        image_id: imageId
                    },
                    success: function(res) {
                        if (res.status) {
                            Swal.fire({
                                icon: 'success',
                                text: res.message,
                                timer: 1200,
                                showConfirmButton: false
                            });

                            $('#img-' + imageId).fadeOut(300, function() {
                                $(this).remove();
                            });
                        } else {
                            showAlert('error', res.message);
                        }
                    },
                    error: function() {
                        showAlert('error', 'Delete failed');
                    }
                });

            });
        }
    </script>
@endsection

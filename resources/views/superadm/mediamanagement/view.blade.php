@extends('superadm.layout.master')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">

            <h4 class="mb-4">Media Images</h4>
            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            {{-- ================= ADD IMAGE FORM ================= --}}
            <form id="imageUploadForm" enctype="multipart/form-data" class="mb-4">
                @csrf
                <input type="hidden" name="media_id" value="{{ $media->id }}">

                <div class="row align-items-center">
                    <div class="col-md-6">
                        <input type="file" name="images[]" multiple class="form-control" accept="image/*">
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-upload"></i> Submit
                        </button>
                    </div>
                </div>
            </form>

            {{-- ================= IMAGE GRID ================= --}}
            <div class="row">
                @forelse($media->images as $img)
                    <div class="col-md-3 mb-4" id="img-{{ $img->id }}">
                        <div class="border rounded p-2 position-relative">

                            <img src="{{ config('fileConstants.IMAGE_VIEW') . $img->images }}" class="img-fluid rounded"
                                style="height:150px; width:100%; object-fit:cover;">

                            <button type="button" class="btn btn-danger btn-sm position-absolute"
                                style="top:5px; right:5px" onclick="deleteImage({{ $img->id }})">
                                <i class="fa fa-trash"></i>
                            </button>

                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <p class="text-muted">No images available.</p>
                    </div>
                @endforelse
            </div>

            <a href="{{ route('media.list') }}" class="btn btn-secondary mt-3">
                Back
            </a>

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

                        // CASE 1: custom message (total > 10 images)
                        if (xhr.responseJSON.message) {
                            // alert(xhr.responseJSON.message);
                            showAlert('warning', xhr.responseJSON.message);
                            return;
                        }

                        // CASE 2: normal validation errors
                        if (xhr.responseJSON.errors) {
                            let errorMessage = '';

                            $.each(xhr.responseJSON.errors, function(key, value) {
                                errorMessage += value[0] + '\n';
                            });

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
                // error: function () {
                //     alert('Image upload failed');
                // }
            });
        });
    </script>

    {{-- ================= DELETE IMAGE ================= --}}
    <script>
        // function deleteImage(imageId) {

        //     if (!confirm('Delete this image?')) return;

        //     $.ajax({
        //         url: "{{ route('media.image.delete') }}",
        //         type: "POST",
        //         data: {
        //             _token: "{{ csrf_token() }}",
        //             image_id: imageId
        //         },
        //         success: function(res) {
        //             if (res.status) {
        //                 $('#img-' + imageId).fadeOut(300, function () {
        //                     $(this).remove();
        //                 });
        //             } else {
        //                 alert(res.message);
        //             }
        //         },
        //         error: function () {
        //             alert('Delete failed');
        //         }
        //     });
        // }
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

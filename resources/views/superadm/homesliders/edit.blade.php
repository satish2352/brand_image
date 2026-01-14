@extends('superadm.layout.master')

@section('content')
<div class="card">
    <div class="card-body">

        <h4>Edit Home Slider</h4>

        <form method="POST"
              enctype="multipart/form-data"
              action="{{ route('homeslider.update', base64_encode($slider->id)) }}">
            @csrf

            {{-- DESKTOP IMAGE --}}
            <div class="mb-3">
                <label>Desktop Image (1924 × 761)</label><br>

                <img src="{{ config('fileConstants.IMAGE_VIEW') . $slider->desktop_image }}"
                     class="mb-2 border"
                     style="height:120px">

                <input type="file"
                       name="desktop_image"
                       class="form-control @error('desktop_image') is-invalid @enderror">

                @error('desktop_image')
                    <div class="text-danger mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- MOBILE IMAGE --}}
            <div class="mb-3">
                <label>Mobile Image (1360 × 1055)</label><br>

                <img src="{{ config('fileConstants.IMAGE_VIEW') . $slider->mobile_image }}"
                     class="mb-2 border"
                     style="height:120px">

                <input type="file"
                       name="mobile_image"
                       class="form-control @error('mobile_image') is-invalid @enderror">

                @error('mobile_image')
                    <div class="text-danger mt-1">{{ $message }}</div>
                @enderror
            </div>

            <button class="btn btn-primary">Update</button>
            <a href="{{ route('homeslider.list') }}" class="btn btn-secondary">Back</a>
        </form>

    </div>
</div>
@endsection

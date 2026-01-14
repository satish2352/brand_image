@extends('superadm.layout.master')

@section('content')
    <div class="card">
        <div class="card-body">

            <h4>Add Home Slider</h4>

            <form method="POST" enctype="multipart/form-data" action="{{ route('homeslider.store') }}">
                @csrf

                {{-- DESKTOP IMAGE --}}
                <div class="mb-3">
                    <label>Desktop Image (1924 × 761)</label>
                    <input type="file" name="desktop_image"
                        class="form-control @error('desktop_image') is-invalid @enderror">

                    @error('desktop_image')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- MOBILE IMAGE --}}
                <div class="mb-3">
                    <label>Mobile Image (1360 × 1055)</label>
                    <input type="file" name="mobile_image"
                        class="form-control @error('mobile_image') is-invalid @enderror">

                    @error('mobile_image')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <button class="btn btn-primary">Submit</button>
                <a href="{{ route('homeslider.list') }}" class="btn btn-secondary">Back</a>
            </form>

        </div>
    </div>
@endsection

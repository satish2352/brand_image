@extends('website.layout')

@section('title', 'Contact Us')

@section('content')
<div class="container my-5">
    <h3 class="mb-4">Contact Us</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('contact.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Full Name *</label>
            <input type="text" name="full_name" class="form-control" value="{{ old('full_name') }}" >
            @error('full_name') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Mobile No *</label>
            <input type="text" name="mobile_no" class="form-control" value="{{ old('mobile_no') }}" >
            @error('mobile_no') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" >
            @error('email') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Address *</label>
            <textarea name="address" class="form-control" >{{ old('address') }}</textarea>
            @error('address') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Remark *</label>
            <textarea name="remark" class="form-control" >{{ old('remark') }}</textarea>
            @error('remark') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <button type="submit" class="btn btn-primary">
            Submit
        </button>
    </form>
</div>
@endsection

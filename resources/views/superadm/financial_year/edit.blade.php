@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h4>Edit Financial Year</h4>
                    <form action="{{ route('financial-year.update', $encodedId) }}" method="POST">
                        @csrf
                        <input type="hidden" name="id" value="{{ old('id', $data->id) }}">

                        <div class="form-group">
                            <label>Financial Year <span class="text-danger">*</span></label>
                            <input type="text" name="year" class="form-control"
                                   value="{{ old('year', $data->year) }}" placeholder="Example : 2025-2026">
                            @error('year')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Hidden Active Field --}}
                        <input type="hidden" name="is_active" value="{{ old('is_active', $data->is_active) }}">

                        <div class="form-group d-flex justify-content-end">
                            <a href="{{ route('financial-year.list') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn-success btn-add">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

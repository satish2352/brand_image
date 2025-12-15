@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h4>Add Financial Year</h4>
                    <form action="{{ route('financial-year.save') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Financial Year <span class="text-danger">*</span></label>
                            <input type="text" name="year" class="form-control" placeholder="Example : 2025-2026" value="{{ old('year') }}">
                            @error('year')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group d-flex justify-content-end">
                            <a href="{{ route('financial-year.list') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn-success btn-add">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

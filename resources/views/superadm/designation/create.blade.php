@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h4>Add Designation</h4>
                    <form action="{{ route('designations.save') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Designation Name <span class="text-danger">*</span></label>
                            <input type="text" name="designation" class="form-control" value="{{ old('designation') }}">
                            @error('designation')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Short Description <span class="text-danger">*</span></label>
                            <input type="text" name="short_description" class="form-control"
                                value="{{ old('short_description') }}">
                            @error('short_description')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group d-flex justify-content-end">
                            
                            <a href="{{ route('designations.list') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn-success btn-add">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

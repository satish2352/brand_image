@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h4>Add Employee Type</h4>
                    <form action="{{ route('employee-types.save') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Employee Type Name <span class="text-danger">*</span></label>
                            <input type="text" name="type_name" class="form-control" value="{{ old('type_name') }}">
                            @error('type_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Description <span class="text-danger">*</span></label>
                            <input type="text" name="description" class="form-control"
                                value="{{ old('description') }}">
                            @error('description')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group d-flex justify-content-end">
                            <a href="{{ route('employee-types.list') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn-success btn-add">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

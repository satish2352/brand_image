@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h4>Edit Employee Type</h4>
                    <form action="{{ route('employee-types.update', $encodedId) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Employee Type Name <span class="text-danger">*</span></label>
                            <input type="hidden" name="id" value="{{ old('id', $data->id) }}">
                            <input type="text" name="type_name" class="form-control"
                                value="{{ old('type_name', $data->type_name) }}">
                            @error('type_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Description <span class="text-danger">*</span></label>
                            <input type="text" name="description" class="form-control"
                                value="{{ old('description', $data->description) }}">
                            @error('description')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        {{-- <div class="form-group">
                            <label>Status</label>
                            <select name="is_active" class="form-control">
                                <option value="">Select status</option>
                                <option value="1" {{ old('is_active', $data->is_active) == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('is_active', $data->is_active) == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('is_active')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div> --}}
                        
                        {{-- Hidden Status Field --}}
                        <input type="hidden" name="is_active" value="1">

                        <div class="form-group d-flex justify-content-end">
                            <a href="{{ route('employee-types.list') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn-success btn-add">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

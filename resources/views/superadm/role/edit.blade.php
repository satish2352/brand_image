@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h4>Edit Role</h4>
                    <form action="{{ route('roles.update', $encodedId) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Role Name <span class="text-danger">*</span></label>
                            <input type="hidden" name="id" class="form-control" value="{{ old('id', $data->id) }}">
                            <input type="text" name="role" class="form-control"
                                value="{{ old('role', $data->role) }}">
                            @error('role')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Short Description <span class="text-danger">*</span></label>

                            <input type="text" name="short_description" class="form-control"
                                value="{{ old('short_description', $data->short_description) }}">
                            @error('short_description')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="is_active" class="form-control">
                                <option value="">select status</option>
                                <option value="1" {{ old('is_active', $data->is_active) == '1' ? 'selected' : '' }}>
                                    Active</option>
                                <option value="0" {{ old('is_active', $data->is_active) == '0' ? 'selected' : '' }}>
                                    Inactive</option>
                            </select>
                            @error('is_active')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group d-flex justify-content-end">
                            <a href="{{ route('roles.list') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn-success btn-add">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    {{-- @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif --}}

                    <h4>Edit Department</h4>
                    <form action="{{ route('departments.update', $encodedId) }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="plant_id">Select Plant <span class="text-danger">*</span></label>
                            <input type="hidden" name="id" class="form-control" value="{{ old('id', $data->id) }}">
                            <select name="plant_id" id="plant_id" class="form-control">
                                <option value="" disabled>Select Plant </option>
                                @foreach ($plants as $plant)
                                    <option value="{{ $plant->id }}"
                                        {{ $data->plant_id == $plant->id ? 'selected' : '' }}
                                        {{ old('plant_id') == $plant->id ? 'selected' : '' }}>
                                        {{ $plant->plant_name }} ({{ $plant->plant_code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('plant_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>


                        <div class="form-group">
                            <label>Department Code <span class="text-danger">*</span></label>
                            <input type="text" name="department_code" class="form-control"
                                value="{{ old('department_code', $data->department_code) }}">
                            @error('department_code')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>


                        <div class="form-group">
                            <label>Department Name <span class="text-danger">*</span></label>
                            <input type="text" name="department_name" class="form-control"
                                value="{{ old('department_name', $data->department_name) }}">
                            @error('department_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Department Short Name</label>
                            <input type="text" name="department_short_name" class="form-control"
                                value="{{ old('department_short_name', $data->department_short_name) }}">
                            @error('department_short_name')
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

                            <a href="{{ route('departments.list') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn-success btn-add">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

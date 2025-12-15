@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h4>Add Department</h4>
                    <form action="{{ route('departments.save') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="plant_id">Select Plant <span class="text-danger">*</span></label>
                            <select name="plant_id" id="plant_id" class="form-control">
                                <option value="" disabled selected>Select Plant </option>
                                @foreach ($plants as $plant)
                                    <option value="{{ $plant->id }}"
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
                                value="{{ old('department_code') }}">
                            @error('department_code')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>


                        <div class="form-group">
                            <label>Department Name <span class="text-danger">*</span></label>
                            <input type="text" name="department_name" class="form-control"
                                value="{{ old('department_name') }}">
                            @error('department_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Department Short Name</label>
                            <input type="text" name="department_short_name" class="form-control"
                                value="{{ old('department_short_name') }}">
                            @error('department_short_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group d-flex justify-content-end">
                            <a href="{{ route('departments.list') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn-success btn-add">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

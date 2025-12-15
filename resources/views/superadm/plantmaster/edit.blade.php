@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h4>Edit Plant</h4>
                    <form action="{{ route('plantmaster.update', $encodedId) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Plant Code <span class="text-danger">*</span></label>
                            <input type="hidden" name="id" class="form-control" value="{{ old('id', $data->id) }}">
                            <input type="text" value="{{ old('plant_code', $data->plant_code) }}" name="plant_code"
                                class="form-control" value="{{ old('plant_code') }}">
                            @error('plant_code')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Plant Name <span class="text-danger">*</span></label>
                            <input type="text" value="{{ old('plant_name', $data->plant_name) }}" name="plant_name"
                                class="form-control" value="{{ old('plant_name') }}">
                            @error('plant_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" value="{{ old('address', $data->address) }}" name="address"
                                class="form-control" value="{{ old('address') }}">
                            @error('address')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>City <span class="text-danger">*</span></label>
                            <input type="text" value="{{ old('city', $data->city) }}" name="city"
                                class="form-control" value="{{ old('city') }}">
                            @error('city')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Plant Short Name</label>
                            <input type="text" value="{{ old('plant_short_name', $data->plant_short_name) }}"
                                name="plant_short_name" class="form-control" value="{{ old('plant_short_name') }}">
                            @error('plant_short_name')
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
                            <a href="{{ route('plantmaster.list') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn-success btn-add">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

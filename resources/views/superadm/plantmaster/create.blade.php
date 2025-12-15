@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h4>Add Plant Details</h4>
                    <form action="{{ route('plantmaster.save') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Plant Code <span class="text-danger">*</span></label>
                            <input type="text" name="plant_code" class="form-control" value="{{ old('plant_code') }}">
                            @error('plant_code')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Plant Name <span class="text-danger">*</span></label>
                            <input type="text" name="plant_name" class="form-control" value="{{ old('plant_name') }}">
                            @error('plant_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address') }}">
                        </div>

                        <div class="form-group">
                            <label>City <span class="text-danger">*</span></label>
                            <input type="text" name="city" class="form-control" value="{{ old('city') }}">
                            @error('city')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Plant Short Name</label>
                            <input type="text" name="plant_short_name" class="form-control"
                                value="{{ old('plant_short_name') }}">
                        </div>
                        <div class="form-group d-flex justify-content-end">
                            <a href="{{ route('plantmaster.list') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn-success btn-add">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">

                    <h4>Edit State</h4>

                    <form action="{{ route('states.update', $encodedId) }}" method="POST">
                        @csrf

                        <input type="hidden" name="id" value="{{ old('id', $data->id) }}">

                        <div class="form-group">
                            <label>State Name <span class="text-danger">*</span></label>
                            <input type="text" name="state" class="form-control"
                                   value="{{ old('state', $data->state) }}">
                            @error('state')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Hidden Status Field -->
                        <input type="hidden" name="is_active" value="{{ old('is_active', $data->is_active) }}">

                        <div class="form-group d-flex justify-content-end mt-3">
                            <a href="{{ route('states.list') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn-success btn-add">Update</button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection

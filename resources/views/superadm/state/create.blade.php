@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h4>Add State</h4>

                    <form action="{{ route('states.save') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label>State Name <span class="text-danger">*</span></label>
                            <input type="text" name="state" class="form-control" value="{{ old('state') }}">
                            @error('state')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group d-flex justify-content-end mt-3">
                            <a href="{{ route('states.list') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn-success btn-add">Save</button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection

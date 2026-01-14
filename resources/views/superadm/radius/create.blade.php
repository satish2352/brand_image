@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">

            <div class="card">
                <div class="card-body">
                    <h4>Add Radius</h4>

                    <form action="{{ route('radius.save') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label>Radius (e.g. 5km-10km) <span class="text-danger">*</span></label>
                            <input type="text" name="radius" class="form-control" value="{{ old('radius') }}">
                            @error('radius')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group d-flex justify-content-end mt-3">
                            <a href="{{ route('radius.list') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button class="btn btn-success">Save</button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
@endsection

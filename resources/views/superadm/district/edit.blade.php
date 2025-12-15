@extends('superadm.layout.master')

@section('content')
<div class="row">
  <div class="col-lg-6 col-md-8 mx-auto">
    <div class="card">
      <div class="card-body">

        <h4>Edit District</h4>

        <form action="{{ route('districts.update', $encodedId) }}" method="POST">
          @csrf

          <input type="hidden" name="id" value="{{ $data->id }}">

          <div class="form-group">
            <label>Select State <span class="text-danger">*</span></label>
            <select name="state_id" class="form-control">
              @foreach($states as $s)
                <option value="{{ $s->id }}" {{ $data->state_id == $s->id ? 'selected' : '' }}>
                    {{ $s->state }}
                </option>
              @endforeach
            </select>
            @error('state_id')
            <span class="text-danger">{{ $message }}</span>
            @enderror
          </div>

          <div class="form-group">
            <label>District Name <span class="text-danger">*</span></label>
            <input type="text" name="district" class="form-control" value="{{ old('district', $data->district) }}">
            @error('district')
            <span class="text-danger">{{ $message }}</span>
            @enderror
          </div>

          <div class="form-group d-flex justify-content-end mt-3">
            <a href="{{ route('districts.list') }}" class="btn btn-secondary mr-2">Cancel</a>
            <button class="btn btn-success">Update</button>
          </div>

        </form>

      </div>
    </div>
  </div>
</div>
@endsection

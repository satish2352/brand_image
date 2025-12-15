@extends('superadm.layout.master')

@section('content')
<div class="row">
  <div class="col-lg-6 col-md-8 mx-auto">
    <div class="card">
      <div class="card-body">

        <h4>Edit City</h4>

        <form action="{{ route('cities.update', $idEncoded) }}" method="POST">
          @csrf

          <input type="hidden" name="id" value="{{ $cities->id }}">

          {{-- State --}}
          <div class="form-group">
            <label>Select State <span class="text-danger">*</span></label>
            <select name="state_id" id="state_select" class="form-control">
              @foreach($states as $s)
              <option value="{{ $s->id }}" {{ $cities->state_id == $s->id ? 'selected' : '' }}>
                {{ $s->state }}
              </option>
              @endforeach
            </select>
            @error('state_id')
              <span class="text-danger">{{ $message }}</span>
            @enderror
          </div>

          {{-- District --}}
          <div class="form-group">
            <label>Select District <span class="text-danger">*</span></label>
            <select name="district_id" id="district_select" class="form-control">
              @foreach($districts as $d)
              <option value="{{ $d->id }}" {{ $cities->district_id == $d->id ? 'selected' : '' }}>
                {{ $d->district }}
              </option>
              @endforeach
            </select>
            @error('district_id')
              <span class="text-danger">{{ $message }}</span>
            @enderror
          </div>

          {{-- City Name --}}
          <div class="form-group">
            <label>City Name <span class="text-danger">*</span></label>
            <input type="text" name="city" class="form-control" value="{{ $cities->city }}">
            @error('city')
              <span class="text-danger">{{ $message }}</span>
            @enderror
          </div>

          <input type="hidden" name="is_active" value="{{ $cities->is_active }}">

          <div class="form-group d-flex justify-content-end mt-3">
            <a href="{{ route('cities.list') }}" class="btn btn-secondary mr-2">Cancel</a>
            <button class="btn btn-success">Update</button>
          </div>

        </form>

      </div>
    </div>
  </div>
</div>

{{-- Reload district list when state changes --}}
<script>
$(function() {

    $('#state_select').change(function() {
        let stateId = $(this).val();

        $('#district_select').html('<option>Loading...</option>');

        $.get("{{ route('districts.byState') }}", { state_id: stateId }, function(res) {
            let html = '<option value="">Select District</option>';
            if(res.status) {
                res.data.forEach(d => {
                    html += `<option value="${d.id}">${d.district}</option>`;
                });
            }
            $('#district_select').html(html);
        });
    });

});
</script>

@endsection

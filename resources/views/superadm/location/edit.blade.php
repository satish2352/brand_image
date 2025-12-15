@extends('superadm.layout.master')

@section('content')
<div class="row">
  <div class="col-lg-6 col-md-8 mx-auto">
    <div class="card">
      <div class="card-body">
        <h4>Edit Location</h4>

        <form action="{{ route('locations.update', $encodedId) }}" method="POST">
          @csrf
          <input type="hidden" name="id" value="{{ $data->id }}">

          {{-- State --}}
          <div class="form-group">
            <label>Select State <span class="text-danger">*</span></label>
            <select name="state_id" id="state_select" class="form-control">
              @foreach($states as $s)
                <option value="{{ $s->id }}" {{ $data->state_id == $s->id ? 'selected' : '' }}>{{ $s->state }}</option>
              @endforeach
            </select>
            @error('state_id') <span class="text-danger">{{ $message }}</span> @enderror
          </div>

          {{-- District --}}
          <div class="form-group">
            <label>Select District <span class="text-danger">*</span></label>
            <select name="district_id" id="district_select" class="form-control">
              @foreach($districts as $d)
                <option value="{{ $d->id }}" {{ $data->district_id == $d->id ? 'selected' : '' }}>{{ $d->district }}</option>
              @endforeach
            </select>
            @error('district_id') <span class="text-danger">{{ $message }}</span> @enderror
          </div>

          {{-- City --}}
          <div class="form-group">
            <label>Select City <span class="text-danger">*</span></label>
            <select name="city_id" id="city_select" class="form-control">
              @foreach($cities as $c)
                <option value="{{ $c->id }}" {{ $data->city_id == $c->id ? 'selected' : '' }}>{{ $c->city }}</option>
              @endforeach
            </select>
            @error('city_id') <span class="text-danger">{{ $message }}</span> @enderror
          </div>

          {{-- Radius --}}
          <div class="form-group">
            <label>Radius (e.g. <code>5km-10km</code>) <span class="text-danger">*</span></label>
            <input type="text" name="radius" class="form-control" value="{{ old('radius', $data->radius) }}">
            @error('radius') <span class="text-danger">{{ $message }}</span> @enderror
          </div>

          {{-- Type --}}
          <div class="form-group">
            <label>Type <span class="text-danger">*</span></label>
            <select name="type_id" class="form-control">
              <option value="">Select Type</option>
              @foreach($types as $t)
                <option value="{{ $t->id }}" {{ $data->type_id == $t->id ? 'selected' : '' }}>{{ $t->type }}</option>
              @endforeach
            </select>
            @error('type_id') <span class="text-danger">{{ $message }}</span> @enderror
          </div>

          <div class="form-group d-flex justify-content-end mt-3">
            <a href="{{ route('locations.list') }}" class="btn btn-secondary mr-2">Cancel</a>
            <button class="btn btn-success">Update</button>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>

<script>
$(function () {

    // On State Change
    $('#state_select').change(function () {
        let stateId = $(this).val();
        $('#district_select').html('<option>Loading...</option>');
        $('#city_select').html('<option value="">Select City</option>');

        if (!stateId) {
            $('#district_select').html('<option value="">Select District</option>');
            return;
        }

        $.get("{{ route('districts.byState') }}", { state_id: stateId }, function (res) {

            let html = '<option value="">Select District</option>';

            if (res.status && res.data.length) {
                res.data.forEach(d => html += `<option value="${d.id}">${d.district}</option>`);
            }

            $('#district_select').html(html);

            // Set selected district from DB
            $('#district_select').val('{{ $data->district_id }}').trigger('change');
        });
    });

    // On District Change
    $('#district_select').change(function () {
        let districtId = $(this).val();
        $('#city_select').html('<option>Loading...</option>');

        if (!districtId) {
            $('#city_select').html('<option value="">Select City</option>');
            return;
        }

        $.get("{{ route('api.cities') }}", { district_id: districtId }, function (res) {

            let html = '<option value="">Select City</option>';

            if (res.length) {
                res.forEach(c => html += `<option value="${c.id}">${c.city}</option>`);
            }

            $('#city_select').html(html);

            // Set selected city from DB
            $('#city_select').val('{{ $data->city_id }}');
        });
    });

    // Trigger state on load
    $('#state_select').trigger('change');

});
</script>
@endsection

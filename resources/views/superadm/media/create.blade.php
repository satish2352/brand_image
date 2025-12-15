@extends('superadm.layout.master')

@section('content')
<div class="row">
  <div class="col-lg-8 mx-auto">
    <div class="card"><div class="card-body">
      <h4>Add Media</h4>

      <form action="{{ route('media.save') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- State --}}
        <div class="form-group">
          <label>State <span class="text-danger">*</span></label>
          <select name="state_id" id="state_select" class="form-control">
            <option value="">Select State</option>
            @foreach($states as $s) <option value="{{ $s->id }}" {{ old('state_id')==$s->id ? 'selected':'' }}>{{ $s->state }}</option> @endforeach
          </select>
          @error('state_id') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        {{-- District --}}
        <div class="form-group">
          <label>District / Area <span class="text-danger">*</span></label>
          <select name="district_id" id="district_select" class="form-control">
            <option value="">Select District</option>
          </select>
          @error('district_id') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        {{-- City --}}
        <div class="form-group">
          <label>City <span class="text-danger">*</span></label>
          <select name="city_id" id="city_select" class="form-control">
            <option value="">Select City</option>
          </select>
          @error('city_id') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        {{-- Location Name --}}
        <div class="form-group">
          <label>Location Name <span class="text-danger">*</span></label>
          <input type="text" name="location_name" class="form-control" value="{{ old('location_name') }}">
          @error('location_name') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        {{-- Type --}}
        <div class="form-group">
          <label>Type <span class="text-danger">*</span></label>
          <select name="type_id" class="form-control">
            <option value="">Select Type</option>
            @foreach($types as $t) <option value="{{ $t->id }}" {{ old('type_id')==$t->id ? 'selected':'' }}>{{ $t->type }}</option> @endforeach
          </select>
          @error('type_id') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        {{-- Radius --}}
        <div class="form-group">
          <label>Dimension / Radius (KM)</label>
          <select name="radius_id" class="form-control">
            <option value="">Select Radius</option>
            @foreach($radii as $r) <option value="{{ $r->id }}" {{ old('radius_id')==$r->id ? 'selected':'' }}>{{ $r->radius }}</option> @endforeach
          </select>
          @error('radius_id') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        {{-- Price --}}
        <div class="form-group">
          <label>Price</label>
          <input type="text" name="price" class="form-control" value="{{ old('price') }}">
          @error('price') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        {{-- Address --}}
        <div class="form-group">
          <label>Address</label>
          <textarea name="address" class="form-control">{{ old('address') }}</textarea>
          @error('address') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        {{-- Description --}}
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control">{{ old('description') }}</textarea>
        </div>

        {{-- Images --}}
        <div class="form-group">
          <label>Images <span class="text-danger">*</span> <small>(min 1, max 2MB each)</small></label>
          <input type="file" name="images[]" id="images_input" class="form-control" multiple accept="image/*">
          <div id="preview_images" class="mt-2 d-flex flex-wrap gap-2"></div>
          @error('images') <span class="text-danger">{{ $message }}</span> @enderror
          @error('images.*') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        {{-- Status --}}
        <div class="form-group">
          <label>Status</label>
          <select name="status" class="form-control">
            <option value="Available" {{ old('status')=='Available' ? 'selected':'' }}>Available</option>
            <option value="Booked" {{ old('status')=='Booked' ? 'selected':'' }}>Booked</option>
            <option value="Under Maintenance" {{ old('status')=='Under Maintenance' ? 'selected':'' }}>Under Maintenance</option>
          </select>
        </div>

        <div class="d-flex justify-content-end mt-3">
          <a href="{{ route('media.list') }}" class="btn btn-secondary mr-2">Cancel</a>
          <button class="btn btn-success">Save</button>
        </div>

      </form>

    </div></div>
  </div>
</div>

<script>
$(function(){
  // dependent dropdowns
  $('#state_select').change(function(){
    let state = $(this).val();
    $('#district_select').html('<option>Loading...</option>');
    $('#city_select').html('<option value="">Select City</option>');
    if(!state){ $('#district_select').html('<option value="">Select District</option>'); return; }
    $.get("{{ route('districts.byState') }}", { state_id: state }, function(res){
      let html = '<option value="">Select District</option>';
      if(res.status && res.data.length) res.data.forEach(d => html += `<option value="${d.id}">${d.district}</option>`);
      $('#district_select').html(html);
    });
  });

  $('#district_select').change(function(){
    let district = $(this).val();
    $('#city_select').html('<option>Loading...</option>');
    if(!district){ $('#city_select').html('<option value="">Select City</option>'); return; }
    $.get("{{ route('cities.byDistrict') }}", { district_id: district }, function(res){
      let html = '<option value="">Select City</option>';
      if(res && res.length) res.forEach(c => html += `<option value="${c.id}">${c.city}</option>`);
      $('#city_select').html(html);
    });
  });

  // preview images + client-side size check
  $('#images_input').on('change', function(){
    $('#preview_images').empty();
    let files = this.files;
    if(files.length === 0) return;
    let invalid = false;
    Array.from(files).forEach(file => {
      if(file.size > 2*1024*1024) {
        invalid = true;
      }
      let reader = new FileReader();
      reader.onload = function(e){
        let img = `<div style="width:120px"><img src="${e.target.result}" style="max-width:100%;border:1px solid #ddd;padding:4px"></div>`;
        $('#preview_images').append(img);
      };
      reader.readAsDataURL(file);
    });
    if(invalid){
      alert('One or more images exceed 2MB. Please choose smaller images.');
      $(this).val('');
      $('#preview_images').empty();
    }
  });

});
</script>

@endsection

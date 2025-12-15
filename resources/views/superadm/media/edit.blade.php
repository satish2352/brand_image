@extends('superadm.layout.master')

@section('content')
<div class="row">
  <div class="col-lg-8 mx-auto">
    <div class="card"><div class="card-body">
      <h4>Edit Media</h4>

      <form action="{{ route('media.update', $idEncoded) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="id" value="{{ $data->id }}">

        {{-- state/district/city - similar to create --}}
        <div class="form-group">
          <label>State <span class="text-danger">*</span></label>
          <select name="state_id" id="state_select" class="form-control">
            <option value="">Select State</option>
            @foreach($states as $s) <option value="{{ $s->id }}" {{ $data->state_id==$s->id ? 'selected':'' }}>{{ $s->state }}</option> @endforeach
          </select>
        </div>

        <div class="form-group">
          <label>District / Area <span class="text-danger">*</span></label>
          <select name="district_id" id="district_select" class="form-control">
            <option value="">Select District</option>
            @foreach($districts as $d) <option value="{{ $d->id }}" {{ $data->district_id==$d->id ? 'selected':'' }}>{{ $d->district }}</option> @endforeach
          </select>
        </div>

        <div class="form-group">
          <label>City <span class="text-danger">*</span></label>
          <select name="city_id" id="city_select" class="form-control">
            <option value="">Select City</option>
            @foreach($cities as $c) <option value="{{ $c->id }}" {{ $data->city_id==$c->id ? 'selected':'' }}>{{ $c->city }}</option> @endforeach
          </select>
        </div>

        <div class="form-group">
          <label>Location Name <span class="text-danger">*</span></label>
          <input type="text" name="location_name" class="form-control" value="{{ old('location_name', $data->location_name) }}">
        </div>

        <div class="form-group">
          <label>Type <span class="text-danger">*</span></label>
          <select name="type_id" class="form-control">
            @foreach($types as $t) <option value="{{ $t->id }}" {{ $data->type_id==$t->id ? 'selected':'' }}>{{ $t->type }}</option> @endforeach
          </select>
        </div>

        <div class="form-group">
          <label>Dimension / Radius (KM)</label>
          <select name="radius_id" class="form-control">
            <option value="">Select Radius</option>
            @foreach($radii as $r) <option value="{{ $r->id }}" {{ $data->radius_id==$r->id ? 'selected':'' }}>{{ $r->radius }}</option> @endforeach
          </select>
        </div>

        <div class="form-group">
          <label>Price</label>
          <input type="text" name="price" class="form-control" value="{{ old('price', $data->price) }}">
        </div>

        <div class="form-group">
          <label>Address</label>
          <textarea name="address" class="form-control">{{ old('address', $data->address) }}</textarea>
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control">{{ old('description', $data->description) }}</textarea>
        </div>

        {{-- existing images --}}
        <div class="form-group">
          <label>Existing Images</label>
          <div id="existing_images" class="d-flex flex-wrap gap-2">
            @foreach($data->images as $img)
              <div data-image-id="{{ $img->id }}" style="position:relative;">
                <img src="{{ asset('storage/'.$img->path) }}" style="width:120px;border:1px solid #ddd;padding:4px">
                <button type="button" class="btn btn-sm btn-danger remove-image" style="position:absolute;top:4px;right:4px;">Remove</button>
              </div>
            @endforeach
          </div>
        </div>

        {{-- Add new images --}}
        <div class="form-group">
          <label>Add Images (optional, max 2MB each)</label>
          <input type="file" name="images[]" id="images_input" class="form-control" multiple accept="image/*">
          <div id="preview_images" class="mt-2 d-flex flex-wrap gap-2"></div>
        </div>

        <div class="form-group">
          <label>Status</label>
          <select name="status" class="form-control">
            <option value="Available" {{ $data->status=='Available' ? 'selected':'' }}>Available</option>
            <option value="Booked" {{ $data->status=='Booked' ? 'selected':'' }}>Booked</option>
            <option value="Under Maintenance" {{ $data->status=='Under Maintenance' ? 'selected':'' }}>Under Maintenance</option>
          </select>
        </div>

        <div class="d-flex justify-content-end mt-3">
          <a href="{{ route('media.list') }}" class="btn btn-secondary mr-2">Cancel</a>
          <button class="btn btn-success">Update</button>
        </div>

      </form>

    </div></div>
  </div>
</div>

<script>
$(function(){
  // dependent dropdown same as create
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

  // preview images client side
  $('#images_input').on('change', function(){
    $('#preview_images').empty();
    let files = this.files;
    if(files.length === 0) return;
    let invalid = false;
    Array.from(files).forEach(file => {
      if(file.size > 2*1024*1024) invalid = true;
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

  // soft-delete image (AJAX)
  $(document).on('click', '.remove-image', function(){
    let container = $(this).closest('div[data-image-id]');
    let imgId = container.data('image-id');
    Swal.fire({
      title:'Remove image?',
      text:'Image will be removed (soft deleted).',
      icon:'warning',
      showCancelButton:true,
      confirmButtonText:'Yes'
    }).then(res=>{
      if(!res.isConfirmed) return;
      $.post("{{ route('media.delete.image') }}", {
        _token: "{{ csrf_token() }}",
        image_id: imgId
      }, function(resp){
        if(resp.status) {
          container.remove();
          Swal.fire('Removed','Image removed','success');
        } else Swal.fire('Error', resp.message, 'error');
      }).fail(function(){ Swal.fire('Error','Something went wrong','error'); });
    });
  });

});
</script>

@endsection

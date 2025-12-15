@extends('superadm.layout.master')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card"><div class="card-body">
      <div class="mb-3 d-flex justify-content-end">
        <a href="{{ route('media.create') }}" class="btn btn-add">Add Media</a>
      </div>

      @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
      @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

      <div class="table-responsive">
        <table class="table table-bordered table-striped datatables">
          <thead>
            <tr>
              <th>Sr.No.</th>
              <th>State</th>
              <th>District</th>
              <th>City</th>
              <th>Location Name</th>
              <th>Type</th>
              <th>Radius</th>
              <th>Price</th>
              <th>Status</th>
              <th>Active</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($media as $k => $m)
            <tr>
              <td>{{ $k+1 }}</td>
              <td>{{ $m->state->state ?? 'N/A' }}</td>
              <td>{{ $m->district->district ?? 'N/A' }}</td>
              <td>{{ $m->city->city ?? 'N/A' }}</td>
              <td>{{ $m->location_name }}</td>
              <td>{{ $m->type->type ?? ($types[$m->type_id] ?? 'N/A') }}</td>
              <td>{{ $m->radius->radius ?? '' }}</td>
              <td>{{ $m->price ? number_format($m->price,2) : '-' }}</td>
              <td>{{ $m->status }}</td>

              <td>
                <label class="switch">
                  <input type="checkbox" class="toggle-status" data-id="{{ base64_encode($m->id) }}" {{ $m->is_active ? 'checked' : '' }}>
                  <span class="slider"></span>
                </label>
              </td>

              <td>
                <a href="{{ route('media.edit', base64_encode($m->id)) }}" class="btn btn-sm btn-primary">Edit</a>

                <form action="{{ route('media.delete') }}" method="POST" class="d-inline-block">
                  @csrf
                  <input type="hidden" name="id" value="{{ base64_encode($m->id) }}">
                  <button type="button" class="btn btn-sm btn-danger delete-btn">Delete</button>
                </form>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

    </div></div>
  </div>
</div>

<script>
$(document).on('change', '.toggle-status', function() {
    let cb = $(this);
    let id = cb.data('id');
    let is_active = cb.is(':checked') ? 1 : 0;
    Swal.fire({
        title: 'Are you sure?',
        text: 'Change active status?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes'
    }).then(res => {
        if(res.isConfirmed) {
            $.post("{{ route('media.updatestatus') }}", {
                _token: "{{ csrf_token() }}",
                id: id,
                is_active: is_active
            }, function(resp){
                if(resp.status) Swal.fire('Success', resp.message, 'success');
                else { Swal.fire('Error', resp.message, 'error'); cb.prop('checked', !is_active); }
            }).fail(function(){ Swal.fire('Error','Something went wrong','error'); cb.prop('checked', !is_active); });
        } else {
            cb.prop('checked', !is_active);
        }
    });
});

$(document).on('click', '.delete-btn', function(){
    let form = $(this).closest('form');
    Swal.fire({
        title: 'Confirm',
        text: 'Delete this media?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete'
    }).then(res=>{
        if(res.isConfirmed) form.submit();
    });
});
</script>

@endsection

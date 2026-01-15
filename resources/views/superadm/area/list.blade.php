@extends('superadm.layout.master')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                {{-- HEADER --}}
                <div class="d-flex justify-content-between mb-3">
                    <h4>Area List</h4>
                    <a href="{{ route('area.create') }}" class="btn btn-add">
                        Add Area
                    </a>
                </div>

                {{-- FLASH MESSAGES --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- TABLE --}}
                <div class="table-responsive">
                    <table class="table table-bordered table-striped datatables">
                        <thead class="table-light">
                            <tr>
                                <th>Sr.No.</th>
                                <th>State</th>
                                <th>District</th>
                                <th>City</th>
                                <th>Area Name</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th>Status</th>
                                 <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($areas as $key => $area)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $area->state_name }}</td>
                                    <td>{{ $area->district_name }}</td>
                                    <td>{{ $area->city_name }}</td>
                                    <td>{{ $area->area_name }}</td>
                                     <td>{{ $area->latitude }}</td>
                                      <td>{{ $area->longitude }}</td>
                                     <td>
                                            <form action="{{ route('area.updatestatus') }}" method="POST"
                                                class="d-inline-block delete-form">
                                                @csrf
                                                <label class="switch">
                                                    <input type="checkbox" class="toggle-status"
                                                        data-id="{{ base64_encode($area->id) }}"
                                                        {{ $area->is_active == '1' ? 'checked' : '' }}>
                                                    <span class="slider"></span>
                                                </label>

                                                <input type="hidden" name="id"
                                                    value="{{ base64_encode($area->id) }}">
                                            </form>
                                        </td>
                                        <td class="d-flex">
    <a href="{{ route('area.edit', base64_encode($area->id)) }}"
       class="btn btn-sm btn-primary mr-2">
        <i class="mdi mdi-square-edit-outline"></i>
    </a>

    <button type="button"
            class="btn btn-sm btn-danger delete-btn"
            data-id="{{ base64_encode($area->id) }}">
        <i class="mdi mdi-trash-can-outline"></i>
    </button>
</td>

                                          {{-- <td class="d-flex">
                                            <a href="{{ route('area.edit', base64_encode($area->id)) }}" 
                                            class="btn btn-sm btn-primary mr-2" 
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="top" 
                                            title="Edit">
                                            <i class="mdi mdi-square-edit-outline icon-medium"></i>
                                            </a>
                                            <form action="{{ route('area.delete') }}" method="POST" class="d-inline-block delete-form">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ base64_encode($area->id) }}">
                                                <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        title="Delete">
                                                    <i class="mdi mdi-trash-can-outline icon-medium"></i>
                                                </button>
                                            </form>
                                        </td> --}}
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">
                                        No areas found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
@section('scripts')
<script>
/* ================= STATUS TOGGLE ================= */
$('.toggle-status').change(function () {

    let id = $(this).data('id');

    $.post("{{ route('area.updatestatus') }}", {
        _token: "{{ csrf_token() }}",
        id: id
    }, function (response) {
        toastr.success(response.message);
    }).fail(function () {
        toastr.error('Failed to update status');
    });

});

/* ================= DELETE ================= */
$('.delete-btn').click(function () {

    let id = $(this).data('id');

    if (!confirm('Are you sure you want to delete this area?')) return;

    $.post("{{ route('area.delete') }}", {
        _token: "{{ csrf_token() }}",
        id: id
    }, function (response) {
        toastr.success(response.message);
        location.reload();
    }).fail(function () {
        toastr.error('Delete failed');
    });

});
</script>
@endsection

@endsection

@extends('superadm.layout.master')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                {{-- HEADER --}}
                <div class="d-flex justify-content-between mb-3">
                    <h4>Media List</h4>
                    <a href="{{ route('media.create') }}" class="btn btn-add">
                        Add Media
                    </a>
                </div>

                {{-- FLASH --}}
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                {{-- TABLE --}}
                <div class="table-responsive">
                    <table class="table table-bordered table-striped datatables">
                        <thead class="table-light">
                            <tr>
                                <th>Sr.No</th>
                                {{-- <th>Media Code</th> --}}
                                <th>Media Title</th>
                                <th>Category</th>
                                <th>State</th>
                                <th>District</th>
                                <th>City</th>
                                <th>Area</th>
                                <th>Price</th>
                                 {{-- <th>Vendor Name</th> --}}
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($mediaList as $key => $media)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                  {{-- <td>{{ $media->media_code ?? '-' }}</td> --}}
                                    <td>{{ $media->media_title ?? '-' }}</td>
                                    <td>{{ $media->category_name ?? '-' }}</td>
                                    <td>{{ $media->state_name ?? '-' }}</td>
                                    <td>{{ $media->district_name ?? '-' }}</td>
                                    <td>{{ $media->city_name ?? '-' }}</td>
                                    <td>{{ $media->area_name ?? '-' }}</td>
                                    <td>
                                        ₹ {{ $media->price !== null ? number_format($media->price, 2) : '-' }}
                                    </td>
                                    {{-- <td>{{ $media->vendor_name ?? '-' }}</td> --}}
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox"
                                                class="toggle-status"
                                                data-id="{{ base64_encode($media->id) }}"
                                                {{ $media->is_active ? 'checked' : '' }}>
                                            <span class="slider"></span>
                                        </label>
                                    </td>
                                    <td class="d-flex">

                                        <a href="{{ route('media.viewdetails', base64_encode($media->id)) }}"
                                        class="btn btn-info btn-sm mr-1"
                                        title="View Details">
                                            <i class="fa fa-eye"></i>
                                        </a>

                                        <a href="{{ route('media.view', base64_encode($media->id)) }}"
                                        class="btn btn-info btn-sm mr-1"
                                        title="View Images">
                                            <i class="fa fa-eye"></i>
                                        </a>


                                        <a href="{{ route('media.edit', base64_encode($media->id)) }}"
                                        class="btn btn-sm btn-primary mr-1 ">
                                            <i class="mdi mdi-square-edit-outline"></i>
                                        </a>

                                        <button type="button"
                                                class="btn btn-sm btn-danger delete-btn"
                                                data-id="{{ base64_encode($media->id) }}">
                                            <i class="mdi mdi-trash-can-outline"></i>
                                        </button>
                                    </td>


                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center">
                                        No media found
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

    $.post("{{ route('media.status') }}", {
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

    if (!confirm('Are you sure you want to delete this media?')) return;

    $.post("{{ route('media.delete') }}", {
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

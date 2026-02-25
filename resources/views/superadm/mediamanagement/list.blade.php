@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    {{-- FLASH --}}
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    {{-- TABLE --}}
                    <div class="table">
                        <form method="GET" class="mb-3">
                            <div class="row">

                                <div class="col-md-3">
                                    <label><b>Vendor</b></label>
                                    <select name="vendor_id" class="form-control">
                                        <option value="">Select Vendor</option>
                                        @foreach ($vendors as $v)
                                            <option value="{{ $v->id }}"
                                                {{ request('vendor_id') == $v->id ? 'selected' : '' }}>
                                                {{ $v->vendor_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label><b>Category</b></label>
                                    <select name="category_id" class="form-control">
                                        <option value="">Select Category</option>
                                        @foreach ($categories as $c)
                                            <option value="{{ $c->id }}"
                                                {{ request('category_id') == $c->id ? 'selected' : '' }}>
                                                {{ $c->category_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label><b>District</b></label>
                                    <select name="district_id" id="district_id" class="form-control">
                                        <option value="">Select District</option>
                                        @foreach ($districts as $d)
                                            <option value="{{ $d->id }}"
                                                {{ request('district_id') == $d->id ? 'selected' : '' }}>
                                                {{ $d->district_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label><b>Town</b></label>
                                    <select name="city_id" id="city_id" class="form-control">
                                        <option value="">Select Town</option>
                                    </select>
                                </div>
                                </div>
                                <div class="row mt-3">
                                <div class="col-md-3">
                                    <label><b>Year</b></label>
                                    <select name="year" class="form-control">
                                        <option value="">Select Year</option>
                                        @foreach ($years as $y)
                                            <option value="{{ $y }}"
                                                {{ request('year') == $y ? 'selected' : '' }}>
                                                {{ $y }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label><b>Month</b></label>
                                    <select name="month" class="form-control">
                                        <option value="">Select Month</option>
                                        @foreach ($months as $num => $name)
                                            <option value="{{ $num }}"
                                                {{ request('month') == $num ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label><b>From</b></label>
                                    <input type="date" name="from_date" class="form-control"
                                        value="{{ request('from_date') }}">
                                </div>

                                <div class="col-md-3">
                                    <label><b>To</b></label>
                                    <input type="date" name="to_date" class="form-control"
                                        value="{{ request('to_date') }}">
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button class="btn btn-success m-2">Filter</button>
                                    <a href="{{ route('media.list') }}" class="btn btn-secondary m-2">Reset</a>
                                </div>
                            </div>
                        </form>


                        {{-- HEADER --}}
                        <div class="d-flex justify-content-between mb-3">
                            <h4>Media List</h4>
                            <a href="{{ route('media.create') }}" class="btn btn-add">
                                Add Media
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">

                                {{-- <table class="table table-bordered table-striped datatables"> --}}
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
                                        <th>Vendor Name</th>
                                        <th>Media Code</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($mediaList as $key => $media)
                                        <tr>
                                            {{-- <td>{{ $key + 1 }}</td> --}}
                                            <td>{{ $mediaList->firstItem() + $key }}</td>
                                            <td>{{ $media->media_title ?? '-' }}</td>
                                            <td>{{ $media->category_name ?? '-' }}</td>
                                            <td>{{ $media->state_name ?? '-' }}</td>
                                            <td>{{ $media->district_name ?? '-' }}</td>
                                            <td>{{ $media->city_name ?? '-' }}</td>
                                            <td>{{ $media->area_name ?? '-' }}</td>
                                            <td>
                                                ₹ {{ $media->price !== null ? number_format($media->price, 2) : '-' }}
                                            </td>
                                            <td>{{ $media->vendor_name ?? '-' }}</td>
                                            <td>{{ $media->media_code ?? '-' }}</td>
                                            <td>
                                                <label class="switch">
                                                    <input type="checkbox" class="toggle-status"
                                                        data-id="{{ base64_encode($media->id) }}"
                                                        {{ $media->is_active ? 'checked' : '' }}>
                                                    <span class="slider"></span>
                                                </label>
                                            </td>
                                            {{-- <td class="d-flex">

                                            <a href="{{ route('media.viewdetails', base64_encode($media->id)) }}"
                                                class="btn btn-info btn-sm mr-1" title="View Details">
                                                <i class="fa fa-eye"></i>
                                            </a>

                                            <a href="{{ route('media.view', base64_encode($media->id)) }}"
                                                class="btn btn-warning btn-sm mr-1" title="View Images">
                                                <i class="fa fa-image"></i>
                                            </a>


                                            <a href="{{ route('media.edit', base64_encode($media->id)) }}"
                                                class="btn btn-sm btn-primary mr-1 ">
                                                <i class="mdi mdi-square-edit-outline"></i>
                                            </a>

                                            <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                data-id="{{ base64_encode($media->id) }}">
                                                <i class="mdi mdi-trash-can-outline"></i>
                                            </button>
                                        </td> --}}
                                            <td class="d-flex">

                                                {{-- View Details --}}
                                                <a href="{{ route('media.viewdetails', base64_encode($media->id)) }}"
                                                    class="btn btn-success btn-sm m-1" title="View Details">
                                                    <i class="fa fa-eye"></i>
                                                </a>

                                                {{-- View Images --}}
                                                <a href="{{ route('media.view', base64_encode($media->id)) }}"
                                                    class="btn btn-secondary btn-sm m-1" title="Add Images">
                                                    <i class="fa fa-image"></i>
                                                </a>

                                                {{-- Edit --}}
                                                <a href="{{ route('media.edit', base64_encode($media->id)) }}"
                                                    class="btn btn-primary btn-sm m-1" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>

                                                {{-- Delete --}}
                                                <button type="button" class="btn btn-danger btn-sm delete-btn m-1"
                                                    data-id="{{ base64_encode($media->id) }}" title="Delete">
                                                    <i class="fa fa-trash"></i>
                                                </button>

                                            </td>


                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="12" class="text-center">
                                                No media found
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-between  mt-3">

                                {{-- LEFT : COUNT --}}
                                <div class="text-muted d-flex align-items-start">
                                    Showing {{ $mediaList->firstItem() }} to {{ $mediaList->lastItem() }}
                                    of {{ $mediaList->total() }} rows
                                </div>

                                {{-- RIGHT : PAGINATION --}}
                                <div>
                                    {{ $mediaList->appends(request()->query())->links() }}
                                </div>

                            </div>

                            {{-- <div class="text-muted">
                                Showing {{ $mediaList->firstItem() }} to {{ $mediaList->lastItem() }}
                                of {{ $mediaList->total() }} rows
                            </div>

                            {{ $mediaList->appends(request()->query())->links() }} --}}
                        </div>

                    </div>
                </div>
            </div>
        </div>
        @section('scripts')
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

            {{-- <script>
                /* ================= STATUS TOGGLE ================= */
                $(document).on('change', '.toggle-status', function() {

                    let id = $(this).data('id');

                    $.post("{{ route('media.status') }}", {
                        _token: "{{ csrf_token() }}",
                        id: id
                    }, function(response) {
                        toastr.success(response.message);
                    }).fail(function() {
                        toastr.error('Failed to update status');
                    });

                });

                /* ================= DELETE (FIXED) ================= */
                $(document).on('click', '.delete-btn', function() {

                    let id = $(this).data('id');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: "This media will be permanently deleted.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, delete it',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {

                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: "{{ route('media.delete') }}",
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                id: id
                            },
                            success: function(response) {

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: 'Media deleted successfully.',
                                    timer: 1500,
                                    showConfirmButton: false
                                });

                                // Remove row without reload
                                setTimeout(() => {
                                    location.reload();
                                }, 1200);
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Failed!',
                                    text: 'Delete failed. Please try again.'
                                });
                            }
                        });

                    });

                });
            </script> --}}
            <script>

$(document).ready(function () {

    // ================= LOAD CITIES =================
 $(document).ready(function () {

    function loadCities(districtId, selectedCity = '') {

        if (districtId === '') {
            $('#city_id').html('<option value="">Select Town</option>');
            return;
        }

        $('#city_id').html('<option value="">Loading...</option>');

        $.ajax({
            url: "{{ url('get-cities') }}/" + districtId,
            type: "GET",
            success: function (response) {

                let options = '<option value="">Select Town</option>';

                $.each(response, function (key, city) {

                    let selected = (city.id == selectedCity)
                        ? 'selected'
                        : '';

                    options += `
                        <option value="${city.id}" ${selected}>
                            ${city.city_name}
                        </option>
                    `;
                });

                $('#city_id').html(options);
            }
        });
    }

    // 🔥 ON DISTRICT CHANGE
    $('#district_id').on('change', function () {
        loadCities($(this).val());
    });

    // 🔥 IMPORTANT — PAGE RELOAD CASE
    let districtId = "{{ request('district_id') }}";
    let cityId     = "{{ request('city_id') }}";

    if (districtId !== '') {
        loadCities(districtId, cityId);
    }

});

});


// ================= STATUS TOGGLE =================
$(document).on('change', '.toggle-status', function() {

    let id = $(this).data('id');

    $.post("{{ route('media.status') }}", {
        _token: "{{ csrf_token() }}",
        id: id
    }, function(response) {
        toastr.success(response.message);
    }).fail(function() {
        toastr.error('Failed to update status');
    });

});


// ================= DELETE =================
$(document).on('click', '.delete-btn', function() {

    let id = $(this).data('id');

    Swal.fire({
        title: 'Are you sure?',
        text: "This media will be permanently deleted.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it'
    }).then((result) => {

        if (!result.isConfirmed) return;

        $.ajax({
            url: "{{ route('media.delete') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                id: id
            },
            success: function() {
                location.reload();
            }
        });

    });

});

</script>
        @endsection
    @endsection

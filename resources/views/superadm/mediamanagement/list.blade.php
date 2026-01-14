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
                        <form method="GET" class="mb-3">
                            <div class="row">

                                <div class="col-md-3">
                                    <label>Vendor</label>
                                    <select name="vendor_id" class="form-control">
                                        <option value="">All</option>
                                        @foreach ($vendors as $v)
                                            <option value="{{ $v->id }}"
                                                {{ request('vendor_id') == $v->id ? 'selected' : '' }}>
                                                {{ $v->vendor_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label>Category</label>
                                    <select name="category_id" class="form-control">
                                        <option value="">All</option>
                                        @foreach ($categories as $c)
                                            <option value="{{ $c->id }}"
                                                {{ request('category_id') == $c->id ? 'selected' : '' }}>
                                                {{ $c->category_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label>Year</label>
                                    <select name="year" class="form-control">
                                        <option value="">All</option>
                                        @foreach ($years as $y)
                                            <option value="{{ $y }}"
                                                {{ request('year') == $y ? 'selected' : '' }}>
                                                {{ $y }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label>Month</label>
                                    <select name="month" class="form-control">
                                        <option value="">All</option>
                                        @foreach ($months as $num => $name)
                                            <option value="{{ $num }}"
                                                {{ request('month') == $num ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label>From</label>
                                    <input type="date" name="from_date" class="form-control"
                                        value="{{ request('from_date') }}">
                                </div>

                                <div class="col-md-3">
                                    <label>To</label>
                                    <input type="date" name="to_date" class="form-control"
                                        value="{{ request('to_date') }}">
                                </div>
                                <div class="col-md-6 d-flex align-items-end justify-content-center">
                                    <button class="btn btn-success m-2">Filter</button>
                                    <a href="{{ route('media.list') }}" class="btn btn-secondary m-2">Reset</a>
                                </div>
                            </div>
                        </form>

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
                                    <th>Vendor Name</th>
                                    <th>Media Code</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($mediaList as $key => $media)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>

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
                        {{ $mediaList->appends(request()->query())->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
    @section('scripts')
        <script>
            /* ================= STATUS TOGGLE ================= */
            $('.toggle-status').change(function() {

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

            /* ================= DELETE ================= */
            $('.delete-btn').click(function() {

                let id = $(this).data('id');

                if (!confirm('Are you sure you want to delete this media?')) return;

                $.post("{{ route('media.delete') }}", {
                    _token: "{{ csrf_token() }}",
                    id: id
                }, function(response) {
                    toastr.success(response.message);
                    location.reload();
                }).fail(function() {
                    toastr.error('Delete failed');
                });

            });
        </script>
    @endsection
@endsection

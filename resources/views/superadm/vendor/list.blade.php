@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class="d-flex justify-content-between mb-3">
                        <h4>Vendor List</h4>
                        <a href="{{ route('vendor.create') }}" class="btn btn-success">Add Vendor</a>
                    </div>
                    {{-- <a href="{{ route('vendor.export.excel') }}" class="btn btn-outline-success mt-3">
                    Export Excel
                </a> --}}

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped datatable">
                            <thead>
                                <tr>
                                    <th>Sr. No</th>
                                    <th>Vendor Code</th>
                                    <th>Vendor Name</th>
                                    <th>Mobile</th>
                                    <th>Email</th>
                                    <th>City</th>
                                    <th>Status</th>
                                    <th width="120">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($vendors as $key => $vendor)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $vendor->vendor_code }}</td>
                                        <td>{{ $vendor->vendor_name }}</td>
                                        <td>{{ $vendor->mobile }}</td>
                                        <td>{{ $vendor->email }}</td>
                                        <td>{{ $vendor->city_name }}</td>
                                        <td>
                                            <label class="switch">
                                                <input type="checkbox" class="toggle-status"
                                                    data-id="{{ base64_encode($vendor->id) }}"
                                                    {{ $vendor->is_active ? 'checked' : '' }}>
                                                <span class="slider"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <a href="{{ route('vendor.edit', base64_encode($vendor->id)) }}"
                                                class="btn btn-sm btn-primary">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            <button class="btn btn-sm btn-danger delete-btn"
                                                data-id="{{ base64_encode($vendor->id) }}">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@section('scripts')
    <script>
        $(document).ready(function() {

            $('.datatable').DataTable();

            /* ================= STATUS TOGGLE WITH SWEET ALERT ================= */
            $('.toggle-status').on('change', function(e) {

                e.preventDefault();

                let checkbox = $(this);
                let id = checkbox.data('id');
                let isChecked = checkbox.is(':checked');

                // checkbox तात्पुरता revert कर
                checkbox.prop('checked', !isChecked);

                let actionText = isChecked ? 'activate' : 'deactivate';

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You want to ' + actionText + ' this vendor',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, ' + actionText + ' it!'
                }).then((result) => {

                    if (result.isConfirmed) {

                        $.post("{{ route('vendor.updatestatus') }}", {
                            _token: "{{ csrf_token() }}",
                            id: id
                        }, function(res) {

                            // success झाल्यावर checkbox set कर
                            checkbox.prop('checked', isChecked);

                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: isChecked ?
                                    'Vendor activated successfully' :
                                    'Vendor deactivated successfully',
                                timer: 1500,
                                showConfirmButton: false
                            });

                        }).fail(function() {

                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Status update failed'
                            });
                        });
                    }
                });
            });

            /* ================= DELETE WITH SWEET ALERT ================= */
            $('.delete-btn').click(function() {

                let id = $(this).data('id');

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This vendor will be deleted',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {

                    if (result.isConfirmed) {

                        $.post("{{ route('vendor.delete') }}", {
                            _token: "{{ csrf_token() }}",
                            id: id
                        }, function() {

                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted',
                                text: 'Vendor deleted successfully',
                                timer: 1500,
                                showConfirmButton: false
                            });

                            setTimeout(() => location.reload(), 1500);
                        });
                    }
                });
            });

        });
    </script>
@endsection

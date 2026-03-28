@extends('superadm.layout.master')

@section('content')
    <style>
        table {
            font-size: 0.875rem !important;
        }
    </style>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class="d-flex justify-content-between mb-3">
                        <h4>Area Type List</h4>
                        <a href="{{ route('areatype.create') }}" class="btn btn-success">
                            Add Area Type
                        </a>
                    </div>

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped datatables">
                            <thead>
                                <tr>
                                    <th>Sr. No</th>
                                    <th>Area Type Name</th>
                                    <th>Status</th>
                                    <th width="120">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($areatype as $key => $item)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $item->areatype_name }}</td>
                                        <td>
                                            <label class="switch">
                                                <input type="checkbox" class="toggle-status"
                                                    data-id="{{ base64_encode($item->id) }}"
                                                    {{ $item->is_active ? 'checked' : '' }}>
                                                <span class="slider"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <a href="{{ route('areatype.edit', base64_encode($item->id)) }}"
                                                class="btn btn-sm btn-primary">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            <button class="btn btn-sm btn-danger delete-btn"
                                                data-id="{{ base64_encode($item->id) }}">
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

            /* STATUS */
            $('.toggle-status').change(function(e) {

                e.preventDefault();

                let checkbox = $(this);
                let id = checkbox.data('id');
                let checked = checkbox.is(':checked');

                checkbox.prop('checked', !checked);

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You want to change status?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes'
                }).then((result) => {

                    if (result.isConfirmed) {
                        $.post("{{ route('areatype.updatestatus') }}", {
                            _token: "{{ csrf_token() }}",
                            id: id
                        }, function() {

                            checkbox.prop('checked', checked);

                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: checked ?
                                    'areatype activated successfully' :
                                    'areatype deactivated successfully',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        });
                    }
                });
            });

            /* DELETE */
            $('.delete-btn').click(function() {

                let id = $(this).data('id');

                Swal.fire({
                    title: 'Are You Sure?',
                    text: 'Do you really want to delete this record?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete'
                }).then((result) => {

                    if (result.isConfirmed) {
                        $.post("{{ route('areatype.delete') }}", {
                            _token: "{{ csrf_token() }}",
                            id: id
                        }, function() {

                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted',
                                text: 'Area Type deleted successfully',
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

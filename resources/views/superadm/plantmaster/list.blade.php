@extends('superadm.layout.master')

@section('content')
<style>
    .dropdown-item.active, .dropdown-item-custom:active {
        background-color: #952419;
    }
</style>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class="mb-3 d-flex justify-content-end">
                        <div class="btn-group mr-2">
                            <button type="button" class="btn btn-warning btn-add dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Export
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item dropdown-item-custom export-btn" data-type="excel" href="#">Excel</a>
                                <a class="dropdown-item dropdown-item-custom export-btn" data-type="pdf" href="#">PDF</a>
                            </div>
                        </div>

                        <a href="{{ route('plantmaster.create') }}" class="btn btn-warning btn-add">Add Plant Details</a>
                        {{-- <a id="exportExcelBtn" class="btn btn-warning btn-add" style="cursor: pointer;">Export Excel</a> --}}
                    </div>

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" id="success-alert">{{ session('success') }}</div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" id="error-alert" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif


                    <div class="table-responsive">
                        <table class="table table-bordered table-striped datatables">
                            <thead>
                                <tr>
                                    <th>Sr.No.</th>
                                    <th>Plant Code</th>
                                    <th>Plant Name</th>
                                    <th>Plant Address</th>
                                    <th>Plant City</th>
                                    <th>Plant Short Name</th>
                                    <th>Created By</th>
                                    <th>Created Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data_all as $key => $data)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $data->plant_code }}</td>
                                        <td>{{ $data->plant_name }}</td>
                                        <td>{{ $data->address ?? '-' }}</td>
                                        <td>{{ $data->city }}</td>
                                        <td>{{ $data->plant_short_name ?? '-' }}</td>
                                        <td>{{ $data->created_by ?? '-' }}</td>
                                        <td>
                                            {{ $data->created_at 
                                                ? $data->created_at->setTimezone('Asia/Kolkata')->format('d-m-Y h:i:s A') 
                                                : '-' }}
                                        </td>
                                        <td>
                                            <form action="{{ route('plantmaster.updatestatus') }}" method="POST"
                                                class="d-inline-block delete-form">
                                                @csrf
                                                <label class="switch">
                                                    <input type="checkbox" class="toggle-status"
                                                        data-id="{{ base64_encode($data->id) }}"
                                                        {{ $data->is_active == '1' ? 'checked' : '' }}>
                                                    <span class="slider"></span>
                                                </label>


                                                <input type="hidden" name="id" value="{{ base64_encode($data->id) }}">
                                            </form>
                                        </td>
                                          <td class="d-flex">
                                            <a href="{{ route('plantmaster.edit', base64_encode($data->id)) }}" 
                                            class="btn btn-sm btn-primary mr-2" 
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="top" 
                                            title="Edit">
                                            <i class="mdi mdi-square-edit-outline icon-medium"></i>
                                            </a>
                                            <form action="{{ route('plantmaster.delete') }}" method="POST" class="d-inline-block delete-form">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ base64_encode($data->id) }}">
                                                <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        title="Delete">
                                                    <i class="mdi mdi-trash-can-outline icon-medium"></i>
                                                </button>
                                            </form>
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
    <script>
        $(document).on("change", ".toggle-status", function(e) {
            e.preventDefault();

            let checkbox = $(this);
            let form = checkbox.closest("form");
            let id = checkbox.data("id");
            let is_active = checkbox.is(":checked") ? 1 : 0;

            // Show SweetAlert confirmation
            Swal.fire({
                title: "Are you sure?",
                text: "Do you want to change the status?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#28a745",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, change it!",
                cancelButtonText: "No, cancel"
            }).then((result) => {
                        if(result.isConfirmed){
                            $.ajax({
                                url: "{{ route('plantmaster.updatestatus') }}",
                                type: "POST",
                                data: {_token: "{{ csrf_token() }}", id: id, is_active: is_active},
                                success: function(res){
                                    if(res.status){
                                        Swal.fire('Success!', res.message, 'success');
                                    } else {
                                        Swal.fire('Error!', res.message, 'error');
                                        checkbox.prop("checked", !is_active); // revert
                                    }
                                },
                                error: function(xhr){
                                    Swal.fire('Error!', xhr.responseJSON?.message || 'Something went wrong', 'error');
                                    checkbox.prop("checked", !is_active); // revert
                                }
                            });
                        } else {
                            checkbox.prop("checked", !checkbox.is(":checked")); // revert
                        }
            });
        });
    </script>

<script>
$(document).ready(function() {
    $('.export-btn').click(function(e){
        e.preventDefault();

        const type = $(this).data('type'); // excel or pdf
        const rows = $("table.datatables tbody tr");
        let hasData = false;

        rows.each(function() {
            const cellText = $(this).find("td:first").text().trim().toLowerCase();
            if (cellText !== "no data available" && cellText !== "no matching records found" && cellText !== "") {
                hasData = true;
                return false;
            }
        });

        if (!hasData) {
            Swal.fire({
                icon: 'warning',
                title: 'No data available!',
                text: 'There is no data in the table to export.',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }

        let searchValue = $('.dataTables_filter input').val();
        let url = "{{ route('plantmaster.export') }}" + '?type=' + type;
        if (searchValue) {
            url += '&search=' + encodeURIComponent(searchValue);
        }

        window.location.href = url;
    });
});
</script>

    <script>
        // Delegated event for delete buttons
        $(document).on("click", ".delete-btn", function (e) {
            e.preventDefault();

            let button = $(this);
            let form = button.closest("form");

            Swal.fire({
                title: "Are you sure?",
                text: "This record will be deleted!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#28a745",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "No, cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

    </script>


@endsection

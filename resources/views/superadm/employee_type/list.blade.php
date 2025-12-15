@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class="mb-3 d-flex justify-content-end">
                        <a href="{{ route('employee-types.create') }}" class="btn btn-warning btn-add">Add Employee Type</a>
                    </div>

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" id="success-alert">{{ session('success') }}</div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" id="error-alert">{{ session('error') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped datatables">
                            <thead>
                                <tr>
                                    <th>Sr.No.</th>
                                    <th>Employee Type Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($employeeTypes as $key => $type)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $type->type_name }}</td>
                                        <td>{{ $type->description }}</td>
                                        <td>
                                            <form action="{{ route('employee-types.updatestatus') }}" method="POST" class="d-inline-block">
                                                @csrf
                                                <label class="switch">
                                                    <input type="checkbox" class="toggle-status"
                                                        data-id="{{ base64_encode($type->id) }}"
                                                        {{ $type->is_active == '1' ? 'checked' : '' }}>
                                                    <span class="slider"></span>
                                                </label>
                                                <input type="hidden" name="id" value="{{ base64_encode($type->id) }}">
                                            </form>
                                        </td>
                                        <td>
                                            <a href="{{ route('employee-types.edit', base64_encode($type->id)) }}" 
                                               class="btn btn-sm btn-primary" 
                                               data-bs-toggle="tooltip" 
                                               data-bs-placement="top" 
                                               title="Edit">
                                               <i class="mdi mdi-square-edit-outline icon-medium"></i>
                                            </a>
                                            <form action="{{ route('employee-types.delete') }}" method="POST" class="d-inline-block delete-form">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ base64_encode($type->id) }}">
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
        let id = form.find("input[name='id']").val();
        let is_active = checkbox.is(":checked") ? 1 : 0;

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
                // AJAX request to update status
                $.ajax({
                    url: "{{ route('employee-types.updatestatus') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: id,
                        is_active: is_active
                    },
                    success: function(res) {
                        if(res.status) {
                            Swal.fire("Success!", res.message, "success");
                        } else {
                            Swal.fire("Error!", res.message, "error");
                            // revert checkbox state
                            checkbox.prop("checked", !checkbox.is(":checked"));
                        }
                    },
                    error: function(xhr) {
                        Swal.fire("Error!", xhr.responseJSON?.message || "Something went wrong", "error");
                        checkbox.prop("checked", !checkbox.is(":checked"));
                    }
                });
            } else {
                // revert checkbox if canceled
                checkbox.prop("checked", !checkbox.is(":checked"));
            }
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

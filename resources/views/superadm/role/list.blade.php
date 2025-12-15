@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class="mb-3 d-flex justify-content-end">
                        <a href="{{ route('roles.create') }}" class="btn btn-add">Add Role</a>
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
                                    <th>Role Name</th>
                                    <th>Short Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roles as $key => $role)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $role->role }}</td>
                                    <td>{{ $role->short_description }}</td>

                                    <td>
                                        @if ($role->id != 0)
                                            <form action="{{ route('roles.updatestatus') }}" method="POST"
                                                class="d-inline-block delete-form">
                                                @csrf
                                                <label class="switch">
                                                    <input type="checkbox" class="toggle-status"
                                                        data-id="{{ base64_encode($role->id) }}"
                                                        {{ $role->is_active == '1' ? 'checked' : '' }}>
                                                    <span class="slider"></span>
                                                </label>
                                                <input type="hidden" name="id" value="{{ base64_encode($role->id) }}">
                                            </form>
                                        @else
                                            <span>Active</span> {{-- or whatever you want --}}
                                        @endif
                                    </td>

                                    <td>
                                        @if ($role->id != 0)
                                            <a href="{{ route('roles.edit', base64_encode($role->id)) }}" 
                                                class="btn btn-sm btn-primary" 
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top" 
                                                title="Edit">
                                                <i class="mdi mdi-square-edit-outline icon-medium"></i>
                                            </a>

                                            <form action="{{ route('roles.delete') }}" method="POST" class="d-inline-block delete-form">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ base64_encode($role->id) }}">
                                                <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        title="Delete">
                                                    <i class="mdi mdi-trash-can-outline icon-medium"></i>
                                                </button>
                                            </form>
                                        @else
                                            {{-- <button class="btn btn-sm btn-secondary" disabled>Protected</button> --}}
                                                        {{-- Disabled Edit Icon --}}
                                            <button class="btn btn-sm btn-secondary" disabled style="pointer-events: none; opacity: 0.5;">
                                                <i class="mdi mdi-square-edit-outline icon-medium"></i>
                                            </button>

                                            {{-- Disabled Delete Icon --}}
                                            <button class="btn btn-sm btn-secondary" disabled style="pointer-events: none; opacity: 0.5;">
                                                <i class="mdi mdi-trash-can-outline icon-medium"></i>
                                            </button>
                                        @endif
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
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('roles.updatestatus') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: id,
                            is_active: is_active
                        },
                        success: function(response) {
                            if (response.status) {
                                Swal.fire('Success!', response.message, 'success');
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                                checkbox.prop("checked", !is_active); // revert
                            }
                        },
                        error: function(xhr) {
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

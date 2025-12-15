@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class="mb-3 d-flex justify-content-end">
                        <a href="{{ route('states.create') }}" class="btn btn-add">Add State</a>
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
                                    <th>State Name</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($states as $key => $state)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $state->state }}</td>

                                    <td>
                                        {{-- Status Toggle --}}
                                        <form action="{{ route('states.updatestatus') }}" method="POST" class="d-inline-block">
                                            @csrf
                                            <label class="switch">
                                                <input type="checkbox" class="toggle-status"
                                                    data-id="{{ base64_encode($state->id) }}"
                                                    {{ $state->is_active == 1 ? 'checked' : '' }}>
                                                <span class="slider"></span>
                                            </label>
                                        </form>
                                    </td>

                                    <td>
                                        <a href="{{ route('states.edit', base64_encode($state->id)) }}"
                                            class="btn btn-sm btn-primary"
                                            data-bs-toggle="tooltip"
                                            title="Edit">
                                            <i class="mdi mdi-square-edit-outline icon-medium"></i>
                                        </a>

                                        <form action="{{ route('states.delete') }}" method="POST" class="d-inline-block delete-form">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ base64_encode($state->id) }}">
                                            <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                data-bs-toggle="tooltip"
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

    {{-- Status Toggle Script --}}
    <script>
        $(document).on("change", ".toggle-status", function(e) {
            e.preventDefault();

            let checkbox = $(this);
            let id = checkbox.data("id");
            let is_active = checkbox.is(":checked") ? 1 : 0;

            Swal.fire({
                title: "Are you sure?",
                text: "Do you want to change the status?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#28a745",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, change it!",
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('states.updatestatus') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: id,
                            is_active: is_active
                        },
                        success: function(response) {
                            if (response.status) {
                                Swal.fire("Success!", response.message, "success");
                            } else {
                                Swal.fire("Error!", response.message, "error");
                                checkbox.prop("checked", !is_active);
                            }
                        },
                        error: function(xhr) {
                            Swal.fire("Error!", "Something went wrong", "error");
                            checkbox.prop("checked", !is_active);
                        }
                    });
                } else {
                    checkbox.prop("checked", !checkbox.is(":checked"));
                }
            });
        });
    </script>

    {{-- Delete Confirmation --}}
    <script>
        $(document).on("click", ".delete-btn", function(e) {
            e.preventDefault();
            let form = $(this).closest("form");

            Swal.fire({
                title: "Are you sure?",
                text: "This record will be deleted!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#28a745",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!",
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>

@endsection

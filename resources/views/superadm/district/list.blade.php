@extends('superadm.layout.master')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class="mb-3 d-flex justify-content-end">
                    <a href="{{ route('districts.create') }}" class="btn btn-add">Add District</a>
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
                                <th>State</th>
                                <th>District</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                        @foreach ($districts as $key => $d)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $d->state->state ?? 'N/A' }}</td>
                                <td>{{ $d->district }}</td>

                                <td>
                                    <form method="POST" class="d-inline-block">
                                        @csrf
                                        <label class="switch">
                                            <input type="checkbox" class="toggle-status"
                                                   data-id="{{ base64_encode($d->id) }}"
                                                   {{ $d->is_active ? 'checked' : '' }}>
                                            <span class="slider"></span>
                                        </label>
                                    </form>
                                </td>

                                <td>
                                    <a href="{{ route('districts.edit', base64_encode($d->id)) }}"
                                       class="btn btn-sm btn-primary" title="Edit">
                                        <i class="mdi mdi-square-edit-outline icon-medium"></i>
                                    </a>

                                    <form action="{{ route('districts.delete') }}" method="POST" class="d-inline-block delete-form">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ base64_encode($d->id) }}">
                                        <button type="button" class="btn btn-sm btn-danger delete-btn" title="Delete">
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


{{-- Status Toggle --}}
<script>
$(document).on("change", ".toggle-status", function () {
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
            $.post("{{ route('districts.updatestatus') }}", {
                _token: "{{ csrf_token() }}",
                id: id,
                is_active: is_active
            }, function (res) {
                if (!res.status) {
                    Swal.fire("Error", res.message, "error");
                    checkbox.prop("checked", !is_active);
                } else {
                    Swal.fire("Updated", res.message, "success");
                }
            });
        } else {
            checkbox.prop("checked", !is_active);
        }
    });
});
</script>

{{-- Delete Confirmation --}}
<script>
$(document).on("click", ".delete-btn", function () {
    let form = $(this).closest("form");

    Swal.fire({
        title: "Are you sure?",
        text: "This district will be deleted!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
    }).then((res) => {
        if (res.isConfirmed) form.submit();
    });
});
</script>

@endsection

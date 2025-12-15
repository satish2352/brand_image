@extends('superadm.layout.master')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class="mb-3 d-flex justify-content-end">
                    <a href="{{ route('cities.create') }}" class="btn btn-add">Add City</a>
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
                                <th>City</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($cities as $key => $city)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $city->state->state ?? 'N/A' }}</td>
                                    <td>{{ $city->district->district ?? 'N/A' }}</td>
                                    <td>{{ $city->city }}</td>

                                    <td>
                                        <form method="POST" class="d-inline-block">
                                            @csrf
                                            <label class="switch">
                                                <input type="checkbox" class="toggle-status"
                                                       data-id="{{ base64_encode($city->id) }}"
                                                       {{ $city->is_active ? 'checked' : '' }}>
                                                <span class="slider"></span>
                                            </label>
                                        </form>
                                    </td>

                                    <td>
                                        <a href="{{ route('cities.edit', base64_encode($city->id)) }}"
                                           class="btn btn-sm btn-primary" title="Edit">
                                            <i class="mdi mdi-square-edit-outline icon-medium"></i>
                                        </a>

                                        <form action="{{ route('cities.delete') }}" method="POST" class="d-inline-block">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ base64_encode($city->id) }}">
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

{{-- JS Status Toggle --}}
<script>
$(document).on("change", ".toggle-status", function() {
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
    }).then((res) => {
        if(res.isConfirmed) {
            $.post("{{ route('cities.updatestatus') }}", {
                _token: "{{ csrf_token() }}",
                id: id,
                is_active: is_active
            }, function(data) {
                if (!data.status) {
                    Swal.fire("Error", data.message, "error");
                    checkbox.prop("checked", !is_active);
                } else {
                    Swal.fire("Success", data.message, "success");
                }
            });
        } else {
            checkbox.prop("checked", !is_active);
        }
    });
});
</script>

{{-- JS Delete --}}
<script>
$(document).on("click", ".delete-btn", function() {
    let form = $(this).closest("form");

    Swal.fire({
        title: "Are you sure?",
        text: "This city will be deleted!",
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

@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class="mb-3 d-flex justify-content-end">
                        <a href="{{ route('financial-year.create') }}" class="btn btn-warning btn-add">Add Financial Year</a>
                    </div>

                    <div id="alert-container">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" id="success-alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" id="error-alert">
                            {{ session('error') }}
                        </div>
                    @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped datatables">
                            <thead>
                                <tr>
                                    <th>Sr.No.</th>
                                    <th>Financial Year</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($years as $key => $year)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $year->year }}</td>
                                        <td>
                                            <form action="{{ route('financial-year.updatestatus') }}" method="POST" class="d-inline-block">
                                                @csrf
                                                <label class="switch">
                                                    <input type="checkbox" class="toggle-status"
                                                        data-id="{{ base64_encode($year->id) }}"
                                                        {{ $year->is_active == '1' ? 'checked' : '' }}>
                                                    <span class="slider"></span>
                                                </label>
                                                <input type="hidden" name="id" value="{{ base64_encode($year->id) }}">
                                            </form>
                                        </td>
                                        <td>
                                            <a href="{{ route('financial-year.edit', base64_encode($year->id)) }}" 
                                               class="btn btn-sm btn-primary" 
                                               data-bs-toggle="tooltip" 
                                               data-bs-placement="top" 
                                               title="Edit">
                                               <i class="mdi mdi-square-edit-outline icon-medium"></i>
                                            </a>
                                            <form action="{{ route('financial-year.delete') }}" method="POST" class="d-inline-block delete-form">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ base64_encode($year->id) }}">
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
                $.ajax({
                    url: "{{ route('financial-year.updatestatus') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: id,
                        is_active: is_active
                    },
                    success: function(res) {
                        if(res.status) {
                            Swal.fire("Success!",  res.message, "success");
                        } else {
                            Swal.fire("Error!", "Something went wrong", "error");
                            checkbox.prop("checked", !checkbox.is(":checked"));
                        }
                    },
                    error: function() {
                        Swal.fire("Error!", "Something went wrong", "error");
                        checkbox.prop("checked", !checkbox.is(":checked"));
                    }
                });
            } else {
                checkbox.prop("checked", !checkbox.is(":checked"));
            }
        });
    });

$(document).on("click", ".delete-btn", function(e) {
    e.preventDefault();

    let btn = $(this);
    let form = btn.closest("form");
    let id = form.find("input[name='id']").val();

    Swal.fire({
        title: "Are you sure?",
        text: "Do you want to delete this financial year?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "No, cancel"
    }).then((result) => {
        if(result.isConfirmed){
            $.ajax({
                url: form.attr("action"),
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id
                },
                success: function(res) {
                    if(res.status) {
                        // Remove deleted row
                        btn.closest("tr").remove();

                        // Show Bootstrap alert above table
                        $("#alert-container").html(
                            `<div class="alert alert-success alert-dismissible fade show" id="success-alert">
                                ${res.message}
                             </div>`
                        );
                            setTimeout(() => {
        $("#alert-container .alert").alert('close');
    }, 5000);
                    } else {
                        $("#alert-container").html(
                            `<div class="alert alert-danger alert-dismissible fade show" id="error-alert">
                                Something went wrong
                             </div>`
                        );
                            setTimeout(() => {
        $("#alert-container .alert").alert('close');
    }, 5000);
                    }
                },
                error: function() {
                    $("#alert-container").html(
                        `<div class="alert alert-danger alert-dismissible fade show" id="error-alert">
                            Something went wrong
                         </div>`
                    );
                        setTimeout(() => {
        $("#alert-container .alert").alert('close');
    }, 5000);
                }
            });
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

@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-body">

                    <div class="mb-3 d-flex justify-content-end">
                        <a href="{{ route('radius.create') }}" class="btn btn-add">Add Radius</a>
                    </div>

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}</div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped datatables">
                            <thead>
                                <tr>
                                    <th>Sr. No</th>
                                    <th>Radius</th>
                                    <th>Status</th>
                                    <th width="150">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($radius as $k => $r)
                                    <tr>
                                        <td>{{ $k + 1 }}</td>
                                        <td>{{ $r->radius }}</td>

                                        <td>
                                            <label class="switch">
                                                <input type="checkbox" class="toggle-status"
                                                    data-id="{{ base64_encode($r->id) }}"
                                                    {{ $r->is_active ? 'checked' : '' }}>
                                                <span class="slider"></span>
                                            </label>
                                        </td>

                                        <td>
                                            <a href="{{ route('radius.edit', base64_encode($r->id)) }}"
                                                class="btn btn-sm btn-primary" title="Edit">
                                                <i class="mdi mdi-square-edit-outline icon-medium"></i>
                                            </a>

                                            <form action="{{ route('radius.delete') }}" method="POST"
                                                class="d-inline-block">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ base64_encode($r->id) }}">
                                                <button type="button" class="btn btn-sm btn-danger delete-btn"
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
        // STATUS UPDATE
        $(document).on('change', '.toggle-status', function() {
            let cb = $(this);
            let id = cb.data('id');
            let is_active = cb.is(':checked') ? 1 : 0;

            Swal.fire({
                title: "Are you sure?",
                text: "Do you want to change the status?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#28a745",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, change it!"
            }).then(res => {

                if (res.isConfirmed) {

                    $.post("{{ route('radius.updatestatus') }}", {
                        _token: "{{ csrf_token() }}",
                        id: id,
                        is_active: is_active
                    }, function(resp) {

                        if (!resp.status) {

                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: resp.message,
                            });

                            cb.prop('checked', !is_active);

                        } else {

                            Swal.fire({
                                icon: "success",
                                title: "Success",
                                text: resp.message,
                            });

                        }

                    }).fail(() => {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "Something went wrong",
                        });

                        cb.prop('checked', !is_active);
                    });

                } else {
                    cb.prop('checked', !cb.is(':checked'));
                }
            });
        });

        // DELETE CONFIRMATION
        $(document).on('click', '.delete-btn', function() {
            let form = $(this).closest('form');

            Swal.fire({
                title: "Delete?",
                text: "This radius will be deleted!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#28a745",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then(res => {
                if (res.isConfirmed) form.submit();
            });
        });
    </script>
@endsection

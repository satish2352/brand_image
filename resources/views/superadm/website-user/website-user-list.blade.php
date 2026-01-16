@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <h4 class="mb-3">Website Users</h4>

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped datatables">
                            <thead>
                                <tr>
                                    <th>Sr.No.</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Organization</th>
                                    <th>GST</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($users as $key => $user)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->mobile_number }}</td>
                                        {{-- <td>{{ $user->organisation }}</td>
                                        <td>{{ $user->gst }}</td> --}}
                                        <td>{{ $user->organisation ?? 'NA' }}</td>
                                        <td>{{ $user->gst ?? 'NA' }}</td>


                                        <td>
                                            <label class="switch">
                                                <input type="checkbox" class="toggle-status"
                                                    data-id="{{ base64_encode($user->id) }}"
                                                    {{ $user->is_active ? 'checked' : '' }}>
                                                <span class="slider"></span>
                                            </label>
                                        </td>


                                        <td>
                                            <form action="{{ route('website-user.delete') }}" method="POST"
                                                class="d-inline-block delete-form">
                                                @csrf
                                                <input type="hidden" name="id"
                                                    value="{{ base64_encode($user->id) }}">
                                                <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                    title="Delete">
                                                    <i class="mdi mdi-trash-can-outline"></i>
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

    {{-- Delete Confirmation --}}
    <script>
        $(document).on("click", ".delete-btn", function(e) {
            e.preventDefault();
            let form = $(this).closest("form");

            Swal.fire({
                title: 'Are You Sure?',
                text: 'Do you really want to delete this record?',
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#28a745",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>
    <script>
        $(document).on('change', '.toggle-status', function() {
            let id = $(this).data('id');

            $.ajax({
                url: "{{ route('website-user.toggle-status') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id
                },
                success: function(res) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: res.message,
                        timer: 1200,
                        showConfirmButton: false
                    });
                },
                error: function() {
                    Swal.fire('Error', 'Something went wrong', 'error');
                }
            });
        });
    </script>
@endsection

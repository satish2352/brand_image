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
                                            <button 
                                                type="button" 
                                                class="btn btn-sm btn-info view-user mb-2"
                                                data-id="{{ base64_encode($user->id) }}"
                                                title="View">
                                                <i class="mdi mdi-eye-outline"></i>
                                            </button>
                                            <form action="{{ route('website-user.delete') }}" method="POST"
                                                class="d-inline-block delete-form">
                                                @csrf
                                                <input type="hidden" name="id"
                                                    value="{{ base64_encode($user->id) }}">
                                                <button type="button" class="btn btn-sm btn-danger delete-btn mb-2"
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

    <!-- USER DETAILS MODAL -->

    <div class="modal fade" id="userDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">

                <div class="modal-header text-white" style="background-color: #008f97">
                    <h5 class="modal-title text-white">User Details</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <!-- ROW 1 -->
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-3 font-weight-bold">Name</div>
                                <div class="col-1">:</div>
                                <div class="col-8" id="u_name"></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-3 font-weight-bold">Email</div>
                                <div class="col-1">:</div>
                                <div class="col-8" id="u_email"></div>
                            </div>
                        </div>
                    </div>

                    <!-- ROW 2 -->
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-3 font-weight-bold">Mobile</div>
                                <div class="col-1">:</div>
                                <div class="col-8" id="u_mobile"></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-3 font-weight-bold">Organization</div>
                                <div class="col-1">:</div>
                                <div class="col-8" id="u_org"></div>
                            </div>
                        </div>
                    </div>

                    <!-- ROW 3 -->
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-3 font-weight-bold">GST</div>
                                <div class="col-1">:</div>
                                <div class="col-8" id="u_gst"></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-3 font-weight-bold">Status</div>
                                <div class="col-1">:</div>
                                <div class="col-8">
                                    <span class="badge" id="u_status" style="color:#fff;"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-3 font-weight-bold">Created At</div>
                                <div class="col-1">:</div>
                                <div class="col-8" id="u_created"></div>
                            </div>
                        </div>
                    </div> --}}

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Close
                    </button>
                </div>

            </div>
        </div>
    </div>



@if (session('success'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: @json(session('success')),
        timer: 1500,
        showConfirmButton: false
    });
});
</script>
@endif

@if (session('error'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: 'error',
        title: 'Not Allowed',
        text: @json(session('error'))
    });
});
</script>
@endif

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
    <script>
        $(document).on('click', '.view-user', function () {
            let id = $(this).data('id');

            $.ajax({
                url: "{{ route('website-user.view') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id
                },
                success: function (res) {

                    $('#u_name').text(res.name);
                    $('#u_email').text(res.email);
                    $('#u_mobile').text(res.mobile_number);
                    $('#u_org').text(res.organisation ?? 'NA');
                    $('#u_gst').text(res.gst ?? 'NA');
                    $('#u_created').text(res.created_at);

                    if (res.is_active) {
                        $('#u_status').text('Active').removeClass().addClass('badge bg-success');
                    } else {
                        $('#u_status').text('Inactive').removeClass().addClass('badge bg-danger');
                    }

                    $('#userDetailsModal').modal('show');
                },
                error: function () {
                    Swal.fire('Error', 'Unable to fetch user details', 'error');
                }
            });
        });
    </script>

@endsection

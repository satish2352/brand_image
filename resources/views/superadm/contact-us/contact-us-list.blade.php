@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <h4 class="mb-3">Contact Us Enquiries</h4>

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
                                    <th>Category Name</th>
                                    <th>Full Name</th>
                                    <th>Mobile</th>
                                    <th>Email</th>
                                    <th>Address</th>
                                    <th>Remark</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($contacts as $key => $row)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $row->category_name ?: 'Direct Enquiry' }}</td>
                                        <td>{{ $row->full_name }}</td>
                                        <td>{{ $row->mobile_no }}</td>
                                        <td>{{ $row->email }}</td>
                                        <td>{{ $row->address }}</td>
                                        <td>{{ $row->remark }}</td>
                                        <td>
                                            <a href="{{ route('contact-us.view', base64_encode($row->id)) }}"
                                                class="btn btn-sm btn-info mb-2" title="View Details">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <form action="{{ route('contact-us.delete') }}" method="POST"
                                                class="d-inline-block delete-form">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ base64_encode($row->id) }}">
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

    {{-- Delete Confirmation --}}
    <script>
        $(document).on("click", ".delete-btn", function(e) {
            e.preventDefault();
            let form = $(this).closest("form");

            Swal.fire({
                title: "Are you sure?",
                text: "This enquiry will be deleted!",
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
@endsection

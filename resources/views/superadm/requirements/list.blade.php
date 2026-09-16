@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <h4 class="mb-3">Client Requirements</h4>

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped datatables">
                            <thead>
                                <tr>
                                    <th>Sr.No.</th>
                                    <th>Received</th>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>City</th>
                                    <th>Media Type</th>
                                    <th>Campaign Dates</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($requirements as $key => $row)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $row->created_at?->format('d M Y, h:i A') }}</td>
                                        <td>{{ $row->full_name }}</td>
                                        <td>{{ $row->mobile_no }}</td>
                                        <td>{{ $row->city }}</td>
                                        <td>{{ $row->media_type }}</td>
                                        <td>
                                            {{ $row->campaign_start_date?->format('d-m-Y') }}
                                            &ndash;
                                            {{ $row->campaign_end_date?->format('d-m-Y') }}
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $row->status === 'closed' ? 'secondary' : ($row->status === 'in_progress' ? 'warning' : 'success') }}">
                                                {{ $row->statusLabel() }}
                                            </span>
                                        </td>
                                        <td>
                                            {{-- Same icon actions as the Contact Us list, so the
                                                 two enquiry screens behave identically. --}}
                                            <a href="{{ route('requirements.view', base64_encode($row->id)) }}"
                                                class="btn btn-sm btn-info mb-2" title="View Details">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <form action="{{ route('requirements.delete') }}" method="POST"
                                                class="d-inline-block delete-form">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $row->id }}">
                                                <button type="button" class="btn btn-sm btn-danger delete-btn mb-2"
                                                    title="Delete">
                                                    <i class="mdi mdi-trash-can-outline"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            No requirements received yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation — same SweetAlert prompt the Contact Us list uses,
         rather than the browser's own confirm() dialog. --}}
    <script>
        $(document).on("click", ".delete-btn", function(e) {
            e.preventDefault();
            let form = $(this).closest("form");

            Swal.fire({
                title: "Are you sure?",
                text: "This requirement will be deleted!",
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

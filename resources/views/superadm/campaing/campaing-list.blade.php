@extends('superadm.layout.master')

@section('content')
<div class="card">
    <div class="card-body">

        <h4 class="mb-3">Campaign List</h4>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>User Name</th>
                    <th>Total Campaigns</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @foreach($campaigns as $key => $row)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $row->user_name }}</td>
                    <td>
                        <span class="badge badge-primary">
                            {{ $row->total_campaigns }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.campaign.details', base64_encode($row->user_id)) }}"
                        class="btn btn-sm btn-info">
                            View
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
</div>
@endsection





{{-- @extends('superadm.layout.master')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <h4 class="mb-3">Campaign List</h4>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-striped datatables">
                        <thead>
                            <tr>
                                <th>Sr.No.</th>
                                <th>Campaign Name</th>
                                <th>Media</th>
                                <th>Size</th>
                                <th>Area</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Total Days</th>
                                <th>Total Price</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead> --}}
<?php 
// dd($campaigns);
// die();
?>
                        {{-- <tbody>
                            @foreach($campaigns as $key => $campaign)
                            <tr>
                                <td>{{ $key + 1 }}</td>

                                <td>{{ $campaign->campaign_name }}</td>

                                <td>{{ $campaign->media_title }}</td>

                                <td>{{ $campaign->width }} x {{ $campaign->height }}</td>

                                <td>{{ $campaign->common_stdiciar_name }}</td>

                                <td>{{ \Carbon\Carbon::parse($campaign->from_date)->format('d-m-Y') }}</td>

                                <td>{{ \Carbon\Carbon::parse($campaign->to_date)->format('d-m-Y') }}</td>

                                <td>{{ $campaign->total_days }}</td>

                                <td>
                                    <strong>₹ {{ number_format($campaign->total_price, 2) }}</strong>
                                </td> --}}
                                 {{-- <td>
                                 <a href="{{ route('campaign.export.excel', base64_encode($campaign->$campaign_id)) }}"
                                   class="btn btn-success btn-sm">
                                    Export Excel
                                </a>
</td>
</td>
  <a href="{{ route('campaign.export.ppt', base64_encode($campaign->$campaign_id)) }}"
                                   class="btn btn-warning btn-sm">
                                    Export PPT
                                </a></td> --}}
                                {{-- STATUS TOGGLE (LIKE USER TABLE) --}}
                                {{-- <td>
                                    <label class="switch">
                                        <input type="checkbox"
                                            class="toggle-status"
                                            data-id="{{ base64_encode($campaign->cart_item_id) }}"
                                            {{ $campaign->status === 'ACTIVE' ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </td> --}}

                                {{-- DELETE --}}
                                {{-- <td>
                                    <form action="{{ route('admin-campaign.delete') }}"
                                          method="POST"
                                          class="d-inline-block delete-form">
                                        @csrf
                                        <input type="hidden"
                                               name="id"
                                               value="{{ base64_encode($campaign->cart_item_id) }}">
                                        <button type="button"
                                                class="btn btn-sm btn-danger delete-btn"
                                                title="Delete">
                                            <i class="mdi mdi-trash-can-outline"></i>
                                        </button>
                                    </form>
                                </td> --}}
                            {{-- </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div> --}}

{{-- DELETE CONFIRMATION (REUSED) --}}
{{-- <script>
$(document).on("click", ".delete-btn", function (e) {
    e.preventDefault();
    let form = $(this).closest("form");

    Swal.fire({
        title: "Are you sure?",
        text: "This campaign will be deleted!",
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
</script> --}}

{{-- STATUS TOGGLE (REUSED STRUCTURE) --}}
{{-- <script>
$(document).on('change', '.toggle-status', function () {
    let id = $(this).data('id');

    $.ajax({
        url: "{{ route('admin-campaign.toggle-status') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            id: id
        },
        success: function (res) {
            Swal.fire({
                icon: 'success',
                title: 'Updated',
                text: res.message,
                timer: 1200,
                showConfirmButton: false
            });
        },
        error: function () {
            Swal.fire('Error', 'Something went wrong', 'error');
        }
    });
});
</script> --}}
{{-- @endsection --}}

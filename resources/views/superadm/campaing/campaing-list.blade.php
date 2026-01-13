@extends('superadm.layout.master')

@section('content')
<div class="card">
    <div class="card-body">

        <h4 class="mb-3">Campaign List</h4>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User Name</th>
                    <th>Campaign Name</th>
                    <th>Date</th>
                   
                     <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @foreach($campaigns as $key => $row)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $row->user_name }}</td>
                    <td>{{ $row->campaign_name }}</td>
                    <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d M Y') }}</td>
                    

                    <td>
                        <a href="{{ route('admin.campaign.details', [
        'campaignId' => base64_encode($row->campaign_id),
        'userId'     => base64_encode($row->user_id)
    ]) }}"
    class="btn btn-sm btn-info">
    View
</a>

                        {{-- <a href="{{ route('admin.campaign.details', base64_encode($row->user_id) userId) }}"
                        class="btn btn-sm btn-info">
                            View
                        </a> --}}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
</div>
@endsection



                    
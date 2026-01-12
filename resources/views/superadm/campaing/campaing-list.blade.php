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


                    
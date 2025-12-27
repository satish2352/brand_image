@extends('superadm.layout.master')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <h4 class="mb-3">User Payment List</h4>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped datatables">
                        <thead>
                            <tr>
                                <th>Sr.No.</th>
                                <th>User Name</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>Order No</th>
                                <th>Total Amount (₹)</th>
                                <th>Payment Status</th>
                                <th>Payment ID</th>
                                <th>Order Date</th>
                                 <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($payments as $key => $row)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $row->name }}</td>
                                <td>{{ $row->email }}</td>
                                <td>{{ $row->mobile_number }}</td>
                                <td>{{ $row->order_no }}</td>
                                <td>{{ number_format($row->total_amount, 2) }}</td>
                                <td>
                                    
                                        {{ $row->payment_status }}
                                   
                                </td>
                                <td>{{ $row->payment_id }}</td>
                                <td>{{ date('d-m-Y H:i', strtotime($row->created_at)) }}</td>
                                    <td>
                <a href="{{ route('user-payment.details', $row->id) }}"
                   class="btn btn-sm btn-info">
                    <i class="fa fa-eye"></i>
                </a>
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
@endsection

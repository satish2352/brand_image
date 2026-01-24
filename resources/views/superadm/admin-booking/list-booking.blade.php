@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <h4 class="mb-3">Booking List</h4>

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
                                @foreach ($payments as $key => $row)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $row->name }}</td>
                                        <td>{{ $row->email }}</td>
                                        <td>{{ $row->mobile_number }}</td>
                                        <td>{{ $row->order_no }}</td>
                                        <td>{{ number_format($row->grand_total, 2) }}</td>
                                        {{-- <td>
                                    
                                        {{ $row->payment_status }}
                                   
                                </td> --}}
                                        {{-- <td>{{ $row->payment_id }}</td> --}}

                                        <td>
                                            @if ($row->payment_status === 'PAID')
                                                <span class="badge badge-paid">PAID</span>
                                            @elseif($row->payment_status === 'ADMIN_BOOKED')
                                                <span class="badge badge-offline">ADMIN BOOKED</span>
                                            @else
                                                <span class="badge badge-pending">PENDING</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($row->payment_status === 'PAID' && $row->payment_id)
                                                <span class="payment-id" title="{{ $row->payment_id }}">
                                                    {{ Str::limit($row->payment_id, 15) }}
                                                </span>
                                            @elseif($row->payment_status === 'ADMIN_BOOKED')
                                                <span class="badge badge-offline">Offline</span>
                                            @else
                                                <span class="badge badge-pending">Pending</span>
                                            @endif
                                        </td>



                                        <td>{{ date('d-m-Y H:i', strtotime($row->created_at)) }}</td>
                                        <td>
                                            <a href="{{ route('admin-booking.booking-details', base64_encode($row->id)) }}"
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

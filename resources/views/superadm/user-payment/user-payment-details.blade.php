@extends('superadm.layout.master')

@section('content')
    <style>
        .order-header {
            background: #008f97 !important;
            color: #fff !important;
        }

        p {
            font-size: 16px;
        }

        th,
        td {
            font-size: 15px;
        }

        .txt-color {
            color: #fff;
        }
    </style>

    <div class="container-fluid">

        {{-- 🔙 Back Button --}}
        <div class="mb-3">
            <a href="{{ route('user-payment.list') }}" class="btn btn-outline-secondary">
                ← Back to User Payment List
            </a>
        </div>

        {{-- 🧾 Order Details --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header order-header">
                <h5 class="mb-0 txt-color">Order Details</h5>
            </div>

            <div class="card-body">
                <div class="row">

                    <div class="col-md-6">
                        <p><strong>User Name:</strong> {{ $order->name }}</p>
                        <p><strong>Email Id:</strong> {{ $order->email }}</p>
                        <p><strong>Mobile No.:</strong> {{ $order->mobile_number }}</p>
                    </div>

                    <div class="col-md-6">
                        <p><strong>Order No:</strong> {{ $order->order_no }}</p>

                        <p>
                            <strong>Payment Status:</strong>
                            <span class="badge bg-success txt-color">
                                {{ $order->payment_status }}
                            </span>
                        </p>

                        <p>
                            <strong>Total Amount:</strong>
                            <span class="text-success fw-bold fs-5">
                                ₹{{ number_format($order->grand_total, 2) }}
                            </span>
                        </p>
                    </div>

                </div>
            </div>
        </div>

        {{-- 📦 Ordered Items --}}
        <div class="card shadow-sm">
            <div class="card-header order-header">
                <h5 class="mb-0 txt-color">Ordered Items</h5>
            </div>

            <div class="card-body p-0">
                <table class="table table-bordered table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Sr. No.</th>
                            <th>Media Title</th>
                            <th>Size</th>
                            <th>Location</th>
                            <th>From Date</th>
                            <th>To Date</th>
                            <th class="text-end">Price (₹)</th>
                            <th class="text-end">GST (18%) (₹)</th>
                            <th class="text-end">Final Total (₹)</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php $grandTotal = 0; @endphp

                        @foreach ($order->items as $key => $item)
                            @php
                                $grandTotal += $item->final_total;
                            @endphp
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td><strong>{{ $item->media_title }}</strong></td>
                                <td>{{ $item->width }} × {{ $item->height }}</td>
                                <td>{{ $item->address }}</td>
                                <td>{{ date('d-m-Y', strtotime($item->from_date)) }}</td>
                                <td>{{ date('d-m-Y', strtotime($item->to_date)) }}</td>

                                <td class="text-end">
                                    {{ number_format($item->item_total, 2) }}
                                </td>

                                <td class="text-end">
                                    {{ number_format($item->gst_amount, 2) }}
                                </td>

                                <td class="text-end fw-bold">
                                    {{ number_format($item->final_total, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot class="table-secondary">
                        <tr>
                            <th colspan="8" class="text-end">Grand Total</th>
                            <th class="text-end text-success fs-6">
                                ₹{{ number_format($grandTotal, 2) }}
                            </th>
                        </tr>
                    </tfoot>

                </table>
            </div>
        </div>

    </div>
@endsection

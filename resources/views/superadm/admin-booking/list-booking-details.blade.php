@extends('superadm.layout.master')

@section('content')
    <style>
        .order-header {
            background: #008f97 !important;
            color: #fff !important;
        }

        p {
            font-size: 18px;
        }

        th td {
            font-size: 18px;
        }
    </style>
    <div class="container-fluid">

        {{-- 🔙 Back Button --}}
        <div class="mb-3">
            <a href="{{ route('admin.booking.list-booking') }}" class="btn btn-outline-secondary">
                ← Back to Booking List
            </a>
        </div>

        {{-- 🧾 Order Summary --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header order-header">
                <h5 class="mb-0 order-header">Order Details</h5>
            </div>

            <div class="card-body">
                <div class="row">

                    <div class="col-md-6">
                        <p><strong>User Name:</strong> {{ $order->name }}</p>
                        <p><strong>Email:</strong> {{ $order->email }}</p>
                        <p><strong>Mobile:</strong> {{ $order->mobile_number }}</p>
                    </div>

                    <div class="col-md-6">
                        <p><strong>Order No:</strong> {{ $order->order_no }}</p>

                        <!-- <p>
                            <strong>Payment Status:</strong>
                           <span class="badge {{ $order->payment_status == 'PAID' ? 'bg-success' : 'bg-warning' }}" style = "color:#fff;">
    {{ $order->payment_status }}
</span>

                        </p> -->
<p>
    <strong>Payment Status:</strong>

    @php
        $statusText = match($order->payment_status) {
            'PAID' => 'Paid',
            'ADMIN_BOOKED' => 'Admin Booked',
        };

        $statusClass = match($order->payment_status) {
            'PAID' => 'bg-success',
            'ADMIN_BOOKED' => 'bg-info',
        };
    @endphp

    <span class="badge {{ $statusClass }}" style="color:#fff;">
        {{ $statusText }}
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
                <h5 class="mb-0 order-header">Ordered Items</h5>
            </div>

      <div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover table-bordered mb-0 align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>Media Title</th>
                            <th>Size</th>
                            <th>Location</th>
                            <th class="text-end">Monthly Price (₹)</th>
                            <th>From Date</th>
                            <th>To Date</th>
                             <th class="text-end">Per Day Price (₹)</th>
                                <th class="text-end">Total Days</th>
                            <th class="text-end">Total Price (₹)</th>
                          
                            <th class="text-end">GST (18%) (₹)</th>
                            <th class="text-end">Final Total (₹)</th>
                        </tr>
                    </thead>

                   <tbody>
@forelse($order->items as $key => $item)
<tr>
    <td>{{ $key + 1 }}</td>
    <td>{{ $item->media_title ?? '-' }}</td>
    <td>{{ $item->width }} × {{ $item->height }}</td>
    <td>{{ $item->address ?? '-' }}</td>

    <td class="text-end">{{ number_format($item->order_price, 2) }}</td>
    <td>{{ $item->from_date ?? '-' }}</td>
    <td>{{ $item->to_date ?? '-' }}</td>

    <td class="text-end">{{ number_format($item->per_day_price, 2) }}</td>
    <td class="text-end">{{ $item->total_days ?? '-' }}</td>

    <td class="text-end">{{ number_format($item->total_price, 2) }}</td>
    <td class="text-end">{{ number_format($item->gst_amount, 2) }}</td>
    <td class="text-end fw-bold">{{ number_format($item->final_amount, 2) }}</td>
</tr>
@empty
<tr>
    <td colspan="12" class="text-center text-muted">
        No items found for this order
    </td>
</tr>
@endforelse
</tbody>


                    <tfoot class="table-secondary">
                        <tr>
                            <th colspan="11" class="text-end">Grand Total</th>
                            <th class="text-end text-success fs-6">
                       ₹{{ number_format($order->grand_total, 2) }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
</div>
            </div>
        </div>

    </div>

    {{-- 🎨 Inline UI polish --}}
    <style>
        .card-header h5 {
            font-weight: 600;
        }

        p {
            margin-bottom: 0.4rem;
        }
    </style>
@endsection

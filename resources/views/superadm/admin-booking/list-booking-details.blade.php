@extends('superadm.layout.master')

@section('content')
<style>
    .order-header {
    background: #952419 !important;
    color: #fff !important;
}
p{
    font-size: 18px;
}
th td{
    font-size: 18px; 
}
</style>
<div class="container-fluid">

    {{-- 🔙 Back Button --}}
    <div class="mb-3">
        <a href="{{ route('user-payment.list') }}" class="btn btn-outline-secondary">
            ← Back to Booking List
        </a>
    </div>

    {{-- 🧾 Order Summary --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header order-header" >
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

                    <p>
                        <strong>Payment Status:</strong>
                        <span class="badge 
                            {{ $order->payment_status == 'PAID'  }}">
                            {{ $order->payment_status }}
                        </span>
                    </p>

                    <p>
                        <strong>Total Amount:</strong>
                        <span class="text-success fw-bold fs-5">
                            ₹{{ number_format($order->total_amount, 2) }}
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
            <table class="table table-hover table-bordered mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Media Title</th>
                        <th>Size</th>
                        <th>Location</th>
                        <th class="text-end">Price (₹)</th>
                        <th>From Date</th>
                           <th>To Date</th>
                        {{-- <th class="text-center">Qty</th> --}}
                        <th class="text-end">Total (₹)</th>
                    </tr>
                </thead>

                <tbody>
                    @php $grandTotal = 0; @endphp

                    @forelse($order->items as $key => $item)
                        @php $grandTotal += $item->total; @endphp
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>
                                <strong>{{ $item->media_title ?? '-' }}</strong>
                            </td>
                            <td>{{ $item->width }} × {{ $item->height }}</td>
                            <td>{{ $item->address ?? '-' }}</td>
                            <td class="text-end">{{ number_format($item->price, 2) }}</td>
                             <td>{{ $item->from_date ?? '-' }}</td>
                              <td>{{ $item->to_date ?? '-' }}</td>
                            {{-- <td class="text-center">{{ $item->qty }}</td> --}}
                            <td class="text-end fw-semibold">
                                {{ number_format($item->total, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                No items found for this order
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot class="table-secondary">
                    <tr>
                        <th colspan="7" class="text-end">Grand Total</th>
                        <th class="text-end text-success fs-6">
                            ₹{{ number_format($grandTotal, 2) }}
                        </th>
                    </tr>
                </tfoot>
            </table>
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

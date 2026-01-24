@extends('website.dashboard.layout')

@section('title', 'Payment History')

@section('dashboard-content')

    <h4 class="mb-4 fw-bold">Payment History</h4>

    @if ($payments->isEmpty())
        <div class="alert alert-info">
            No payment history found.
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="bg-light">
                            <tr>
                                <th>Sr. No.</th>
                                <th>Campaign</th>
                                <th>Order No</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Receipt </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $index => $pay)
                                <tr>
                                    <td>{{ $index + 1 }}</td>

                                    <td>
                                        {{ $pay->campaign_name ?: '-' }}
                                    </td>

                                    <td class="fw-semibold text-primary">
                                        {{ $pay->order_no }}
                                    </td>

                                    <td class="fw-bold text-success">
                                        ₹ {{ number_format($pay->grand_total, 2) }}
                                    </td>

                                    <td>
                                        @if ($pay->payment_status == 'PAID')
                                            <span class="badge bg-success">PAID</span>
                                        @elseif($pay->payment_status == 'ADMIN_BOOKED')
                                            <span class="badge bg-warning text-dark">ADMIN BOOKED</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $pay->payment_status }}</span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ \Carbon\Carbon::parse($pay->created_at)->format('d M Y') }}
                                    </td>

                                    <td>
                                        <a href="{{ route('campaign.invoice.view', base64_encode($pay->order_id)) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>

                                        <a href="{{ route('invoice.download', base64_encode($pay->order_id)) }}"
                                            class="btn btn-sm btn-outline-success ms-1">
                                            PDF
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

@endsection

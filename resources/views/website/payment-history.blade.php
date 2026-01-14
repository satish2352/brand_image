@extends('website.dashboard.layout')

@section('title', 'Payment History')

@section('dashboard-content')

    <h4 class="mb-4">Payment History</h4>

    @if ($payments->isEmpty())
        <div class="alert alert-info">
            No payment history found.
        </div>
    @else
        <table class="table table-bordered text-center align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Campaign Name</th>
                    <th>Location</th>
                    <th>Order No</th>

                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments as $index => $pay)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $pay->campaign_name ?? '-' }}</td>
                        <td>{{ $pay->common_stdiciar_name ?? '-' }}</td>
                        <td>{{ $pay->order_no }}</td>

                        <td>₹ {{ number_format($pay->total_amount, 2) }}</td>
                        <td>
                            <span class="badge bg-success">
                                {{ strtoupper($pay->payment_status) }}
                            </span>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($pay->created_at)->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('campaign.invoice.view', base64_encode($pay->order_id)) }}"
                                class="btn btn-sm btn-primary">
                                View
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

@endsection

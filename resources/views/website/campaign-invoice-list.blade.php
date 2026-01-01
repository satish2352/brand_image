@extends('website.layout')

@section('title', 'Invoice & Payments')

@section('content')

	<!-- breadcrumb-section -->
	<div class="breadcrumb-section breadcrumb-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="breadcrumb-text">
						<p>Campaign Invoice & Payment</p>
						<h1>Campaign</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

<div class="container my-5">

    <h3 class="mb-4">Invoice & Payments</h3>

    @if($invoices->isEmpty())
        <div class="alert alert-info">No invoices found.</div>
    @else
    <table class="table table-bordered text-center align-middle">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Campaign</th>
                <th>Order No</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Payment ID</th>
                <th>Date</th>
                <th>Invoice</th>
            </tr>
        </thead>

        <tbody>
        @foreach($invoices as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $row->campaign_name ?? '-' }}</td>
                <td>{{ $row->order_no }}</td>
                <td>₹ {{ number_format($row->total_amount, 2) }}</td>
                <td>
                    <span class="badge bg-success">PAID</span>
                </td>
                <td>{{ $row->payment_id }}</td>
                <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d M Y') }}</td>
                <td>
                    <a href="{{ route('campaign.invoice.view', encrypt($row->order_id)) }}"
                       class="btn btn-sm btn-primary">
                        View
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif

</div>
@endsection

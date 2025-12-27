@extends('website.layout')

@section('title', 'My Campaigns')

@section('content')
<style>
    .nav-tabs {
    border-bottom:none !important;
}
    .nav-tabs .nav-link.active {
        border: none !important;
        border-color:none !important;
    }
</style>
<div class="container my-5">

    <h3 class="mb-4">Campaigns</h3>

    {{-- ================= TAB HEADERS ================= --}}
    <ul class="nav nav-tabs mb-4" id="campaignTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active"
                    id="campaigns-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#campaigns"
                    type="button"
                    role="tab">
                Campaigns
            </button>
        </li>

        <li class="nav-item" role="presentation">
            <button class="nav-link"
                    id="payments-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#payments"
                    type="button"
                    role="tab">
                Invoice & Payments
            </button>
        </li>
    </ul>

    {{-- ================= TAB CONTENT ================= --}}
    <div class="tab-content" id="campaignTabsContent">

        {{-- =================================================
            ================ CAMPAIGNS TAB ==================
        ================================================= --}}
        <div class="tab-pane fade show active"
             id="campaigns"
             role="tabpanel">

            {{-- SEARCH --}}
            <form method="GET" action="{{ route('campaign.list') }}" class="row g-2 mb-4">
                <div class="col-md-4">
                    <input type="text"
                           name="campaign_name"
                           class="form-control"
                           placeholder="Search Campaign Name"
                           value="{{ request('campaign_name') }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100">Search</button>
                </div>
            </form>

            @if($campaigns->count() === 0)
                <div class="alert alert-info">No campaigns found.</div>
            @else

            <div class="accordion" id="campaignAccordion">

                @foreach($campaigns as $campaignId => $items)
                    @php
                        $campaignName = $items->first()->campaign_name;
                        $totalAmount = $items->sum(fn($i) => $i->price * $i->qty);
                    @endphp

                    <div class="accordion-item mb-3 shadow-sm">
                        <h2 class="accordion-header" id="heading{{ $campaignId }}">
                            <button class="accordion-button collapsed d-flex align-items-center"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#collapse{{ $campaignId }}">

                                {{-- LEFT --}}
                                <div class="col-lg-6">
                                    <strong class="text-primary">
                                        {{ $campaignName }}
                                    </strong>

                                    <span class="badge bg-dark ms-2">
                                        ₹ {{ number_format($totalAmount, 2) }}
                                    </span>
                                </div>

                            </button>
                        </h2>

                        <div id="collapse{{ $campaignId }}"
                             class="accordion-collapse collapse"
                             data-bs-parent="#campaignAccordion">

                            <div class="accordion-body p-0">

                                {{-- ACTION BUTTONS --}}
                                <div class="d-flex justify-content-end p-3 gap-2">
                                    <a href="{{ route('campaign.export.excel', $campaignId) }}"
                                       class="btn btn-success btn-sm"
                                       onclick="event.stopPropagation();">
                                        Export Excel
                                    </a>

                                    <form action="{{ route('checkout.campaign', $campaignId) }}"
                                          method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">
                                            Place Order
                                        </button>
                                    </form>
                                </div>

                                {{-- ITEMS TABLE --}}
                                <table class="table table-bordered text-center align-middle mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Media</th>
                                            <th>Size</th>
                                            <th>Price</th>
                                            {{-- <th>Qty</th> --}}
                                            <th>Total</th>
                                            <th>Date</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                    @foreach($items as $index => $row)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $row->media_title ?? '-' }}</td>
                                            <td>{{ $row->width }} × {{ $row->height }}</td>
                                            <td>₹ {{ number_format($row->price, 2) }}</td>
                                            {{-- <td>{{ $row->qty }}</td> --}}
                                            <td>₹ {{ number_format($row->price * $row->qty, 2) }}</td>
                                            <td>{{ \Carbon\Carbon::parse($row->campaign_date)->format('d M Y') }}</td>
                                            <td>
                                                <a href="{{ route('campaign.details', $row->cart_item_id) }}"
                                                   class="btn btn-primary btn-sm">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                @endforeach

            </div>
            @endif
        </div>

        {{-- =================================================
            ============ INVOICE & PAYMENTS TAB =============
        ================================================= --}}
        <div class="tab-pane fade"
     id="payments"
     role="tabpanel">

    @if($payments->isEmpty())
        <div class="alert alert-info mt-3">
            Nothing to show.
        </div>
    @else
        <table class="table table-bordered mt-3 text-center align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Sr. No.</th>
                     {{-- <th>Campaign Name</th>
                      <th>Category Name</th>
                       <th>Location</th> --}}
                    <th>Order No</th>
                    <th>Amount</th>
                    <th>Status</th>
                    {{-- <th>Payment ID</th> --}}
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $index => $pay)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        {{-- <th>{{$pay->$campaign_name}}</th>
                        <th>{{$pay->$category_name}}</th>
                        <th>{{$pay->$area_name}}</th> --}}
                        <td>{{ $pay->order_no }}</td>
                        <td>₹ {{ number_format($pay->total_amount, 2) }}</td>
                        <td>
                            <span class="badge bg-success">
                                {{ strtoupper($pay->payment_status) }}
                            </span>
                        </td>
                        {{-- <td>{{ $pay->payment_id }}</td> --}}
                        <td>{{ \Carbon\Carbon::parse($pay->created_at)->format('d M Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

</div>


    </div> {{-- tab-content --}}
</div>
@endsection

@extends('website.dashboard.layout')

@section('title', 'Campaign List')

{{-- The layout draws the breadcrumb and the heading over the artwork, so the
     page only has to say what it is. Same three labels as before. --}}
@section('dashboard-title')
    @if ($type === 'open')
        Campaign List
    @elseif($type === 'booked')
        Booked Campaign List
    @else
        Past Campaign List
    @endif
@endsection

@section('dashboard-subtitle')
    @if ($type === 'open')
        Search and manage all your campaigns in one place.
    @elseif($type === 'booked')
        Every campaign you have placed an order for.
    @else
        Campaigns that have finished their booking period.
    @endif
@endsection

@section('dashboard-content')

    {{-- SEARCH --}}
    <div class="bi-dash-card">
        <form method="GET" action="{{ url()->current() }}" class="bi-dash-search">
            <div class="bi-dash-search-field">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="text" name="campaign_name" class="form-control" placeholder="Search Campaign Name"
                    value="{{ request('campaign_name') }}">
            </div>
            <button class="btn">Search</button>
        </form>
    </div>

    @if ($campaigns->isEmpty())
        {{-- Empty state. The artwork is inline SVG rather than a file: it is two
             flat shapes in the brand colours, so it costs nothing to ship and
             cannot 404. --}}
        <div class="bi-dash-card bi-dash-empty">
            <svg class="bi-dash-empty-art" viewBox="0 0 220 150" fill="none" role="img"
                aria-label="No campaigns illustration">
                <ellipse cx="110" cy="82" rx="88" ry="56" fill="#F97316" fill-opacity=".12" />
                <rect x="58" y="26" width="70" height="92" rx="8" fill="#FFFFFF" stroke="#CBD5E1"
                    stroke-width="2" />
                <rect x="72" y="44" width="42" height="6" rx="3" fill="#CBD5E1" />
                <rect x="72" y="58" width="42" height="6" rx="3" fill="#CBD5E1" />
                <rect x="72" y="72" width="28" height="6" rx="3" fill="#CBD5E1" />
                <path d="M118 96l34-18v40l-34-18z" fill="#F97316" />
                <rect x="104" y="88" width="18" height="18" rx="4" fill="#F97316" />
                <path d="M160 72h14M158 88l12-7M158 96l12 7" stroke="#F97316" stroke-width="3"
                    stroke-linecap="round" />
                <circle cx="46" cy="70" r="4" fill="#F97316" fill-opacity=".35" />
                <rect x="40" y="88" width="12" height="3" rx="1.5" fill="#F97316" fill-opacity=".35" />
            </svg>

            <h3>{{ $type === 'past' ? 'No past campaigns found.' : 'No active campaigns found.' }}</h3>
            <p>
                @if ($type === 'past')
                    Campaigns move here once their booking period ends.
                @else
                    It looks like you don&rsquo;t have any active campaigns at the moment.
                @endif
            </p>

            @if ($type !== 'past')
                <a href="{{ route('website.search.view') }}" class="btn">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Create New Campaign
                </a>
            @endif
        </div>
    @else
        {{-- CAMPAIGN ACCORDION --}}
        <div class="bi-dash-card">
            <div class="accordion" id="campaignAccordion">

                @foreach ($campaigns as $campaignId => $items)
                    @php
                        $items = $items->sortBy('to_date');
                        $campaignName = $items->first()->campaign_name;

                        if ($type === 'booked') {
                            // $totalAmount = $items->sum(fn($i) => $i->grand_total);
                            $totalAmount = $items->sum(fn($i) => $i->final_amount);
                        } else {
                            $totalAmount = $items->sum(fn($i) => $i->total_price);
                        }
                    @endphp

                    <div class="accordion-item mb-3 shadow-lg ">
                        {{-- HEADER --}}
                        <h2 class="accordion-header" id="heading{{ $campaignId }}">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse{{ $campaignId }}">

                                <div
                                    class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center w-100 gap-2">

                                    {{-- Campaign Name --}}
                                    <strong class="text-primary" style="font-size:17px;">
                                        {{ ucfirst($campaignName) }}
                                    </strong>

                                    {{-- Amount + Status --}}
                                    <div class="d-flex align-items-center gap-2">

                                        <span>
                                            <span
                                                class="badge bg-warning rounded-circle d-inline-flex justify-content-center align-items-center"
                                                style="width:20px; height:20px;">
                                                ₹
                                            </span>
                                            {{ number_format($totalAmount, 2) }}
                                        </span>

                                        @if ($type === 'open')
                                            {{-- <span class="badge bg-success">Running</span> --}}
                                        @elseif ($type === 'booked')
                                            <span class="badge bg-warning">Booked</span>
                                        @else
                                            <span class="badge bg-secondary">Completed</span>
                                        @endif
                                    </div>
                                </div>
                            </button>
                        </h2>
                        {{-- BODY --}}
                        <div id="collapse{{ $campaignId }}" class="accordion-collapse collapse"
                            data-bs-parent="#campaignAccordion">

                            <div class="accordion-body p-0">

                                {{-- ACTION BUTTONS --}}
                                <div class="d-flex justify-content-end gap-2 p-3 border-bottom">
                                    <a href="{{ route('campaign.export.excel', base64_encode($campaignId)) }}"
                                        class="btn btn-success btn-sm">
                                        Export Excel
                                    </a>

                                    <a href="{{ route('campaign.export.ppt', base64_encode($campaignId)) }}"
                                        class="btn btn-warning btn-sm">
                                        Export PPT
                                    </a>

                                    <form action="{{ route('checkout.campaign', base64_encode($campaignId)) }}"
                                        method="POST">
                                        @csrf
                                    </form>

                                </div>
                                <style>
                                    .table-bordered> :not(caption)>*>* {
                                        border-color: #E5E7EB !important;
                                    }

                                    .table-darks {
                                        background: linear-gradient(90deg, #F97316, #F97316) !important;
                                        color: #0F172A !important;
                                        font-weight: 600;
                                    }
                                </style>

                                {{-- ITEMS TABLE --}}
                                <div class="table-responsive p-2">
                                    <table
                                        class="table table-bordered table-hover text-center align-middle mb-0 campaign-table">
                                        <thead class="table-darks">
                                            <tr>
                                                <th>Sr. No.</th>
                                                <th>Name</th>
                                                <th>Hoarding Code</th>
                                                <th>Highway</th>
                                                <th>Landmarks</th>
                                                <th>Facing</th>
                                                <th>Size</th>

                                                @if ($type === 'booked')
                                                    <th>Monthly Price</th>
                                                    <th>Days</th>

                                                    {{-- <th>Per Day</th> --}}
                                                    <th>Total</th>
                                                    <th>GST (18%)</th>
                                                    <th>Final</th>
                                                    <th>Details</th>
                                                @else
                                                    <th>Total Price</th>
                                                    <th>From Date</th>
                                                    <th>To Date</th>
                                                    <th>Booking Days</th>
                                                    <th>Campaign Date</th>
                                                    <th>Details</th>
                                                    <th>Status</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($items as $index => $row)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>
                                                        {{ ucwords(trim(($row->media_title ?? '') . ' ' . ($row->area_name ?? ''))) ?: '-' }}
                                                    </td>
                                                    <td>{{ $row->hoarding_code ?? '-' }}</td>
                                                    <td>{{ $row->highway_name ?? '-' }}</td>
                                                    <td>{{ $row->landmark_names ?? '-' }}</td>
                                                    <td>{{ $row->facing ?? '-' }}</td>
                                                    <td>{{ $row->width }} × {{ $row->height }}</td>

                                                    @if ($type === 'booked')
                                                        <td>₹ {{ number_format($row->price, 2) }}</td>
                                                        <td>{{ $row->total_days }}</td>
                                                        <td>₹ {{ number_format($row->total_price, 2) }}</td>
                                                        {{-- <td>₹ {{ number_format($row->gst_amount, 2) }}</td>
                                                        <td>₹ {{ number_format($row->grand_total, 2) }}</td> --}}
                                                        <td>₹ {{ number_format($row->gst_amount, 2) }}</td>
                                                        <td>₹ {{ number_format($row->final_amount, 2) }}</td>
                                                        <td>
                                                            <a href="{{ route('campaign.details', [
                                                                'cart_item_id' => base64_encode($row->cart_item_id),
                                                            ]) }}"
                                                                class="btn btn-outline-primary btn-sm">
                                                                View
                                                            </a>
                                                        </td>
                                                    @else
                                                        <td>₹ {{ number_format($row->total_price, 2) }}</td>

                                                        <td>{{ $row->from_date ? \Carbon\Carbon::parse($row->from_date)->format('d-m-Y') : '-' }}
                                                        </td>

                                                        <td>{{ $row->to_date ? \Carbon\Carbon::parse($row->to_date)->format('d-m-Y') : '-' }}
                                                        </td>

                                                        <td>{{ $row->total_days ?? '-' }}</td>

                                                        <td>{{ \Carbon\Carbon::parse($row->campaign_date)->format('d M Y') }}
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('campaign.details', [
                                                                'cart_item_id' => base64_encode($row->cart_item_id),
                                                            ]) }}"
                                                                class="btn btn-outline-primary btn-sm">
                                                                View
                                                            </a>
                                                        </td>
                                                        <td>
                                                            @if ($row->is_booked ?? false)
                                                                <span class="badge bg-danger">Other User Booked</span>
                                                            @else
                                                                <span class="badge bg-success">Open</span>
                                                            @endif
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @php
                                        // $hasBookedMedia = $items->contains(fn($i) => $i->is_booked);
                                        $hasBookedMedia = $items->contains(fn($i) => $i->is_booked ?? false);
                                    @endphp

                                    @if ($type === 'open')
                                        <div class="d-flex justify-content-end p-3 border-top">
                                            <form action="{{ route('checkout.campaign', base64_encode($campaignId)) }}"
                                                method="POST">
                                                @csrf

                                                <button type="submit" class="btn btn-primary"
                                                    {{ $hasBookedMedia ? 'disabled' : '' }}>

                                                    {{ $hasBookedMedia ? 'Already Booked By Other User' : 'Place Order' }}
                                                </button>
                                            </form>
                                        </div>
                                    @elseif ($type === 'booked')
                                        <div class="d-flex justify-content-end p-3 border-top">
                                            {{-- booked UI --}}
                                        </div>
                                    @else
                                        <div class="d-flex justify-content-end p-3 border-top">
                                            <button class="btn btn-secondary" disabled>
                                                Campaign Closed
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection

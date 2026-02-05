@extends('website.dashboard.layout')

@section('title', 'Campaign List')

@section('dashboard-content')

    <div class="container-fluid">

        {{-- PAGE TITLE --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            {{-- <h4 class="mb-0">Campaign List</h4> --}}
            <h4 class="mb-0">
                @if ($type === 'open')
                    Campaign List
                @elseif($type === 'booked')
                    Booked Campaign List
                @else
                    Past Campaign List
                @endif
            </h4>

        </div>

        {{-- SEARCH --}}
        <form method="GET" action="{{ url()->current() }}" class="row g-2 mb-4">
            <div class="col-lg-4 col-md-6">
                <input type="text" name="campaign_name" class="form-control" placeholder="Search Campaign Name"
                    value="{{ request('campaign_name') }}">
            </div>
            <div class="col-lg-2 col-md-3">
                <button class="btn btn-primary w-100">Search</button>
            </div>
        </form>
        @if ($campaigns->isEmpty())
            <div class="alert alert-info text-center">
                {{ $type === 'past' ? 'No past campaigns found.' : 'No active campaigns found.' }}
            </div>
        @else
            {{-- CAMPAIGN ACCORDION --}}
            <div class="accordion" id="campaignAccordion">

                @foreach ($campaigns as $campaignId => $items)
                    @php
                        $items = $items->sortBy('to_date');
                        $campaignName = $items->first()->campaign_name;

                        if ($type === 'booked') {
                            $totalAmount = $items->sum(fn($i) => $i->grand_total);
                        } else {
                            $totalAmount = $items->sum(fn($i) => $i->total_price);
                        }
                    @endphp

                    <div class="accordion-item mb-3 shadow-lg ">
                        {{-- HEADER --}}
                        <h2 class="accordion-header" id="heading{{ $campaignId }}">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse{{ $campaignId }}">

                                <div class="d-flex justify-content-between align-items-center w-100">

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
                                        border-color: #dadada !important;
                                    }

                                    .table-darks {
                                        background: linear-gradient(90deg, #ffb300, #ff9800) !important;
                                        color: #000000 !important;
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
                                                <th>Location</th>
                                                <th>Media</th>
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
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($items as $index => $row)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $row->area_name ?? '-' }} {{ $row->facing ?? '-' }}</td>
                                                    <td>{{ $row->media_title ?? '-' }}</td>
                                                    <td>{{ $row->width }} × {{ $row->height }}</td>

                                                    @if ($type === 'booked')
                                                        <td>₹ {{ number_format($row->price, 2) }}</td>
                                                        <td>{{ $row->total_days }}</td>



                                                        {{-- <td>₹ {{ number_format($row->per_day_price, 2) }}</td> --}}

                                                        <td>₹ {{ number_format($row->total_price, 2) }}</td>

                                                        <td>₹ {{ number_format($row->gst_amount, 2) }}</td>

                                                        <td>₹ {{ number_format($row->grand_total, 2) }}</td>
                                                        <td>
                                                            <a href="{{ route('campaign.details', base64_encode($row->cart_item_id)) }}"
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
                                                            <a href="{{ route('campaign.details', base64_encode($row->cart_item_id)) }}"
                                                                class="btn btn-outline-primary btn-sm">
                                                                View
                                                            </a>
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @if ($type === 'open')
                                        <div class="d-flex justify-content-end p-3 border-top">
                                            <form action="{{ route('checkout.campaign', base64_encode($campaignId)) }}"
                                                method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-primary">
                                                    Place Order
                                                </button>
                                            </form>
                                        </div>
                                    @elseif ($type === 'booked')
                                        <div class="d-flex justify-content-end p-3 border-top">
                                            <!-- <button class="btn btn-warning" disabled>
                                                Already Booked
                                            </button> -->
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
        @endif

    </div>

@endsection

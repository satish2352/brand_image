@extends('website.dashboard.layout')

@section('title', 'Campaign List')

@section('dashboard-content')

<div class="container-fluid">

    {{-- PAGE TITLE --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Campaign List</h4>
    </div>

    {{-- SEARCH --}}
    <form method="GET" action="{{ route('campaign.list') }}" class="row g-2 mb-4">
        <div class="col-lg-4 col-md-6">
            <input type="text"
                   name="campaign_name"
                   class="form-control"
                   placeholder="Search Campaign Name"
                   value="{{ request('campaign_name') }}">
        </div>
        <div class="col-lg-2 col-md-3">
            <button class="btn btn-primary w-100">Search</button>
        </div>
    </form>

    @if($campaigns->count() === 0)
        <div class="alert alert-info text-center">
            No campaigns found.
        </div>
    @else

        {{-- CAMPAIGN ACCORDION --}}
        <div class="accordion" id="campaignAccordion">

            @foreach($campaigns as $campaignId => $items)
                @php
                    $campaignName = $items->first()->campaign_name;
                    $totalAmount = $items->sum(fn($i) => $i->total_price);
                @endphp

                <div class="accordion-item mb-3 shadow-sm">

                    {{-- HEADER --}}
                    <h2 class="accordion-header" id="heading{{ $campaignId }}">
                        <button class="accordion-button collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapse{{ $campaignId }}">

                            <div class="d-flex justify-content-between w-100 align-items-center">
                                <strong class="text-primary">
                                    {{ $campaignName }}
                                </strong>

                                <span class="badge bg-dark">
                                    ₹ {{ number_format($totalAmount, 2) }}
                                </span>
                            </div>

                        </button>
                    </h2>

                    {{-- BODY --}}
                    <div id="collapse{{ $campaignId }}"
                         class="accordion-collapse collapse"
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
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        Place Order
                                    </button>
                                </form>
                            </div>

                            {{-- ITEMS TABLE --}}
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover text-center align-middle mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Location</th>
                                            <th>Media</th>
                                            <th>Size</th>
                                            <th>Total Price</th>
                                            <th>From</th>
                                            <th>To</th>
                                            <th>Days</th>
                                            <th>Date</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                    @foreach($items as $index => $row)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $row->common_stdiciar_name ?? '-' }}</td>
                                            <td>{{ $row->media_title ?? '-' }}</td>
                                            <td>{{ $row->width }} × {{ $row->height }}</td>
                                            <td>₹ {{ number_format($row->total_price, 2) }}</td>
                                            <td>{{ $row->from_date ?? '-' }}</td>
                                            <td>{{ $row->to_date ?? '-' }}</td>
                                            <td>{{ $row->total_days ?? '-' }}</td>
                                            <td>{{ \Carbon\Carbon::parse($row->campaign_date)->format('d M Y') }}</td>
                                            <td>
                                                <a href="{{ route('campaign.details', base64_encode($row->cart_item_id)) }}"
                                                   class="btn btn-outline-primary btn-sm">
                                                    View
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
            @endforeach

        </div>
    @endif

</div>

@endsection

@extends('superadm.layout.master')

@section('content')
<div class="container-fluid">

    <a href="{{ route('admin-campaing.list') }}" class="btn btn-secondary mb-3">
        ← Back to Campaign List
    </a>

    <div class="accordion" id="campaignAccordion">

        @foreach($campaigns as $campaignName => $items)

        @php
            $totalAmount = $items->sum('total_price');
        @endphp

        <div class="card mb-3">

            <div class="card-header" id="heading{{ $loop->index }}">
                <h5 class="mb-0 d-flex justify-content-between align-items-center">
                    <button class="btn btn-link" data-toggle="collapse"
                        data-target="#collapse{{ $loop->index }}">
                        {{ $campaignName }}
                    </button>

                    <span class="badge badge-success">
                        ₹ {{ number_format($totalAmount, 2) }}
                    </span>
                </h5>
            </div>

            <div id="collapse{{ $loop->index }}" class="collapse"
                data-parent="#campaignAccordion">

                {{-- EXPORT BUTTONS --}}
                <div class="p-3 text-right">
                    <a href="{{ route('admin.campaign.export.excel', base64_encode($items->first()->campaign_id)) }}"
                    class="btn btn-success btn-sm">Export Excel</a>

                    <a href="{{ route('campaign.export.ppt', base64_encode($items->first()->campaign_id)) }}"
                    class="btn btn-warning btn-sm">Export PPT</a>
                </div>

                {{-- ORDER ITEMS TABLE --}}
                <table class="table table-bordered text-center mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Location</th>
                            <th>Media</th>
                            <th>Size</th>
                            <th>From</th>
                            <th>To</th>
                            {{-- <th>Qty</th> --}}
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>
                    @foreach($items as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row->common_stdiciar_name }}</td>
                            <td>{{ $row->media_title }}</td>
                            <td>{{ $row->width }} × {{ $row->height }}</td>
                            <td>{{ $row->from_date }}</td>
                            <td>{{ $row->to_date }}</td>
                            {{-- <td>{{ $row->qty }}</td> --}}
                            <td>₹ {{ number_format($row->total_price, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

            </div>
        </div>

        @endforeach
    </div>

</div>
@endsection

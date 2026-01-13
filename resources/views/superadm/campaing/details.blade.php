{{-- @extends('superadm.layout.master')

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


                <div class="p-3 text-right">
                    <a href="{{ route('admin.campaign.export.excel', base64_encode($items->first()->campaign_id)) }}"
                    class="btn btn-success btn-sm">Export Excel</a>

                    <a href="{{ route('campaign.export.ppt', base64_encode($items->first()->campaign_id)) }}"
                    class="btn btn-warning btn-sm">Export PPT</a>
                </div>

                <table class="table table-bordered text-center mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Location</th>
                            <th>Media</th>
                            <th>Size</th>
                            <th>From</th>
                            <th>To</th>
                            
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
@endsection --}}
@extends('superadm.layout.master')

@section('content')
<div class="container">

   <a href="{{ route('admin-campaing.list') }}" 
   class="btn btn-secondary mb-3">
   ← Back to Campaign List
</a>

    @if($campaigns->isEmpty())
        <p class="text-muted">No campaign details found</p>
    @else

        @foreach($campaigns->groupBy('campaign_id') as $campaignId => $campaign)
            @php 
                $first = $campaign->first(); 
                $total = $campaign->sum('total_price');
            @endphp

            <div class="card mb-3">
                <div class="card-header bg-light">
                    <strong>{{ $first->campaign_name }}</strong>
                    <span class="badge badge-danger float-right">
                        ₹ {{ number_format($total,2) }}
                    </span>
                </div>

                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Media Title</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Days</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($campaign as $item)
                            <tr>
                                <td>{{ $item->media_title }}</td>
                                <td>{{ \Carbon\Carbon::parse($item->from_date)->format('d M Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($item->to_date)->format('d M Y') }}</td>
                                <td>{{ $item->total_days }}</td>
                                <td>{{ number_format($item->price,2) }}</td>
                                <td>{{ number_format($item->total_price,2) }}</td>
                                <td></td>
                                <?php
                                // dd( $item);
                                ?>
                                <td>
                                    <form action="{{ route('admin.campaign.book') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="campaign_id" value="{{ $item->campaign_id }}">
                                          <input type="hidden" name="user_id" value="{{ $item->user_id }}"> <!-- ADD THIS -->
                                        <input type="hidden" name="media_id" value="{{ $item->media_id }}">
                                        <input type="hidden" name="from_date" value="{{ $item->from_date }}">
                                        <input type="hidden" name="to_date" value="{{ $item->to_date }}">
                                         <input type="hidden" name="price" value="{{ $item->price }}">
<input type="hidden" name="total_price" value="{{ $item->total_price }}">
<input type="hidden" name="total_days" value="{{ $item->total_days }}">
 

                                        <button type="submit" class="btn btn-primary btn-sm">Book</button>
                                    </form>
                                </td>

                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        @endforeach

    @endif
</div>
@endsection

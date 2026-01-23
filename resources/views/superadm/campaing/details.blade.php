@extends('superadm.layout.master')

@section('content')
    <div class="container">

        <a href="{{ route('admin-campaing.list') }}" class="btn btn-secondary mb-3">
            ← Back to Campaign List
        </a>

        @if ($campaigns->isEmpty())
            <p class="text-muted">No campaign details found</p>
        @else
            @foreach ($campaigns->groupBy('campaign_id') as $campaignId => $campaign)
                @php
                    $first = $campaign->first();
                    $total = $campaign->sum('total_price');
                @endphp

                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <strong>{{ $first->campaign_name }}</strong>
                        <span class="badge badge-danger float-right">
                            ₹ {{ number_format($total, 2) }}
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
                                    <th>Monthly Price</th>
                                    <th>Total</th>

                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($campaign as $item)
                                    <tr>
                                        <td>{{ $item->media_title }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->from_date)->format('d M Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->to_date)->format('d M Y') }}</td>
                                        <td>{{ $item->total_days }}</td>
                                        <td>{{ number_format($item->price, 2) }}</td>
                                        <td>{{ number_format($item->total_price, 2) }}</td>

                                        <td>
                                            @if ($item->is_booked)
                                                <span class="badge badge-danger">Booked</span>
                                            @else
                                                <span class="badge badge-success">Available</span>
                                            @endif
                                        </td>

                                        <?php
                                        // dd( $item);
                                        ?>
                                        <td>
                                            <form action="{{ route('admin.campaign.book') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="campaign_id" value="{{ $item->campaign_id }}">
                                                <input type="hidden" name="user_id" value="{{ $item->user_id }}">
                                                <!-- ADD THIS -->
                                                <input type="hidden" name="media_id" value="{{ $item->media_id }}">
                                                <input type="hidden" name="from_date" value="{{ $item->from_date }}">
                                                <input type="hidden" name="to_date" value="{{ $item->to_date }}">
                                                <input type="hidden" name="price" value="{{ $item->price }}">
                                                <input type="hidden" name="total_price" value="{{ $item->total_price }}">
                                                <input type="hidden" name="total_days" value="{{ $item->total_days }}">
                                                <input type="hidden" name="campaign_id" value="{{ $item->campaign_id }}">



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

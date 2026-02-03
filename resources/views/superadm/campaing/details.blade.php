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

                    $subtotal = $campaign->sum('total_price');
                    $gstTotal = round($subtotal * 0.18, 2);
                    $finalTotal = round($subtotal + $gstTotal, 2);
                @endphp

                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <strong>{{ $first->campaign_name }}</strong>
                        <span class="badge badge-danger float-right">
                            ₹ {{ number_format($finalTotal, 2) }}
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



                                    <th>Days</th>
                                    <th>Monthly Price</th>
                                    <th>Per Day</th>
                                    <th>Total</th>
                                    <th>GST (18%)</th>
                                    <th>Final</th>

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
                                        {{-- <td>{{ number_format($item->price, 2) }}</td>
                                        <td>{{ number_format($item->total_price, 2) }}</td> --}}
                                        <td>{{ $item->total_days }}</td>
                                        <td>{{ number_format($item->price, 2) }}</td>
                                        <td>{{ number_format($item->per_day_price ?? $item->total_price / $item->total_days, 2) }}
                                        </td>
                                        <td>{{ number_format($item->total_price, 2) }}</td>

                                        @php
                                            $gst = round($item->total_price * 0.18, 2);
                                            $final = $item->total_price + $gst;
                                        @endphp

                                        <td>{{ number_format($gst, 2) }}</td>
                                        <td><strong>{{ number_format($final, 2) }}</strong></td>

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
                                        <!-- <td>
                                            <form action="{{ route('admin.campaign.book') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="campaign_id" value="{{ $item->campaign_id }}">
                                                <input type="hidden" name="user_id" value="{{ $item->user_id }}">
                                              
                                                <input type="hidden" name="media_id" value="{{ $item->media_id }}">
                                                <input type="hidden" name="from_date" value="{{ $item->from_date }}">
                                                <input type="hidden" name="to_date" value="{{ $item->to_date }}">
                                                <input type="hidden" name="price" value="{{ $item->price }}">
                                                <input type="hidden" name="total_price" value="{{ $item->total_price }}">
                                                <input type="hidden" name="total_days" value="{{ $item->total_days }}">
                                                <input type="hidden" name="campaign_id" value="{{ $item->campaign_id }}">
                                                <input type="hidden" name="per_day_price"
                                                    value="{{ $item->per_day_price ?? 0 }}">




                                                <button type="submit" class="btn btn-primary btn-sm">Book</button>
                                            </form>
                                        </td> -->
                                        <td>
    @if ($item->is_booked)
        <button class="btn btn-secondary btn-sm" disabled>Booked</button>
    @else
        <form action="{{ route('admin.campaign.book') }}" method="POST">
            @csrf
            <input type="hidden" name="campaign_id" value="{{ $item->campaign_id }}">
            <input type="hidden" name="user_id" value="{{ $item->user_id }}">
            <input type="hidden" name="media_id" value="{{ $item->media_id }}">
            <input type="hidden" name="from_date" value="{{ $item->from_date }}">
            <input type="hidden" name="to_date" value="{{ $item->to_date }}">
            <input type="hidden" name="price" value="{{ $item->price }}">
            <input type="hidden" name="total_price" value="{{ $item->total_price }}">
            <input type="hidden" name="total_days" value="{{ $item->total_days }}">
            <input type="hidden" name="per_day_price" value="{{ $item->per_day_price ?? 0 }}">

            <button type="submit" class="btn btn-primary btn-sm">Book</button>
        </form>
    @endif
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

@extends('website.layout')

@section('title', 'My Campaigns')

@section('content')
<div class="container my-5">

    <h3 class="mb-4">Campaigns</h3>

    @if($campaigns->count() === 0)
        <div class="alert alert-info">
            No campaigns found.
        </div>
    @else

    <table class="table table-bordered text-center align-middle">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Campaign Name</th>
                <th>Media</th>
                <th>Size</th>
                <th>Price</th>
                <th>Qty</th>
                <th>Total</th>
                <th>Date</th>
            </tr>
        </thead>

        <tbody>
        @foreach($campaigns as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>

                <td>{{ $row->campaign_name }}</td>

                <td>{{ $row->media_title ?? '-' }}</td>

                <td>{{ $row->width }} × {{ $row->height }}</td>

                <td>₹ {{ number_format($row->price, 2) }}</td>

                <td>{{ $row->qty }}</td>

                <td>
                    ₹ {{ number_format($row->price * $row->qty, 2) }}
                </td>

                <td>{{ \Carbon\Carbon::parse($row->campaign_date)->format('d M Y') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @endif

</div>
@endsection

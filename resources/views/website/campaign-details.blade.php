@extends('website.layout')

@section('title', 'Campaign Details')

@section('content')
<div class="container my-5">

    <h3 class="mb-4">
        Campaign:
        <strong>{{ $campaign->first()->campaign_name }}</strong>
    </h3>

    <table class="table table-bordered text-center align-middle">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Image</th>
                <th>Media</th>
                <th>Size</th>
                <th>Price</th>
                {{-- <th>Qty</th> --}}
                {{-- <th>Total</th> --}}
                <th>Date</th>
            </tr>
        </thead>

        <tbody>
        @foreach($campaign as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>

                <td>
                    @if($row->images)
                       
                             <img src="{{ config('fileConstants.IMAGE_VIEW') . $row->images }}"
                             class="img-fluid rounded"
                             style="height:150px; width:100%; object-fit:cover;">
                    @else
                        —
                    @endif
                </td>

                <td>{{ $row->media_title ?? '-' }}</td>

                <td>{{ $row->width }} × {{ $row->height }}</td>

                <td>₹ {{ number_format($row->price, 2) }}</td>

                {{-- <td>{{ $row->qty }}</td> --}}

                {{-- <td>₹ {{ number_format($row->price * $row->qty, 2) }}</td> --}}

                <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d M Y') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="mt-3">
        <a href="{{ route('campaign.list') }}" class="btn btn-secondary">
            ← Back to Campaigns
        </a>
    </div>

</div>
@endsection

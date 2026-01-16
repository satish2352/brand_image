@extends('website.layout')

@section('title', 'Campaign Details')

@section('content')

    <!-- breadcrumb-section -->
    <div class="breadcrumb-section breadcrumb-bg">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2 text-center">
                    <div class="breadcrumb-text">
                        <p>Campaign details</p>
                        <h1>Campaign</h1>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end breadcrumb section -->

    <div class="container my-5">

        <h3 class="mb-4">
            Campaign:
            <strong>{{ $campaign->first()->campaign_name }}</strong>
        </h3>

        @foreach ($campaign as $row)
            <div class="card shadow-sm border-0 mb-4">
                <div class="row g-0 align-items-center">

                    {{-- IMAGE LEFT --}}
                    <div class="col-md-4">
                        @if ($row->images)
                            <img src="{{ config('fileConstants.IMAGE_VIEW') . $row->images }}"
                                class="img-fluid rounded-start" style="height:100%; min-height:260px; object-fit:cover;"
                                alt="Media Image">
                        @endif
                    </div>

                    {{-- DETAILS RIGHT --}}
                    <div class="col-md-8">
                        <div class="card-body">

                            <h5 class="card-title mb-2">
                                {{ $row->media_title ?? 'Media' }}
                            </h5>

                            <h6 class="text-success fw-bold mb-3">
                                ₹ {{ number_format($row->total_price, 2) }}
                            </h6>

                            <div class="row">
                                <div class="col-sm-6 mb-2">
                                    <strong>Size:</strong><br>
                                    {{ $row->width }} × {{ $row->height }}
                                </div>

                                <div class="col-sm-6 mb-2">
                                    <strong>Total Days:</strong><br>
                                    {{ $row->total_days ?? '-' }}
                                </div>

                                <div class="col-sm-6 mb-2">
                                    <strong>From Date:</strong><br>
                                    {{ $row->from_date ? \Carbon\Carbon::parse($row->from_date)->format('d-m-Y') : '-' }}
                                </div>

                                <div class="col-sm-6 mb-2">
                                    <strong>To Date:</strong><br>
                                    {{ $row->to_date ? \Carbon\Carbon::parse($row->to_date)->format('d-m-Y') : '-' }}
                                </div>
                            </div>

                            <p class="text-muted mt-3 mb-0">
                                <small>
                                    Added on {{ \Carbon\Carbon::parse($row->created_at)->format('d M Y') }}
                                </small>
                            </p>

                        </div>
                    </div>

                </div>
            </div>
        @endforeach

        <div class="mt-4">
            <a href="{{ route('campaign.list') }}" class="btn btn-secondary">
                ← Back to Campaigns
            </a>
        </div>

    </div>
@endsection

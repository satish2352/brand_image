@extends('website.layout')

@section('title', 'Campaign Details')

@section('content')

<!-- breadcrumb-section -->
<div class="breadcrumb-section">
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



    @foreach ($campaign as $row)
    <div class="card border-0 mb-4">
        <div class="row g-0 align-items-center">

            {{-- IMAGE LEFT --}}
            <div class="col-md-5 single-product-img">
                @if ($row->images)
                <img src="{{ config('fileConstants.IMAGE_VIEW') . $row->images }}"
                    class="img-fluid rounded-start" style="height:100%; min-height:360px; object-fit:cover;"
                    alt="Media Image">
                @endif
            </div>

            {{-- DETAILS RIGHT --}}
            <div class="col-md-7">
                <h3 class="mb-4">
                    Campaign
                    <span style="color: #2b64b5;">  : {{ $campaign->first()->campaign_name }}</span>
                </h3>
                <div class="card-body">

                    <h5 class="card-title mb-2">
                        {{ $row->media_title ?? 'Media' }}
                    </h5>

                    <h6 class="text-success fw-bold mb-3">
                        ₹ {{ number_format($row->total_price, 2) }}
                    </h6>
                    <div class="row g-2">

                        <div class="col-sm-6">
                            <div class="row">
                                <div class="col-3"><strong>Size</strong></div>
                                <div class="col-7"> : {{ $row->width }} × {{ $row->height }}</div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="row">
                                <div class="col-3"><strong>Total Days</strong></div>
                                <div class="col-7"> : {{ $row->total_days ?? '-' }}</div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="row">
                                <div class="col-3"><strong>From Date</strong></div>
                                <div class="col-7">
                                    : {{ $row->from_date ? \Carbon\Carbon::parse($row->from_date)->format('d-m-Y') : '-' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="row">
                                <div class="col-3"><strong>To Date</strong></div>
                                <div class="col-7">
                                    : {{ $row->to_date ? \Carbon\Carbon::parse($row->to_date)->format('d-m-Y') : '-' }}
                                </div>
                            </div>
                        </div>

                    </div>


                    <p class="text-muted mt-3 mb-0">
                        <small>
                            Added on {{ \Carbon\Carbon::parse($row->created_at)->format('d M Y') }}
                        </small>
                    </p>
                    <div class="mt-4">

                        <a href="{{ route('campaign.list') }}" class="cart-btn"><i class="fas fa-shopping-cart"></i>← Back to Campaigns</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
    @endforeach



</div>
@endsection
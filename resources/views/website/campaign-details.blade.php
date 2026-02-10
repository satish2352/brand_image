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

                        @php
                            $images = $row->images ? explode(',', $row->images) : [];
                        @endphp

                        @if (count($images) > 0)
                            <div id="mediaSlider{{ $loop->index }}" class="carousel slide" data-bs-ride="carousel">

                                {{-- Indicators --}}
                                <div class="carousel-indicators">
                                    @foreach ($images as $i => $img)
                                        <button type="button" data-bs-target="#mediaSlider{{ $loop->parent->index }}"
                                            data-bs-slide-to="{{ $i }}" class="{{ $i == 0 ? 'active' : '' }}">
                                        </button>
                                    @endforeach
                                </div>

                                {{-- Slides --}}
                                <div class="carousel-inner">
                                    @foreach ($images as $i => $img)
                                        <div class="carousel-item {{ $i == 0 ? 'active' : '' }}">
                                            <img src="{{ config('fileConstants.IMAGE_VIEW') . trim($img) }}"
                                                class="d-block w-100 rounded" style="height:360px; object-fit:cover;"
                                                alt="Media Image">
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Controls --}}
                                <button class="carousel-control-prev" type="button"
                                    data-bs-target="#mediaSlider{{ $loop->index }}" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                </button>

                                <button class="carousel-control-next" type="button"
                                    data-bs-target="#mediaSlider{{ $loop->index }}" data-bs-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                </button>

                            </div>
                        @else
                            <img src="{{ asset('no-image.png') }}" class="img-fluid rounded"
                                style="height:360px; object-fit:cover;">
                        @endif

                    </div>


                    {{-- DETAILS RIGHT --}}
                    <div class="col-md-7">

                        <div class="card-body">
                            <h3 class="mb-1 ">
                                Campaign Name 
                                <span> : {{ $campaign->first()->campaign_name }}</span>
                            </h3>
                            <p class="mb-3 " style="color: #2b64b5;">
                                {{ $row->media_title ?? 'Media' }}
                            </p>

                            <h5 class="card-title mb-2">
                                <i class="fas fa-map-marker-alt text-danger"></i>
                                {{ $row->area_name }}, {{ $row->city_name }}
                            </h5>


                            <h6 class="text-success fw-bold mb-3">
                                ₹ {{ number_format($row->total_price, 2) }}
                            </h6>
                            <div class="row g-2">

                                <div class="col-sm-6">
                                    <div class="row">
                                        <div class="col-4"><strong>Facing</strong></div>
                                        <div class="col-7"> : {{ $row->facing }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="row">
                                        <div class="col-4"><strong>Illumination</strong></div>
                                        <div class="col-7"> : {{ $row->illumination_name }}</div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="row">
                                        <div class="col-4"><strong>Size</strong></div>
                                        <div class="col-7"> : {{ $row->width }} × {{ $row->height }}</div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="row">
                                        <div class="col-4"><strong>Total Days</strong></div>
                                        <div class="col-7"> : {{ $row->total_days ?? '-' }}</div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="row">
                                        <div class="col-4"><strong>From Date</strong></div>
                                        <div class="col-7">
                                            :
                                            {{ $row->from_date ? \Carbon\Carbon::parse($row->from_date)->format('d-m-Y') : '-' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="row">
                                        <div class="col-4"><strong>To Date</strong></div>
                                        <div class="col-7">
                                            :
                                            {{ $row->to_date ? \Carbon\Carbon::parse($row->to_date)->format('d-m-Y') : '-' }}
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
    <a href="{{ url()->previous() }}" class="cart-btn">
        <i class="fas fa-arrow-left"></i> Back
    </a>
</div>

                            {{-- <div class="mt-4">

                                <a href="{{ route('campaigns.open') }}" class="cart-btn"><i
                                        class="fas fa-shopping-cart"></i>← Back to Campaigns</a>
                            </div> --}}
                        </div>
                    </div>

                </div>
            </div>
        @endforeach



    </div>
@endsection

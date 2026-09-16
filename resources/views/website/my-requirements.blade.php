@extends('website.layout')

@section('title', 'My Requirements')

@section('content')
    <div class="container-fluid about-banner-img g-0">
        <div class="row g-0">
            <div class="col-md-12 d-none d-md-block">
                <img src="{{ asset('assets/img/media_desk.png') }}" alt="Brand Adda" class="img-fluid w-100">
            </div>
            <div class="col-md-12 d-block d-md-none">
                <img src="{{ asset('assets/img/media_mobile.png') }}" alt="Brand Adda" class="img-fluid w-100">
            </div>
        </div>
    </div>

    <section class="req-section">
        <div class="container">

            <div class="req-head">
                <span class="bi-eyebrow">Your briefs</span>
                <h1>My Requirements</h1>
                <p>
                    Everything you have sent our team, and where each one stands.
                </p>
            </div>

            @if ($requirements->isEmpty())
                <div class="bi-media-empty">
                    <i class="bi bi-clipboard" aria-hidden="true"></i>
                    <h4>You have not sent us a requirement yet</h4>
                    <p>
                        Tell us what you are looking for and our media team will put a
                        plan together for you.
                    </p>
                    <a href="{{ route('website.requirement.create') }}" class="bi-media-empty-btn">
                        Share Your Requirement
                    </a>
                </div>
            @else
                <div class="req-card">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 my-req-table">
                            <thead>
                                <tr>
                                    <th>Submitted</th>
                                    <th>Campaign</th>
                                    <th>City</th>
                                    <th>Media Type</th>
                                    <th>Dates</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requirements as $r)
                                    <tr>
                                        <td>{{ $r->created_at?->format('d M Y') }}</td>
                                        <td>{{ $r->campaign_name ?: '—' }}</td>
                                        <td>{{ $r->city }}</td>
                                        <td>{{ $r->media_type }}</td>
                                        <td>
                                            {{ $r->campaign_start_date?->format('d-m-Y') }}
                                            &ndash;
                                            {{ $r->campaign_end_date?->format('d-m-Y') }}
                                        </td>
                                        <td>
                                            <span class="my-req-status is-{{ $r->status }}">
                                                {{ $r->statusLabel() }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="req-actions">
                        <a href="{{ url('/contact-us') }}" class="sa-btn sa-btn-ghost">
                            <i class="bi bi-headset" aria-hidden="true"></i> Contact Brand Adda Team
                        </a>
                        <a href="{{ route('website.requirement.create') }}" class="sa-btn sa-btn-primary">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> Share New Requirement
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </section>
@endsection

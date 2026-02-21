@extends('website.layout')

@section('title', 'My Dashboard')

@section('content')

<!-- breadcrumb-section -->

<!-- end breadcrumb section -->
<div class="container-fluid about-banner-img g-0">
    <div class="row">
        <!-- Desktop Image -->
        <div class="col-md-12 d-none d-md-block">
            <img src="{{ asset('assets/img/campaindetail.png') }}" alt="About Banner" class="img-fluid" style="width: inherit !important;">
        </div>

        <!-- Mobile Image -->
        <div class="col-md-12 d-block d-md-none">
            <img src="{{ asset('assets/img/mobile_campain_page.png') }}" alt="About Banner" class="img-fluid">
        </div>
    </div>
    <div class="dashboard-wrapper container my-5">
        <div class="row g-4">

            {{-- ================= SIDEBAR ================= --}}
            <div class="col-lg-3">
                <div class="dashboard-sidebar">
                    {{-- <h5 class="sidebar-title">My Dashboard</h5> --}}

                    <ul class="sidebar-menu" id="dashboardMenu">
                        <li>
                            <a href="{{ route('campaign.list') }}" data-menu="campaigns">
                                <i class="bi bi-megaphone"></i>
                                Campaign List
                            </a>
                        </li>

                        <li>
                            <a href="#payments" data-menu="payments">
                                <i class="bi bi-receipt"></i>
                                Invoice & Payments
                            </a>
                        </li>

                        {{-- <li>
                        <a href="#profile" data-menu="profile">
                            <i class="bi bi-person"></i>
                            Update Details
                        </a>
                    </li> --}}
                    </ul>
                </div>
            </div>

            {{-- ================= CONTENT ================= --}}
            <div class="col-lg-9">
                <div class="dashboard-content">

                    <h4 class="mb-3">Welcome 👋</h4>
                    <p class="text-muted mb-4">
                        Manage your campaigns, payments and profile details here.
                    </p>


                </div>
            </div>

        </div>
    </div>


    @endsection
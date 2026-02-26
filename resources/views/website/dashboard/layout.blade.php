@extends('website.layout')

@section('title', 'My Dashboard')

@section('content')

    <style>
        /* Parent active style */
        .dashboard-sidebar>ul>li.active>a {
            background: linear-gradient(90deg, #ffb300, #ff9800);
            color: #000;
        }

        /* ONLY child active highlight */
        .dashboard-sidebar ul ul a.active {
            background: linear-gradient(90deg, #ffb300, #ff9800);
            color: #000;
        }

        .sidebar-menu li.active a {
            background: transparent;
        }
    </style>
    <!-- breadcrumb-section -->
    <!-- end breadcrumb section -->
    <div class="container-fluid about-banner-img g-0">
        <div class="row g-0">
            <!-- Desktop Image -->
            <div class="col-md-12 d-none d-md-block">
                <img src="{{ asset('assets/img/campaindetail.png') }}" alt="About Banner" class="img-fluid">
            </div>

            <!-- Mobile Image -->
            <div class="col-md-12 d-block d-md-none">
                <img src="{{ asset('assets/img/mobile_campain_page.png') }}" alt="About Banner" class="img-fluid">
            </div>
        </div>
        <div class="dashboard-wrapper container my-5">
            <div class="row g-4">
                {{-- SIDEBAR --}}
                <div class="col-lg-3">
                    <div class="dashboard-sidebar">
                        <h5 class="sidebar-title">My Dashboard</h5>
                        <ul class="sidebar-menu">
                            <li class="{{ request()->routeIs('campaigns.*') ? 'active' : '' }}">
                                <a data-bs-toggle="collapse" href="#campaignMenu">
                                    <i class="bi bi-megaphone"></i> Campaign List
                                </a>

                                <ul class="collapse ps-3 mt-3 {{ request()->routeIs('campaigns.*') ? 'show' : '' }}"
                                    id="campaignMenu">

                                    <li>
                                        <a href="{{ route('campaigns.open') }}"
                                            class="{{ request()->routeIs('campaigns.open') ? 'active' : '' }}">
                                            List Campaigns
                                        </a>
                                    </li>

                                    <li>
                                        <a href="{{ route('campaigns.booked') }}"
                                            class="{{ request()->routeIs('campaigns.booked') ? 'active' : '' }}">
                                            Booked Campaigns
                                        </a>
                                    </li>

                                    <li>
                                        <a href="{{ route('campaigns.past') }}"
                                            class="{{ request()->routeIs('campaigns.past') ? 'active' : '' }}">
                                            Past Campaigns
                                        </a>
                                    </li>

                                </ul>
                            </li>

                            <li>
                                <a href="{{ route('campaign.payment.history') }}">
                                    <i class="bi bi-receipt"></i> Payment History
                                </a>
                            </li>

                        </ul>


                    </div>
                </div>

                {{-- RIGHT CONTENT --}}
                <div class="col-lg-9">
                    <div class="dashboard-content">
                        @yield('dashboard-content')
                    </div>
                </div>

            </div>
        </div>

    @endsection

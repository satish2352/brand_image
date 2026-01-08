@extends('website.layout')

@section('title', 'My Dashboard')

@section('content')

<style>
    /* Parent active style */
.dashboard-sidebar > ul > li.active > a {
    background: linear-gradient(90deg, #ffb300, #ff9800);
    color: #000;
}

/* ONLY child active highlight */
.dashboard-sidebar ul ul a.active {
    background: linear-gradient(90deg, #ffb300, #ff9800);
    color: #000;
}
.sidebar-menu li.active a{
    background: transparent;
}
</style>

@php
    $isCampaignPage = request()->routeIs('campaign.list');
    $campaignType  = $isCampaignPage
        ? request()->get('type', 'active')
        : null;
@endphp

	<!-- breadcrumb-section -->
	<div class="breadcrumb-section breadcrumb-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="breadcrumb-text">
						<p>Read the Details</p>
						<h1>Dashboard</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

<div class="dashboard-wrapper container my-5">
    <div class="row g-4">

        {{-- SIDEBAR --}}
        <div class="col-lg-3">
            <div class="dashboard-sidebar">
                <h5 class="sidebar-title">My Dashboard</h5>

                <ul class="sidebar-menu">
                    <li class="{{ request()->routeIs('dashboard.home') ? 'active' : '' }}">
                        <a href="{{ route('dashboard.home') }}">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>

                    {{-- <li class="{{ request()->routeIs('campaign.list') ? 'active' : '' }}">
                        <a href="{{ route('campaign.list') }}">
                            <i class="bi bi-megaphone"></i> Campaign List
                        </a>
                    </li> --}}
                    <li class="{{ request()->routeIs('campaign.*') ? 'active' : '' }}">
                        <a data-bs-toggle="collapse" href="#campaignMenu" role="button">
                            <i class="bi bi-megaphone"></i> Campaign List
                        </a>

                        <ul class="collapse ps-3 mt-3 {{ request()->routeIs('campaign.list') ? 'show' : '' }}"
                            id="campaignMenu">

                            <li>
                                <a href="{{ route('campaign.list', ['type' => 'active']) }}"
                                    class="{{ $isCampaignPage && $campaignType === 'active' ? 'active' : '' }}">
                                        Active Campaign List
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('campaign.list', ['type' => 'past']) }}"
                                    class="{{ $isCampaignPage && $campaignType === 'past' ? 'active' : '' }}">
                                        Past Campaign List
                                </a>
                            </li>

                        </ul>
                    </li>
               <li class="{{ request()->routeIs('campaign.payment.history') ? 'active' : '' }}">
                        <a href="{{ route('campaign.payment.history') }}">
                            <i class="bi bi-megaphone"></i> Payment History
                        </a>
                    </li>

                    <li class="{{ request()->routeIs('dashboard.profile') ? 'active' : '' }}">
                        <a href="{{ route('dashboard.profile') }}">
                            <i class="bi bi-person"></i> Update Details
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

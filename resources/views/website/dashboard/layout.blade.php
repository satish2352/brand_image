@extends('website.layout')

@section('title', 'My Dashboard')

@section('content')

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

                    <li class="{{ request()->routeIs('campaign.list') ? 'active' : '' }}">
                        <a href="{{ route('campaign.list') }}">
                            <i class="bi bi-megaphone"></i> Campaign List
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

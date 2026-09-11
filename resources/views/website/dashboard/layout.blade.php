@extends('website.layout')

@section('title', 'My Dashboard')

@section('content')

    {{-- ===== HERO BANNER =====
         The full-bleed strip that opens this page. --}}
    <div class="container-fluid about-banner-img g-0">
        <div class="row g-0">
            <!-- Desktop Image -->
            <div class="col-md-12 d-none d-md-block">
                <img src="{{ asset('assets/img/cap_hero.png') }}" alt="Campaign banner" class="img-fluid">
            </div>

            <!-- Mobile Image -->
            <div class="col-md-12 d-block d-md-none">
                <img src="{{ asset('assets/img/cap_hero_mob.png') }}" alt="Campaign banner" class="img-fluid w-100">
            </div>
        </div>
    </div>

    {{-- The campaign artwork sits on the section behind the cards, veiled back
         to a backdrop: at full strength it competes with the banner above,
         which is the same scene. --}}
    <section class="bi-dash">
        <div class="container">
            <div class="row g-4">

                {{-- ================= SIDEBAR ================= --}}
                <div class="col-lg-3">
                    <aside class="bi-dash-side">

                        <div class="bi-dash-side-head">
                            <span class="bi-dash-side-ico" aria-hidden="true">
                                <i class="bi bi-grid-1x2-fill"></i>
                            </span>
                            <div>
                                <span class="bi-dash-side-title">My Dashboard</span>
                                <span class="bi-dash-side-sub">Manage your campaigns</span>
                            </div>
                        </div>

                        <ul class="bi-dash-nav">
                            {{-- The parent is the group's toggle, as before: it opens the
                                 three campaign views and lights up whenever one of them
                                 is the current page. --}}
                            <li class="{{ request()->routeIs('campaigns.*') ? 'is-active' : '' }}">
                                <a data-bs-toggle="collapse" href="#campaignMenu"
                                    aria-expanded="{{ request()->routeIs('campaigns.*') ? 'true' : 'false' }}">
                                    <i class="bi bi-megaphone-fill" aria-hidden="true"></i>
                                    <span>Campaign List</span>
                                    <i class="bi bi-chevron-right bi-dash-nav-caret" aria-hidden="true"></i>
                                </a>

                                <ul class="collapse {{ request()->routeIs('campaigns.*') ? 'show' : '' }}"
                                    id="campaignMenu">
                                    <li>
                                        <a href="{{ route('campaigns.open') }}"
                                            class="{{ request()->routeIs('campaigns.open') ? 'is-current' : '' }}">
                                            <i class="bi bi-list-ul" aria-hidden="true"></i>
                                            <span>List Campaigns</span>
                                            <i class="bi bi-chevron-right bi-dash-nav-caret" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('campaigns.booked') }}"
                                            class="{{ request()->routeIs('campaigns.booked') ? 'is-current' : '' }}">
                                            <i class="bi bi-calendar-check" aria-hidden="true"></i>
                                            <span>Booked Campaigns</span>
                                            <i class="bi bi-chevron-right bi-dash-nav-caret" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('campaigns.past') }}"
                                            class="{{ request()->routeIs('campaigns.past') ? 'is-current' : '' }}">
                                            <i class="bi bi-clock-history" aria-hidden="true"></i>
                                            <span>Past Campaigns</span>
                                            <i class="bi bi-chevron-right bi-dash-nav-caret" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li class="{{ request()->routeIs('campaign.payment.history') ? 'is-active' : '' }}">
                                <a href="{{ route('campaign.payment.history') }}">
                                    <i class="bi bi-credit-card-2-front" aria-hidden="true"></i>
                                    <span>Payment History</span>
                                    <i class="bi bi-chevron-right bi-dash-nav-caret" aria-hidden="true"></i>
                                </a>
                            </li>
                        </ul>

                        <div class="bi-dash-promo">
                            <span class="bi-dash-promo-ico" aria-hidden="true">
                                <i class="bi bi-bar-chart-fill"></i>
                            </span>
                            <div>
                                <strong>Grow Your Brand</strong>
                                <span>Manage, track and analyze your OOH campaigns easily.</span>
                            </div>
                        </div>

                    </aside>
                </div>

                {{-- ================= CONTENT ================= --}}
                <div class="col-lg-9">

                    {{-- The header sits on the artwork rather than in a card, so the
                         skyline reads behind it the way the reference does. --}}
                    <div class="bi-dash-head">
                        <nav class="bi-dash-crumb" aria-label="Breadcrumb">
                            <a href="{{ route('campaigns.open') }}">Dashboard</a>
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            <span>@yield('dashboard-title', 'My Dashboard')</span>
                        </nav>

                        <h1 class="bi-dash-title">@yield('dashboard-title', 'My Dashboard')</h1>
                        <p class="bi-dash-sub">
                            @yield('dashboard-subtitle', 'Manage your campaigns, payments and profile details here.')
                        </p>
                    </div>

                    @yield('dashboard-content')
                </div>

            </div>
        </div>
    </section>

@endsection

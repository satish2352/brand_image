@extends('superadm.layout.master')

@section('content')
    <!-- ============================================================== -->
    <!-- Start Page Content -->
    <!-- ============================================================== -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class="row">
                        <!-- Roles Card -->
                        <div class="col-lg-3 col-md-6 mb-4">
                            <a href="{{ route('area.list') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-role">
                                        <div class="d-flex flex-row">
                                            <div
                                                class="round round-lg text-white d-inline-block text-center rounded-circle bg-dashboard-info">
                                              <i class="mdi mdi-account-key mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h3 class="mb-0 font-weight-light-new new-font-size">
                                                    Area <strong>{{ $allArea }}</strong>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Designations Card -->
                        <div class="col-lg-3 col-md-6 mb-4">
                            <a href="{{ route('media.list') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-designation">
                                        <div class="d-flex flex-row">
                                            <div
                                                class="round round-lg text-white d-inline-block text-center rounded-circle bg-dashboard-info">
                                                <i class="mdi mdi-id-card mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h3 class="mb-0 font-weight-light-new new-font-size">
                                                    Media <strong>{{ $allMediaManagement }}</strong>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Plants Card -->
                        <div class="col-lg-3 col-md-6 mb-4">
                            <a href="{{ route('media.list') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-plants ">
                                        <div class="d-flex flex-row">
                                            <div
                                                class="round round-lg text-white d-inline-block text-center rounded-circle bg-dashboard-info">
                                               <i class="mdi mdi-factory mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h3 class="mb-0 font-weight-light-new new-font-size">
                                                    Category <strong>{{ $allCategory }}</strong>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>


                          <!-- Plants Card -->
                        <div class="col-lg-3 col-md-6 mb-4">
                            <a href="{{ route('media.list') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-projects">
                                        <div class="d-flex flex-row">
                                            <div
                                                class="round round-lg text-white d-inline-block text-center rounded-circle bg-dashboard-info">
                                                <i class="mdi mdi-clipboard-text mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h3 class="mb-0 font-weight-light-new new-font-size">
                                                    Facing Direction <strong>{{ $allFacingDirection }}</strong>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>


                          <!-- Plants Card -->
                        <div class="col-lg-3 col-md-6 mb-4">
                            <a href="{{ route('media.list') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-department">
                                        <div class="d-flex flex-row">
                                            <div
                                                class="round round-lg text-white d-inline-block text-center rounded-circle bg-dashboard-info">
                                               <i class="mdi mdi-domain mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h3 class="mb-0 font-weight-light-new new-font-size">
                                                    Illumination <strong>{{ $allIllumination }}</strong>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Contact us Card -->
                        <div class="col-lg-3 col-md-6 mb-4">
                            <a href="{{ route('contact-us.list') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-department">
                                        <div class="d-flex flex-row">
                                            <div
                                                class="round round-lg text-white d-inline-block text-center rounded-circle bg-dashboard-info">
                                                <i class="mdi mdi-email-outline mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h3 class="mb-0 font-weight-light-new new-font-size">
                                                    Contact Enquiry
                                                    <strong>{{ $latestContactCount }}</strong>
                                                </h3>
                                                <small>Last 15 Days</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Booking count Card -->
                        <div class="col-lg-3 col-md-6 mb-4">
                            <a href="{{ route('admin.booking.list-booking') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-projects">
                                        <div class="d-flex flex-row">
                                            <div
                                                class="round round-lg text-white d-inline-block text-center rounded-circle bg-dashboard-info">
                                                <i class="mdi mdi-calendar-check mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h3 class="mb-0 font-weight-light-new new-font-size">
                                                    Bookings
                                                    <strong>{{ $latestBookingCount }}</strong>
                                                </h3>
                                                <small>Last 15 Days</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        {{-- Monthly booking Revenue count --}}
                        <div class="col-lg-3 col-md-6 mb-4">
                            <a href="{{ route('reports.revenue.index') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-department">
                                        <div class="d-flex flex-row">
                                            <div class="round round-lg text-white text-center rounded-circle bg-dashboard-info">
                                                <i class="mdi mdi-cash mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h4 class="mb-0">
                                                    {{-- ₹ {{ formatAmountShort($monthlyRevenue) }} --}}
                                                </h4>
                                                <small>This Month Revenue</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        {{-- Yearly booking Revenue count --}}
                        <div class="col-lg-3 col-md-6 mb-4">
                            <a href="{{ route('reports.revenue.index') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-department">
                                        <div class="d-flex flex-row">
                                            <div class="round round-lg text-white text-center rounded-circle bg-dashboard-info">
                                                <i class="mdi mdi-chart-line mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h4 class="mb-0">
                                                    {{-- ₹ {{ formatAmountShort($yearlyRevenue) }} --}}
                                                </h4>
                                                <small>This Year Revenue</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        {{-- ONGOING CAMPAIGNS --}}
                        <div class="col-lg-3 col-md-6 mb-4">
                            <a href="{{ route('admin-campaing.list') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-department">
                                        <div class="d-flex flex-row">
                                            <div class="round round-lg text-white text-center rounded-circle bg-dashboard-info">
                                                <i class="mdi mdi-bullhorn mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h3 class="mb-0 font-weight-bold">
                                                    {{ $ongoingCampaignCount }}
                                                </h3>
                                                <small>Ongoing Campaigns</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>


                        {{-- CATEGORY WISE MEDIA COUNT --}}
                        <div class="col-12 mt-4">
                            <h4 class="mb-3">Media Count by Category</h4>
                        </div>

                        @foreach ($categoryMediaCounts as $cat)
                            <div class="col-lg-3 col-md-6 mb-4">
                                <a href="{{ route('media.list') }}" class="text-decoration-none text-dark">
                                    <div class="card shadow-sm card-radius h-80">
                                        <div class="card-body bg-plants">
                                            <div class="d-flex flex-row">
                                                <div class="round round-lg text-white text-center rounded-circle bg-dashboard-info">
                                                    <i class="mdi mdi-monitor-multiple mdi-36px icon-padding"></i>
                                                </div>
                                                <div class="ml-2 align-self-center">
                                                    <h4 class="mb-0 font-weight-bold">
                                                        {{ $cat->media_count }}
                                                    </h4>
                                                    <small>{{ $cat->category_name }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach

                          <!-- Plants Card -->
                        {{-- <div class="col-lg-3 col-md-6 mb-4">
                            <a href="{{ route('employees.list') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-employees ">
                                        <div class="d-flex flex-row">
                                            <div
                                                class="round round-lg text-white d-inline-block text-center rounded-circle bg-dashboard-info">
                                                <i class="mdi mdi-account-group mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h3 class="mb-0 font-weight-light-new new-font-size">
                                                    Employees <strong>{{ $allEmployees }}</strong>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div> --}}

                        <!-- Employee Types Card -->
                        {{-- <div class="col-lg-3 col-md-6 mb-4">
                            <a href="{{ route('employee-types.list') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-employee-type">
                                        <div class="d-flex flex-row">
                                            <div class="round round-lg text-white d-inline-block text-center rounded-circle bg-dashboard-info">
                                                <i class="mdi mdi-account-tie mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h3 class="mb-0 font-weight-light-new new-font-size">
                                                    Employee Types <strong>{{ $allEmployeeTypes }}</strong>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div> --}}

                        <!-- Financial Years -->
                        {{-- <div class="col-lg-3 col-md-6 mb-4">
                            <a href="{{ route('financial-year.list') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-financial">
                                        <div class="d-flex flex-row">
                                            <div class="round round-lg text-white d-inline-block text-center rounded-circle bg-dashboard-info">
                                                <i class="mdi mdi-account-tie mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h3 class="mb-0 font-weight-light-new new-font-size">
                                                    Financial Years <strong>{{ $allfinancialyears }}</strong>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div> --}}

                        <!-- Employee Assignments Card -->
                        {{-- <div class="col-lg-3 col-md-6 mb-4">
                            <a href="{{ route('employee.assignments.list') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-assignment">
                                        <div class="d-flex flex-row">
                                            <div class="round round-lg text-white d-inline-block text-center rounded-circle bg-dashboard-info">
                                                <i class="mdi mdi-account-switch mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h3 class="mb-0 font-weight-light-new new-font-size">
                                                    Assigned Employees <strong>{{ $allEmployeeAssignments }}</strong>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div> --}}

                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- End of Page Content -->
    <!-- ============================================================== -->
@endsection

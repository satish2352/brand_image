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
                            <a href="{{ route('roles.list') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-role">
                                        <div class="d-flex flex-row">
                                            <div
                                                class="round round-lg text-white d-inline-block text-center rounded-circle bg-dashboard-info">
                                              <i class="mdi mdi-account-key mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h3 class="mb-0 font-weight-light-new new-font-size">
                                                    Roles <strong>{{ $allRoles }}</strong>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Designations Card -->
                        <div class="col-lg-3 col-md-6 mb-4">
                            <a href="{{ route('designations.list') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-designation">
                                        <div class="d-flex flex-row">
                                            <div
                                                class="round round-lg text-white d-inline-block text-center rounded-circle bg-dashboard-info">
                                                <i class="mdi mdi-id-card mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h3 class="mb-0 font-weight-light-new new-font-size">
                                                    Designations <strong>{{ $allDesignations }}</strong>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Plants Card -->
                        <div class="col-lg-3 col-md-6 mb-4">
                            <a href="{{ route('plantmaster.list') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-plants ">
                                        <div class="d-flex flex-row">
                                            <div
                                                class="round round-lg text-white d-inline-block text-center rounded-circle bg-dashboard-info">
                                               <i class="mdi mdi-factory mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h3 class="mb-0 font-weight-light-new new-font-size">
                                                    Plants <strong>{{ $allPlants }}</strong>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>


                          <!-- Plants Card -->
                        <div class="col-lg-3 col-md-6 mb-4">
                            <a href="{{ route('projects.list') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-projects">
                                        <div class="d-flex flex-row">
                                            <div
                                                class="round round-lg text-white d-inline-block text-center rounded-circle bg-dashboard-info">
                                                <i class="mdi mdi-clipboard-text mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h3 class="mb-0 font-weight-light-new new-font-size">
                                                    Projects <strong>{{ $allProjects }}</strong>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>


                          <!-- Plants Card -->
                        <div class="col-lg-3 col-md-6 mb-4">
                            <a href="{{ route('departments.list') }}" class="text-decoration-none text-dark">
                                <div class="card shadow-sm card-radius h-80">
                                    <div class="card-body bg-department">
                                        <div class="d-flex flex-row">
                                            <div
                                                class="round round-lg text-white d-inline-block text-center rounded-circle bg-dashboard-info">
                                               <i class="mdi mdi-domain mdi-36px icon-padding"></i>
                                            </div>
                                            <div class="ml-2 align-self-center">
                                                <h3 class="mb-0 font-weight-light-new new-font-size">
                                                    Departments <strong>{{ $allDepartments }}</strong>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>


                          <!-- Plants Card -->
                        <div class="col-lg-3 col-md-6 mb-4">
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
                        </div>

                        <!-- Employee Types Card -->
                        <div class="col-lg-3 col-md-6 mb-4">
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
                        </div>

                        <!-- Financial Years -->
                        <div class="col-lg-3 col-md-6 mb-4">
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
                        </div>

                        <!-- Employee Assignments Card -->
                        <div class="col-lg-3 col-md-6 mb-4">
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
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- End of Page Content -->
    <!-- ============================================================== -->
@endsection

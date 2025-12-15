@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class="row">
                        <!-- Roles Card -->
                        @php
                            $colors = ['bg-color-1', 'bg-color-2', 'bg-color-3', 'bg-color-4', 'bg-color-5', 'bg-color-6', 'bg-color-7', 'bg-color-8', 'bg-color-9'];
                        @endphp

                        @foreach ($projects as $index => $project)
                            @php
                                $colorClass = $colors[$index % count($colors)];
                            @endphp

                            <div class="col-lg-4 col-md-6 mb-4">

                                {{-- <a href="{{ $project->project_url }}?plant_no={{$project->plant_id}}&emp_code={{ session('emp_code') }}&fy_id={{ session('emp_financial_year_id') }}"> --}}
                                <a href="{{ $project->project_url }}?plant_code={{ session('emp_plant_code') }}&emp_code={{ $project->emp_code }}&fy={{ $project->financial_year }}&role={{ session('emp_role_name') }}&com_portal_url={{ session('com_portal_url') }}" target="_blank">

                                    <div class="card shadow-sm card-radius h-80">
                                        <div class="card-body card-project {{ $colorClass }}">
                                            <div class="d-flex flex-row">
                                                <div
                                                    class="round round-lg text-white d-inline-block text-center rounded-circle bg-dashboard-info">
                                                    <i class="mdi mdi-id-card mdi-36px icon-padding"></i>
                                                </div>
                                                <div class="ml-2 align-self-center">
                                                    <h3 class="mb-0 font-weight-light-new new-font-size">
                                                        {{ $project->project_name}}
                                                    </h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>

                            </div>
                        @endforeach

                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    @if (request()->has('message') && request('message') == 'Invalid Details')
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Employee Details Not Found On That Protal!',
            });
        </script>
    @endif


@endsection

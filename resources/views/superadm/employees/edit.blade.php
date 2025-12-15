@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    {{-- @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif --}}

                    <h4>Edit Employee</h4>
                    <form action="{{ route('employees.update', $employee->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Plant --}}
                        {{-- <div class="form-group">
                            <label for="plant_id">Select Plant <span class="text-danger">*</span></label>
                            <select name="plant_id" id="plant_id" class="form-control">
                                <option value="">Select Plant</option>
                                @foreach ($plants as $plant)
                                    <option value="{{ $plant->id }}"
                                        {{ $employee->plant_id == $plant->id ? 'selected' : '' }}>
                                        {{ $plant->plant_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('plant_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div> --}}

                        {{-- Department --}}
                        {{-- <div class="form-group">
                            <label for="department_id">Select Department <span class="text-danger">*</span></label>
                            <select id="department_id" name="department_id[]" multiple="multiple" class="form-control">
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                        {{ in_array($dept->id, explode(',', $employee->department_id)) ? 'selected' : '' }}>
                                        {{ $dept->department_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div> --}}

                        {{-- Projects --}}
                        {{-- <div class="form-group">
                            <label for="projects_id">Select Project <span class="text-danger">*</span></label>
                            <select id="projects_id" name="projects_id[]" multiple="multiple" class="form-control">
                                @foreach ($projects as $proj)
                                    <option value="{{ $proj->id }}"
                                        {{ in_array($proj->id, explode(',', $employee->projects_id)) ? 'selected' : '' }}>
                                        {{ $proj->project_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('projects_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div> --}}

                        <div class="form-group">
                            <label>Employee Name <span class="text-danger">*</span></label>
                            <input type="text" name="employee_name" class="form-control"
                                value="{{ $employee->employee_name }}">
                            @error('employee_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="employee_type">Employee Type <span class="text-danger">*</span></label>
                            <select name="employee_type" id="employee_type" class="form-control">
                                <option value="">Select Type</option>
                                @foreach ($employeeType as $type)
                                    <option value="{{ $type->id }}"
                                        {{ (isset($employee) && $employee->employee_type == $type->id) || old('employee_type') == $type->id ? 'selected' : '' }}>
                                        {{ $type->type_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_type')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Other Fields --}}
                        <div class="form-group">
                            <label>Employee Code <span class="text-danger">*</span></label>
                            <input type="text" name="employee_code" class="form-control"
                                value="{{ $employee->employee_code }}">
                            @error('employee_code')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Designation --}}
                        <div class="form-group">
                            <label for="designation_id">Designation <span class="text-danger">*</span></label>
                            <select name="designation_id" id="designation_id" class="form-control">
                                <option value="">Select Designation</option>
                                @foreach ($designations as $designation)
                                    <option value="{{ $designation->id }}"
                                        {{ $employee->designation_id == $designation->id ? 'selected' : '' }}>
                                        {{ $designation->designation }}
                                    </option>
                                @endforeach
                            </select>
                            @error('designation_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Role --}}
                        <div class="form-group">
                            <label for="role_id">Role <span class="text-danger">*</span></label>
                            <select name="role_id" id="role_id" class="form-control">
                                <option value="">Select Role</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}"
                                        {{ $employee->role_id == $role->id ? 'selected' : '' }}>
                                        {{ $role->role }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Employee Type --}}
                        {{-- <div class="form-group">
                            <label for="employee_type">Employee Type <span class="text-danger">*</span></label>
                            <select name="employee_type" id="employee_type" class="form-control">
                                <option value="">Select Type </option>
                                <option value="test_1" {{ $employee->employee_type == 'test_1' ? 'selected' : '' }}>Test 1
                                </option>
                                <option value="test_2" {{ $employee->employee_type == 'test_2' ? 'selected' : '' }}>Test 2
                                </option>
                            </select>
                            @error('employee_type')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div> --}}

                        <div class="form-group">
                            <label>Email</label>
                            <input type="text" name="employee_email" class="form-control"
                                value="{{ $employee->employee_email }}">
                            @error('employee_email')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>User Name <span class="text-danger">*</span></label>
                            <input type="text" name="employee_user_name" class="form-control"
                                value="{{ $employee->employee_user_name }}">
                            @error('employee_user_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Password (leave blank to keep existing)</label>
                            <input type="text" name="employee_password" class="form-control">
                            @error('employee_password')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Reporting To --}}
                        {{-- <div class="form-group">
                            <label for="reporting_to">Reporting To</label>
                            <select name="reporting_to" id="reporting_to" class="form-control">
                                <option value="">Select Reporting To </option>
                            </select>
                            @error('reporting_to')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div> --}}
                        {{-- Reporting To --}}
                        {{-- <div class="form-group">
                            <label for="reporting_to">Reporting To</label>
                            <select name="reporting_to" id="reporting_to" class="form-control">
                                <option value="">Select Reporting To</option>
                                @foreach ($employeesList  as $emp) 
                                    <option value="{{ $emp->id }}" 
                                        {{ $employee->reporting_to == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->employee_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('reporting_to')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div> --}}

                        <div class="form-group">
                            <label for="reporting_to">Reporting To</label>
                                <select name="reporting_to" id="reporting_to" class="form-control">
                                    <option value="">Select Reporting To</option>
                                    @foreach ($employeesList as $emp)
                                        <option value="{{ $emp->id }}" {{ $employee->reporting_to == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->employee_name }} - 
                                            {{ $emp->assignedPlants->isNotEmpty() ? $emp->assignedPlants->pluck('plant_name')->join(', ') : 'No Plant Assigned' }}
                                        </option>
                                    @endforeach
                                </select>
                            @error('reporting_to')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group d-flex justify-content-end">
                            <a href="{{ route('employees.list') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn-success btn-add">Update</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>



    {{-- Bootstrap Multiselect CSS & JS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-multiselect/dist/css/bootstrap-multiselect.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-multiselect/dist/js/bootstrap-multiselect.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#projects_id').multiselect({
                includeSelectAllOption: true, // "Select All" option
                enableFiltering: true, // Search box
                maxHeight: 300, // Scrollable
                buttonWidth: '100%'
            });
        });

            function loadEmployees(plantId) {
    if (!plantId) {
        $('#reporting_to').empty().append('<option value="">Select name</option>').multiselect('rebuild');
        return $.Deferred().resolve({ employees: [] }).promise();
    }

    return $.ajax({
        url: "{{ route('employees.list-ajax') }}",
        type: "POST",
        data: { _token: "{{ csrf_token() }}", plant_id: plantId },
        dataType: "json"
    }).done(function(response) {
        $('#reporting_to').empty().append('<option value="">Select name</option>');
        if (response.employees && response.employees.length > 0) {
            $.each(response.employees, function(key, emp) {
                if (emp.plant_name && emp.plant_name.length > 0) {
                    let text = emp.employee_name + ' - ' + emp.plant_name;
                    $('#reporting_to').append(`<option value="${emp.id}">${text}</option>`);
                }
            });
        }
        $('#reporting_to').multiselect('rebuild');
    });
}
    </script>

    <script>
        $(document).ready(function() {
            // Initialize multiselect first
            $('#projects_id').multiselect({
                includeSelectAllOption: true,
                enableFiltering: true,
                maxHeight: 300,
                buttonWidth: '100%'
            });

            // On Plant Change
            $('#plant_id').on('change', function() {
                let plantId = $(this).val();
                if (!plantId) {
                    $('#projects_id').empty().multiselect('rebuild');
                    return;
                }

                $.ajax({
                    url: "{{ route('projects.list-ajax') }}", // Laravel route
                    type: "POST", // POST because we are sending plant_id
                    data: {
                        _token: "{{ csrf_token() }}", // CSRF token
                        plant_id: plantId
                    },
                    dataType: "json",
                    success: function(response) {
                        console.log(response);
                        $('#projects_id').empty(); // clear old options

                        if (response.projects && response.projects.length > 0) {
                            $.each(response.projects, function(key, project) {
                                $('#projects_id').append(
                                    `<option value="${project.id}">${project.project_name}</option>`
                                );
                            });
                        } else if (response.projects.length == 0) {
                            alert("No projects found");
                        }

                        // Refresh multiselect to show new options
                        $('#projects_id').multiselect('rebuild');
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                    }
                });
            });
        });
    </script>




    <script>
        $(document).ready(function() {
            // Initialize multiselect first
            $('#department_id').multiselect({
                includeSelectAllOption: true,
                enableFiltering: true,
                maxHeight: 300,
                buttonWidth: '100%'
            });

            // On Plant Change
            $('#plant_id').on('change', function() {
                let plantId = $(this).val();
                if (!plantId) {
                    $('#department_id').empty().multiselect('rebuild');
                    return;
                }

                $.ajax({
                    url: "{{ route('departments.list-ajax') }}", // Laravel route
                    type: "POST", // POST because we are sending plant_id
                    data: {
                        _token: "{{ csrf_token() }}", // CSRF token
                        plant_id: plantId
                    },
                    dataType: "json",
                    success: function(response) {
                        console.log(response);
                        $('#department_id').empty(); // clear old options

                        if (response.department && response.department.length > 0) {
                            $.each(response.department, function(key, department_result) {
                                $('#department_id').append(
                                    `<option value="${department_result.id}">${department_result.department_name}</option>`
                                );
                            });
                        } else if (response.department.length == 0) {
                            alert("No departments found");
                        }

                        // Refresh multiselect to show new options
                        $('#department_id').multiselect('rebuild');
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                    }
                });
            });
        });
    </script>


    <script>
        $(document).ready(function() {

            // Initialize multiselect first
            $('#reporting_to').multiselect({
                includeSelectAllOption: true,
                enableFiltering: true,
                maxHeight: 300,
                buttonWidth: '100%'
            });

            // On Plant Change
            $('#plant_id').on('change', function() {
                let plantId = $(this).val();
                if (!plantId) {
                    $('#reporting_to').empty().multiselect('rebuild');
                    return;
                }

                $.ajax({
                    url: "{{ route('employees.list-ajax') }}", // Laravel route
                    type: "POST", // POST because we are sending plant_id
                    data: {
                        _token: "{{ csrf_token() }}", // CSRF token
                        plant_id: plantId
                    },
                    dataType: "json",
                    success: function(response) {
                        console.log(response);
                        $('#reporting_to').empty(); // clear old options
                        $('#reporting_to').append(
                            `<option value="">select name</option>`
                        );

                        if (response.employees && response.employees.length > 0) {
                            $.each(response.employees, function(key, employees_result) {
                                $('#reporting_to').append(
                                    `<option value="${employees_result.id}">${employees_result.employee_name}</option>`
                                );
                            });
                        } else if (response.employees.length == 0) {
                            alert("No employees found");
                        }

                        // Refresh multiselect to show new options
                        $('#reporting_to').multiselect('rebuild');
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                    }
                });
            });
        });
    </script>

@endsection

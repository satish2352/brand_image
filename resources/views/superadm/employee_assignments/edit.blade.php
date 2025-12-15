@extends('superadm.layout.master')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
<div class="row">
    <div class="col-lg-6 col-md-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <h4>Edit Employee Assignment</h4>

                {{-- Show global error message --}}
                {{-- @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                        </ul>
                    </div>
                @endif --}}

                <form action="{{ route('employee.assignments.update', $encodedId) }}" method="POST">
                    @csrf

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- Employee Select --}}
                    <div class="form-group">
                        <label>Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" id="employee_select" class="form-control">
                            <option value="">Select Employee</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" 
                                    {{ old('employee_id', $assignment->employee_id) == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->employee_name }} - {{ $emp->role->role ?? 'No Role' }}
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Plant Select --}}
                    <div class="form-group">
                        <label>Plant <span class="text-danger">*</span></label>
                        <select name="plant_id" id="plant_id" class="form-control">
                            <option value="">Select Plant</option>
                            @foreach($plants as $plant)
                                <option value="{{ $plant->id }}" 
                                    {{ old('plant_id', $assignment->plant_id) == $plant->id ? 'selected' : '' }}>
                                    {{ $plant->plant_code }} - {{ $plant->plant_name }} 
                                </option>
                            @endforeach
                        </select>
                        @error('plant_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Projects Multi-Select --}}
                    <div class="form-group">
                        <label>Projects <span class="text-danger">*</span></label>
                        @php
                            // $oldProjects = old('projects_id', explode(',', $assignment->projects_id ?? ''));
$oldProjects = old('projects_id', $assignment->projects_id);
$oldProjects = is_array($oldProjects) ? $oldProjects : [];
                        @endphp
                        <select id="projects_id" name="projects_id[]" multiple class="form-control">
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" 
                                    {{ in_array($project->id, $oldProjects) ? 'selected' : '' }}>
                                    {{ $project->project_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('projects_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Departments Multi-Select --}}
                    <div class="form-group">
                        <label>Departments <span class="text-danger">*</span></label>
                        @php
                            // $oldDepartments = old('department_id', explode(',', $assignment->department_id ?? ''));
$oldDepartments = old('department_id', $assignment->department_id);
$oldDepartments = is_array($oldDepartments) ? $oldDepartments : [];
                        @endphp
                        <select id="department_id" name="department_id[]" multiple class="form-control">
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" 
                                    {{ in_array($dept->id, $oldDepartments) ? 'selected' : '' }}>
                                    {{ $dept->department_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div class="form-group" style="display: none">
                        <label>Status <span class="text-danger">*</span></label>
                        <select name="is_active" class="form-control">
                            <option value="1" {{ old('is_active', $assignment->is_active) == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active', $assignment->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('is_active')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Buttons --}}
                    <div class="form-group d-flex justify-content-end">
                        <a href="{{ route('employee.assignments.list') }}" class="btn btn-secondary mr-2">Cancel</a>
                        <button type="submit" class="btn btn-success btn-add">Update</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

{{-- Bootstrap Multiselect --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-multiselect/dist/css/bootstrap-multiselect.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap-multiselect/dist/js/bootstrap-multiselect.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {

    // Initialize multiselect
    $('#projects_id, #department_id').multiselect({
        includeSelectAllOption: true,
        enableFiltering: true,
        maxHeight: 300,
        buttonWidth: '100%'
    });

    function loadProjects(plantId, selectedProjects = []) {
        return $.ajax({
            url: "{{ route('projects.list-ajax') }}",
            type: "POST",
            data: { _token: "{{ csrf_token() }}", plant_id: plantId },
            dataType: "json",
            success: function(res) {
                let options = '';
                (res.projects || []).forEach(p => {
                    let selected = selectedProjects.includes(String(p.id)) ? 'selected' : '';
                    options += `<option value="${p.id}" ${selected}>${p.project_name}</option>`;
                });
                $('#projects_id').html(options).multiselect('rebuild');

                if(res.projects.length === 0){
                    Swal.fire({icon:'warning', title:'Projects Missing', text:'Please add projects before assigning.'});
                }
            }
        });
    }

    function loadDepartments(plantId, selectedDepartments = []) {
        return $.ajax({
            url: "{{ route('departments.list-ajax') }}",
            type: "POST",
            data: { _token: "{{ csrf_token() }}", plant_id: plantId },
            dataType: "json",
            success: function(res) {
                let options = '';
                (res.department || []).forEach(d => {
                    let selected = selectedDepartments.includes(String(d.id)) ? 'selected' : '';
                    options += `<option value="${d.id}" ${selected}>${d.department_name}</option>`;
                });
                $('#department_id').html(options).multiselect('rebuild');

                if(res.department.length === 0){
                    Swal.fire({icon:'warning', title:'Departments Missing', text:'Please add departments before assigning.'});
                }
            }
        });
    }

    // On page load: check for old values or existing assignment
    let initialPlant = $('#plant_id').val();
    let selectedProjects = {!! json_encode($oldProjects) !!};
    let selectedDepartments = {!! json_encode($oldDepartments) !!};

    if(initialPlant){
        $('#projects_id, #department_id').prop('disabled', false);
        loadProjects(initialPlant, selectedProjects);
        loadDepartments(initialPlant, selectedDepartments);
    } else {
        $('#projects_id, #department_id').prop('disabled', true);
    }

    // On plant change
    $('#plant_id').on('change', function () {
        let plantId = $(this).val();
        if(!plantId){
            $('#projects_id, #department_id').empty().multiselect('rebuild').prop('disabled', true);
            return;
        }
        $('#projects_id, #department_id').prop('disabled', false);
        loadProjects(plantId);
        loadDepartments(plantId);
    });

});
</script>

<script>
$(document).ready(function() {
    $('#employee_select').select2({
        placeholder: 'Search Employee',
        allowClear: true,
        width: '100%'
    });
});
</script>


{{-- <script>
$(document).ready(function () {
    $('#projects_id, #department_id').multiselect({
        includeSelectAllOption: true,
        enableFiltering: true,
        maxHeight: 300,
        buttonWidth: '100%'
    });

    function loadProjects(plantId) {
        return $.ajax({
            url: "{{ route('projects.list-ajax') }}",
            type: "POST",
            data: { _token: "{{ csrf_token() }}", plant_id: plantId },
            dataType: "json"
        });
    }

    function loadDepartments(plantId) {
        return $.ajax({
            url: "{{ route('departments.list-ajax') }}",
            type: "POST",
            data: { _token: "{{ csrf_token() }}", plant_id: plantId },
            dataType: "json"
        });
    }

    $('#plant_id').on('change', function () {
        let plantId = $(this).val();

        if (!plantId) {
            $('#projects_id, #department_id').empty().multiselect('rebuild');
            return;
        }

        $.when(loadProjects(plantId), loadDepartments(plantId)).done(function(projectResp, deptResp) {
            let projects = projectResp[0].projects || [];
            let departments = deptResp[0].department || [];

            $('#projects_id').empty();
            $('#department_id').empty();

            projects.forEach(p => $('#projects_id').append(`<option value="${p.id}">${p.project_name}</option>`));
            departments.forEach(d => $('#department_id').append(`<option value="${d.id}">${d.department_name}</option>`));

            $('#projects_id, #department_id').multiselect('rebuild');

            if(projects.length === 0 && departments.length === 0){
                Swal.fire({icon:'warning', title:'Required Fields Missing', text:'Please add both Projects and Departments before updating.'});
            } else if(projects.length === 0){
                Swal.fire({icon:'warning', title:'Projects Missing', text:'Please add projects before updating.'});
            } else if(departments.length === 0){
                Swal.fire({icon:'warning', title:'Departments Missing', text:'Please add departments before updating.'});
            }
        });
    });
});
</script> --}}
@endsection

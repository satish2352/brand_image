@extends('superadm.layout.master')

@section('content')
<style>
    .dropdown-item.active, .dropdown-item-custom:active {
        background-color: #952419;
    }
    .modal-title{
        color: #ffffff;
    }
    .close-icon{
        color: #ffffff;
        opacity: unset;
    }
    /* Blink effect (just once per trigger) */
    .blink-btn {
        animation: blink 1s infinite;
    }

    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }
    /* Global Fullscreen Loader */
    #global-loader {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(255, 255, 255, 0.7);
        z-index: 9999;
        text-align: center;
    }
    #global-loader i {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 3rem;
        color: #f0ad4e;
    }
</style>

<div id="global-loader">
    <i class="fa fa-spinner fa-spin"></i>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class="mb-3 d-flex justify-content-end">
                    <div class="btn-group mr-2">
                        <button type="button" class="btn btn-warning btn-add dropdown-toggle" data-toggle="dropdown">
                            Export
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item dropdown-item-custom btn-export" href="{{ route('employee.assignments.export', ['type'=>'excel']) }}">Excel</a>
                            <a class="dropdown-item dropdown-item-custom btn-export" href="{{ route('employee.assignments.export', ['type'=>'pdf']) }}">PDF</a>
                        </div>
                    </div>
                    <a href="{{ route('employee.assignments.create') }}" class="btn btn-warning btn-add">Assign Plants</a>
                    {{-- <a href="{{ route('employee.assignments.export') }}" 
                    class="btn btn-warning btn-export btn-add">Download Excel</a> --}}
                </div>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" id="success-alert">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" id="error-alert">{{ session('error') }}</div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-striped datatables">
                        <thead>
                            <tr>
                                <th>Sr.No.</th>
                                <th>Employee</th>
                                <th>Plant</th>
                                <th>Departments</th>
                                <th>Projects</th>
                                <th>Send</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($assignments as $key => $data)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    {{-- <td>{{ $data->employee->employee_name ?? '-' }}</td> --}}
                                    <td>
                                        {{ $data->employee->employee_name ?? '-' }}
                                        @if(!empty($data->employee) && !empty($data->employee->role))
                                            - {{ $data->employee->role->role ?? '' }}
                                        @endif
                                    </td>
                                    <td>{{ $data->plant->plant_code ?? '-' }} - {{ $data->plant->plant_name ?? '-' }}</td>
                                    <td>{{ $data->departments_names }}</td>
                                    <td>{{ $data->projects_names }}</td>
                                    {{-- <td>
                                        <button class="btn btn-sm btn-success send-api-btn"
                                            data-id="{{ $data->id }}"
                                            title="Send API">
                                            <i class="mdi mdi-send"></i> Send API
                                        </button>
                                    </td> --}}
                                    <td>
                                        <button class="btn btn-sm btn-warning open-send-modal {{ $data->send_api == 0 ? 'blink-btn' : '' }}"
                                            data-id="{{ $data->id }}"
                                            data-employee="{{ $data->employee->employee_name ?? '-' }}"
                                            data-plant="{{ $data->plant->plant_code ?? '-' }} - {{ $data->plant->plant_name ?? '-' }}"
                                            title="Send Data">
                                            <i class="mdi mdi-upload"></i> Send Data
                                        </button>
                                    </td>
                                    <td>
                                        <form class="d-inline-block">
                                            @csrf
                                            <label class="switch">
                                                <input type="checkbox" class="toggle-status"
                                                    data-id="{{ base64_encode($data->id) }}"
                                                    {{ $data->is_active ? 'checked' : '' }}>
                                                <span class="slider"></span>
                                            </label>
                                        </form>
                                    </td>
                                    <td>
                                        {{-- Edit Button --}}
                                        <a href="{{ route('employee.assignments.edit', base64_encode($data->id)) }}" 
                                        class="btn btn-sm btn-primary mr-1 mb-1" 
                                        data-bs-toggle="tooltip" 
                                        data-bs-placement="top" 
                                        title="Edit">
                                        <i class="mdi mdi-square-edit-outline icon-medium"></i>
                                        </a>

                                        {{-- Delete Button --}}
                                        <form action="{{ route('employee.assignments.delete') }}" method="POST" class="d-inline-block delete-form">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ base64_encode($data->id) }}">
                                            <button type="button" class="btn btn-sm btn-danger delete-btn mb-1" 
                                                    data-employee="{{ $data->employee->employee_name ?? '-' }}"
                                                    data-plant="{{ $data->plant->plant_name ?? '-' }}"
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="top" 
                                                    title="Delete">
                                                <i class="mdi mdi-trash-can-outline icon-medium"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                             @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- =================== SEND DATA MODAL =================== --}}
<div class="modal fade" id="sendDataModal" tabindex="-1" role="dialog" aria-labelledby="sendDataLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header bg-warning">
            <h5 class="modal-title" id="sendDataLabel">Send Employee Data to API</h5>
            <button type="button" class="close close-icon" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <h6><strong>Employee:</strong> <span id="modalEmployeeName"></span></h6>
            <h6><strong>Plant:</strong> <span id="modalPlantName"></span></h6>
            <hr>

            <table class="table table-bordered" id="projectTable">
                <thead class="bg-light">
                    <tr>
                        <th>Sr. No</th>
                        <th>Project Name</th>
                        <th>Departments</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="button" class="btn btn-success" id="sendApiConfirmBtn">Send to API</button>
        </div>
    </div>
  </div>
</div>

{{-- =================== SCRIPT =================== --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-multiselect/dist/js/bootstrap-multiselect.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-multiselect/dist/css/bootstrap-multiselect.css">

<script>
    $(document).ready(function() {

        // Open modal on click
        $(document).on('click', '.open-send-modal', function() {
            let id = $(this).data('id');
            let employeeName = $(this).data('employee');
            let plantName = $(this).data('plant');

            $('#modalEmployeeName').text(employeeName);
            $('#modalPlantName').text(plantName);
            $('#projectTable tbody').html('<tr><td colspan="3" class="text-center">Loading...</td></tr>');
            $('#sendDataModal').modal('show');

            const $sendBtn = $('#sendApiConfirmBtn');
            $sendBtn.prop('disabled', true); // Initially disabled

            // Load assigned projects
            $.ajax({
                url: "{{ route('employee.assignments.getProjects') }}",
                type: "POST",
                data: { _token: "{{ csrf_token() }}", id: id },
                success: function(res) {
                    if (res.status) {
                        let rows = '';
                        res.projects.forEach((p, index) => {
                            let deptOptions = '';
                            res.departments.forEach(d => {
                                deptOptions += `<option value="${d.id}">${d.department_name}</option>`;
                            });

                            rows += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${p.project_name}</td>
                                    <td>
                                        <select class="form-control department-select" multiple name="department[${p.id}][]">
                                            ${deptOptions}
                                        </select>
                                    </td>
                                </tr>`;
                        });

                        $('#projectTable tbody').html(rows);

                        // Initialize multi-select dropdowns
                        $('.department-select').multiselect({
                            includeSelectAllOption: true,
                            enableFiltering: true,
                            buttonWidth: '100%',
                            maxHeight: 300
                        });

                        // 🔹 Check if any department selected -> enable/disable Send button
                        const updateSendButton = () => {
                            let allSelected = true;

                            $('.department-select').each(function() {
                                if (!$(this).val() || $(this).val().length === 0) {
                                    allSelected = false;
                                    return false; // break loop if any project missing
                                }
                            });

                            $sendBtn.prop('disabled', !allSelected);
                        };

                        // Initial check
                        updateSendButton();

                        // Watch for changes in dropdown selections
                        $(document).on('change', '.department-select', updateSendButton);

                    } else {
                        $('#projectTable tbody').html('<tr><td colspan="3" class="text-center text-danger">No projects found.</td></tr>');
                    }
                },
                error: function() {
                    $('#projectTable tbody').html('<tr><td colspan="3" class="text-center text-danger">Error loading data.</td></tr>');
                }
            });

            // store assignment id in modal for later use
            $('#sendApiConfirmBtn').data('id', id);
        });

        // Disable Send button until all projects have departments selected
        $(document).on('change', '.department-select', function() {
            let allSelected = true;

            $('.department-select').each(function() {
                if ($(this).val() === null || $(this).val().length === 0) {
                    allSelected = false;
                    return false; // break loop
                }
            });

            if (allSelected) {
                $('#sendApiConfirmBtn').prop('disabled', false);
            } else {
                $('#sendApiConfirmBtn').prop('disabled', true);
            }
        });


        // Send to API
        $('#sendApiConfirmBtn').on('click', function() {
            let id = $(this).data('id');
            let departmentsData = {};

            $('.department-select').each(function() {
                let projectId = $(this).attr('name').match(/\d+/)[0];
                let selected = $(this).val() || [];
                departmentsData[projectId] = selected;
            });

            Swal.fire({
                title: "Are you sure?",
                text: "Do you want to send this data to API?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#28a745",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, send!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('employee.assignments.sendApi') }}",
                        type: "POST",
                        data: { _token: "{{ csrf_token() }}", id: id, departments: departmentsData },
                        success: function(response){
                            // if(response.status){
                            //     Swal.fire("Success!", response.message, "success");
                            //     $('#sendDataModal').modal('hide');
                            if(response.status){
                                Swal.fire({
                                    title: "Success!",
                                    text: response.message,
                                    icon: "success",
                                    allowOutsideClick: true,
                                }).then((swalResult) => {
                                    if (swalResult.isConfirmed || swalResult.isDismissed) {
                                        location.reload();
                                    }
                                });
                                $('#sendDataModal').modal('hide');
                            } else {
                                Swal.fire("Error!", response.message, "error");
                            }
                        },
                        error: function(){
                            Swal.fire("Error!", "Something went wrong.", "error");
                        }
                    });
                }
            });
        });

    });
</script>

{{-- Global AJAX Loader --}}
<script>
    $(document).ajaxStart(function () {
        $("#global-loader").fadeIn(100);
    });
    $(document).ajaxStop(function () {
        $("#global-loader").fadeOut(300);
    });
</script>

<script>
$(document).on("change", ".toggle-status", function(e) {
    e.preventDefault();

    let checkbox = $(this);
    let id = checkbox.data("id");
    let is_active = checkbox.is(":checked") ? 1 : 0;

    Swal.fire({
        title: "Are you sure?",
        text: "Do you want to change the status?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, change it!",
        cancelButtonText: "No, cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ route('employee.assignments.updatestatus') }}",
                type: "POST",
                data: { _token: "{{ csrf_token() }}", id: id, is_active: is_active },
                success: function(response) {
                    if(response.status){
                        Swal.fire('Success!', response.message, 'success');
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                        checkbox.prop("checked", !is_active);
                    }
                },
                error: function(xhr){
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Something went wrong', 'error');
                    checkbox.prop("checked", !is_active);
                }
            });
        } else {
            checkbox.prop("checked", !checkbox.is(":checked"));
        }
    });
});
</script>

<script>
$(document).ready(function(){

    // ---- Delete Employee Assignment ----
    $(document).on("click", ".delete-btn", function(e){
        e.preventDefault();

        let button = $(this);
        let form = button.closest("form");
        let id = form.find("input[name='id']").val();
        let employeeName = button.data("employee");
        let plantName = button.data("plant");

        Swal.fire({
            title: "Are you sure?",
            text: `Do you want to delete ${employeeName} assignment for ${plantName}?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, delete it!",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if(result.isConfirmed){
                $.ajax({
                    url: form.attr("action"),
                    type: "POST",
                    data: form.serialize(),
                    success: function(response){
                        if(response.status){
                            Swal.fire("Deleted!", response.message, "success")
                                .then(() => location.reload()); // Reload page
                        } else {
                            Swal.fire("Error!", response.message, "error");
                        }
                    },
                    error: function(xhr){
                        Swal.fire("Error!", xhr.responseJSON?.message || "Something went wrong", "error");
                    }
                });
            }
        });
    });


    // ---- Toggle Status ----
    $(document).on("change", ".toggle-status", function(e){
        e.preventDefault();

        let checkbox = $(this);
        let previousState = !checkbox.prop("checked"); // 1st state
        let id = checkbox.data("id");
        let newState = checkbox.is(":checked") ? 1 : 0;

        let row = checkbox.closest("tr");
        let employeeName = row.find("td:nth-child(2)").text();
        let plantName = row.find("td:nth-child(3)").text();
        let statusText = newState ? "activate" : "deactivate";

        // instent state not change → 1st revert do
        checkbox.prop("checked", previousState);

        Swal.fire({
            title: "Are you sure?",
            text: `Do you want to ${statusText} ${employeeName}'s assignment for ${plantName}?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#28a745",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, change it!",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if(result.isConfirmed){
                $.ajax({
                    url: "{{ route('employee.assignments.updatestatus') }}",
                    type: "POST",
                    data: { _token: "{{ csrf_token() }}", id: id, is_active: newState },
                    success: function(response){
                        if(response.status){
                            // Success done then checkbox update do
                            checkbox.prop("checked", newState);
                            Swal.fire("Success!", response.message, "success");
                        } else {
                            Swal.fire("Error!", response.message, "error");
                        }
                    },
                    error: function(xhr){
                        Swal.fire("Error!", xhr.responseJSON?.message || "Something went wrong", "error");
                    }
                });
            } else {
                // Cancel → do nothing
                checkbox.prop("checked", previousState);
            }
        });
    });



});
</script>

<script>
$(document).on('click', '.btn-export', function(e){
    e.preventDefault();
    const exportUrl = $(this).attr('href');

    // Get visible rows after DataTable search/filter
    const rows = $("table.datatables tbody tr:visible");
    let hasData = false;

    rows.each(function() {
        const firstCell = $(this).find("td:first").text().trim().toLowerCase();
        if (firstCell !== "" && firstCell !== "no data available" && firstCell !== "no matching records found") {
            hasData = true;
            return false; // stop loop
        }
    });

    if (!hasData) {
        Swal.fire({
            icon: 'warning',
            title: 'No data available!',
            text: 'There is no matching record to export.',
            confirmButtonColor: '#3085d6'
        });
        return false;
    }

    // If data exists, proceed
    window.location.href = exportUrl;
});
</script>


    <script>
        // Delegated event for delete buttons
        $(document).on("click", ".delete-btn", function (e) {
            e.preventDefault();

            let button = $(this);
            let form = button.closest("form");

            Swal.fire({
                title: "Are you sure?",
                text: "This record will be deleted!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#28a745",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "No, cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

    </script>

    {{-- <script>
    setInterval(() => {
        $.ajax({
            url: "{{ route('employee.assignments.checkSendApi') }}",
            type: "GET",
            global: false, // ✅ Proper way to disable global ajaxStart/ajaxStop
            success: function(res) {
                if (res.status) {
                    res.data.forEach(item => {
                        const btn = $(`.open-send-modal[data-id='${item.id}']`);

                        if (item.send_api == 0) {
                            if (!btn.hasClass('blink-btn')) {
                                btn.addClass('blink-btn');
                            }
                        } else {
                            btn.removeClass('blink-btn');
                        }
                    });
                }
            },
            error: function() {
                console.error("Error checking send_api status.");
            }
        });
    }, 10000); // every 10 seconds
    </script> --}}


    {{-- <script>
        $(document).on('click', '.send-api-btn', function(e){
        e.preventDefault();
        let button = $(this);
        let id = button.data('id');

        Swal.fire({
            title: "Are you sure?",
            text: "Do you want to send this employee data to API?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#28a745",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, send!",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if(result.isConfirmed){
                $.ajax({
                    url: "{{ route('employee.assignments.sendApi') }}",
                    type: "POST",
                    data: { _token: "{{ csrf_token() }}", id: id },
                    success: function(response){
                        if(response.status){
                            Swal.fire("Success!", response.message, "success");
                        } else {
                            Swal.fire("Error!", response.message, "error");
                        }
                    },
                    error: function(xhr){
                        Swal.fire("Error!", xhr.responseJSON?.message || "Something went wrong", "error");
                    }
                });
            }
        });
    });

    </script> --}}


@endsection

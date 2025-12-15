@extends('superadm.layout.master')
@section('content')
<style>
    .dropdown-item.active, .dropdown-item-custom:active {
        background-color: #952419;
    }
    #DataTables_Table_0_filter{
        display: none;
    }
</style>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                {{-- <div class="mb-3 d-flex justify-content-between">
                    <input type="text" id="searchInput" class="form-control w-50" placeholder="Search employees...">
                    <a href="{{ route('employees.create') }}" class="btn btn-danger btn-add">
                        <i class="mdi mdi-account-plus"></i> Add Employee
                    </a>
                    <a id="exportExcelBtn" class="btn btn-danger btn-add cursor-pointer" 
                        style="cursor:pointer;" 
                        title="Export Excel">
                        <i class="mdi mdi-file-excel"></i> Export Excel
                    </a>
                </div> --}}

                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <input type="text" id="searchInput" class="form-control w-50" placeholder="Search employees...">

                    <div class="d-flex gap-2">
                    <div class="btn-group mr-2">
                        <button type="button" class="btn btn-warning btn-add dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Export
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item dropdown-item-custom export-link" href="#" data-type="excel">Excel</a>
                            <a class="dropdown-item dropdown-item-custom export-link" href="#" data-type="pdf">PDF</a>
                        </div>
                    </div>
                        <a href="{{ route('employees.create') }}" class="btn btn-danger btn-add">
                            <i class="mdi mdi-account-plus"></i> Add Employee
                        </a>
                        {{-- <a id="exportExcelBtn" class="btn btn-danger btn-add cursor-pointer" 
                            style="cursor:pointer;" 
                            title="Export Excel">
                            <i class="mdi mdi-file-excel"></i> Export Excel
                        </a> --}}
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" id="success-alert">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" id="error-alert" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped datatables">
                        <thead>
                            <tr>
                                <th>Sr. No.</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Email</th>
                                <th>User Name</th>
                                <th>Reporting To</th>
                               
                                <th>Designation</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            @foreach($employees as $key => $data)
                            <tr>
                                <td>{{ $employees->firstItem() + $key }}</td>
                                <td>{{ $data->employee_name }}</td>
                                <td>{{ $data->employee_code }}</td>
                                <td>{{ $data->employee_email }}</td>
                                <td>{{ $data->employee_user_name }}</td>
                                <td>{{ $data->reporting_name ?? '-' }}</td>
                                
                                <td>{{ $data->designation->designation ?? '-' }}</td>
                                <td>{{ $data->role->role ?? '-' }}</td>
                                <td>
                                    @if (!empty($data->role) && $data->role->id != 0)
                                    <label class="switch">
                                        <input type="checkbox" class="toggle-status " data-id="{{ base64_encode($data->id) }}" {{ $data->is_active ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                     @else 
                                      Active
                                       @endif
                                </td>
                                <td class="d-flex">
                                    @if (!empty($data->role) && $data->role->id != 0)
                                        {{-- Edit Button --}}
                                        <a href="{{ route('employees.edit', base64_encode($data->id)) }}" 
                                            class="btn btn-sm btn-primary mr-1" 
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="top" 
                                            title="Edit">
                                            <i class="mdi mdi-square-edit-outline icon-medium"></i>
                                        </a>

                                        {{-- Delete Button --}}
                                        <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                                data-id="{{ base64_encode($data->id) }}"
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top" 
                                                title="Delete">
                                            <i class="mdi mdi-trash-can-outline icon-medium"></i>
                                        </button>
                                    @else
                                        {{-- Disabled Buttons --}}
                                        <button class="btn btn-sm btn-secondary mr-1" disabled>
                                            <i class="mdi mdi-square-edit-outline icon-medium"></i>
                                        </button>
                                        <button class="btn btn-sm btn-secondary" disabled>
                                            <i class="mdi mdi-trash-can-outline icon-medium"></i>
                                        </button>
                                    @endif
                                </td>                               
                            </tr>
                            @endforeach
                            @if($employees->count() == 0)
                            <tr><td colspan="13" class="text-center">No employees found.</td></tr>
                            @endif
                        </tbody>
                    </table>

                    <div class="mt-3" id="paginationLinks">
                        {{ $employees->links('pagination::bootstrap-4') }}
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {

    // Function to render table rows
    // function renderTable(data = [], currentPage = 1, perPage = 10) {
    //     let html = '';
    //     if(data.length > 0){
    //         data.forEach(function(emp, index){
    //             // Serial number calculation per page
    //             let srNo = (currentPage - 1) * perPage + index + 1;

    //             html += `<tr>
    //                 <td>${srNo}</td>
    //                 <td>${emp.employee_name}</td>
    //                 <td>${emp.employee_code}</td>
    //                 <td>${emp.employee_email}</td>
    //                 <td>${emp.employee_user_name}</td>
    //                 <td>${emp.reporting_name ?? '-'}</td>
    //                 <td>${emp.plant_name ?? '-'}</td>
    //                 <td>${emp.project_names ?? '-'}</td>
    //                 <td>${emp.department_names ?? '-'}</td>
    //                 <td>${emp.designation ?? '-'}</td>
    //                 <td>${emp.role ?? '-'}</td>
    //                 <td>
    //                     <label class="switch">
    //                         <input type="checkbox" class="toggle-status" data-id="${btoa(emp.id)}" ${emp.is_active ? 'checked' : ''}>
    //                         <span class="slider"></span>
    //                     </label>
    //                 </td>
    //                 <td>
    //                     <a href="/employees/edit/${btoa(emp.id)}" class="btn btn-sm btn-primary mr-1" data-bs-toggle="tooltip" data-bs-placement="top">
    //                         <i class="mdi mdi-square-edit-outline"></i>
    //                     </a>
    //                     <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="${btoa(emp.id)}"  data-bs-toggle="tooltip" data-bs-placement="top">
    //                         <i class="mdi mdi-trash-can-outline"></i>
    //                     </button>
    //                 </td>
    //             </tr>`;
    //         });
    //     } else {
    //         html = '<tr><td colspan="13" class="text-center">No employees found.</td></tr>';
    //     }
    //     return html;
    // }

function renderTable(data = [], currentPage = 1, perPage = 10) {
    let html = '';
    if (data.length > 0) {
        data.forEach(function(emp, index) {
            let srNo = (currentPage - 1) * perPage + index + 1;

            html += `<tr>
                <td>${srNo}</td>
                <td>${emp.employee_name}</td>
                <td>${emp.employee_code}</td>
                <td>${emp.employee_email || '-'}</td>
                <td>${emp.employee_user_name || '-'}</td>
                <td>${emp.reporting_name || '-'}</td>
                
                <td>${emp.designation?.designation || '-'}</td>
                <td>${emp.role?.role || '-'}</td>
<td>
${(emp.role && emp.role.id != 0) ? `
    <label class="switch">
        <input type="checkbox" class="toggle-status" data-id="${btoa(emp.id)}" ${emp.is_active ? 'checked' : ''}>
        <span class="slider"></span>
    </label>
` : `
    <label class="switch">
        <input type="checkbox" disabled>
        <span class="slider"></span>
    </label>
`}
</td>

<td class="d-flex">
${(emp.role != null && parseInt(emp.role) !== 0) ? `
    <a href="{{ url('employees/edit') }}/${btoa(emp.id)}"
        class="btn btn-sm btn-primary mr-1" 
        data-bs-toggle="tooltip" 
        title="Edit">
        <i class="mdi mdi-square-edit-outline icon-medium"></i>
    </a>
    <button type="button" 
        class="btn btn-sm btn-danger delete-btn" 
        data-id="${btoa(emp.id)}"
        data-bs-toggle="tooltip" 
        title="Delete">
        <i class="mdi mdi-trash-can-outline icon-medium"></i>
    </button>
` : `
    <button class="btn btn-sm btn-secondary mr-1" disabled>
        <i class="mdi mdi-square-edit-outline icon-medium"></i>
    </button>
    <button class="btn btn-sm btn-secondary" disabled>
        <i class="mdi mdi-trash-can-outline icon-medium"></i>
    </button>
`}
</td>

            </tr>`;
        });
    } else {
        html = '<tr><td colspan="10" class="text-center">No employees found.</td></tr>';
    }
    return html;
}



    // Fetch employees AJAX
    function fetchEmployees(url = "{{ route('employees.ajax') }}", search = '') {
        $.ajax({
            url: url,
            type: 'GET',
            data: { search: search },
            success: function(res){
                let employeesData = res.data ?? [];
                let currentPage = res.current_page ?? 1;
                let perPage = res.per_page ?? 10;

                $('#tableBody').html(renderTable(employeesData, currentPage, perPage));
                $('#paginationLinks').html(res.pagination ?? '');

                // Re-initialize Bootstrap tooltips after AJAX render
                $('[data-bs-toggle="tooltip"]').tooltip();
            },
            error: function(xhr){
                console.error(xhr.responseText);
                $('#tableBody').html('<tr><td colspan="13" class="text-danger text-center">Failed to load data</td></tr>');
            }
        });
    }

    // Search typing
    let typingTimer;
    let doneTypingInterval = 400;
    $('#searchInput').on('keyup', function(){
        clearTimeout(typingTimer);
        let search = $(this).val();
        typingTimer = setTimeout(function(){
            fetchEmployees("{{ route('employees.ajax') }}", search);
        }, doneTypingInterval);
    });

    // Pagination click
    $(document).on('click', '.pagination a', function(e){
        e.preventDefault();
        let url = $(this).attr('href');
        url = url.replace("{{ route('employees.list') }}", "{{ route('employees.ajax') }}");
        let search = $('#searchInput').val();
        fetchEmployees(url, search);
    });

$(document).on('click', '.delete-btn', function(){
    let id = $(this).data('id'); // base64 encoded

    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("employees.delete") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id
                },
                success: function(res){
                    if(res.status){
                        Swal.fire('Deleted!', res.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Failed!', res.message, 'error');
                    }
                },
                error: function(xhr){
                    Swal.fire('Failed!', xhr.responseJSON?.message || 'Something went wrong', 'error');
                }
            });
        }
    });
});


    // Toggle status with confirmation
    $(document).on('change', '.toggle-status', function(e){
        e.preventDefault();

        let checkbox = $(this);
        let id = checkbox.data('id'); // base64 encoded
        let isActive = checkbox.is(':checked') ? 1 : 0;

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
            if(result.isConfirmed){
                $.ajax({
                    url: '{{ route("employees.updatestatus") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: id,
                        is_active: isActive
                    },
                    success: function(res){
                        if(res.status){
                            Swal.fire("Success!", res.message, "success");
                        } else {
                            Swal.fire("Error!", res.message, "error");
                            checkbox.prop("checked", !checkbox.is(":checked")); // revert
                        }
                    },
                    error: function(xhr){
                        Swal.fire("Error!", xhr.responseJSON?.message || "Something went wrong", "error");
                        checkbox.prop("checked", !checkbox.is(":checked")); // revert
                    }
                });
            } else {
                // If canceled, revert checkbox
                checkbox.prop("checked", !checkbox.is(":checked"));
            }
        });
    });


});
</script>

<script>
$(document).ready(function() {
    $('.export-link').click(function(e){
        e.preventDefault();
        let type = $(this).data('type'); // excel or pdf

        // Collect values of all text inputs (your search boxes)
        let searchValues = [];
        $('input[type="text"]').each(function(){
            let val = $(this).val().trim();
            searchValues.push(val);
        });

        // Check visible rows in the table (after Datatable filtering)
        let visibleRows = $("table.datatables tbody tr:visible");
        let hasData = false;

        visibleRows.each(function() {
            let rowText = $(this).text().trim().toLowerCase();
            if(rowText && !rowText.includes('no employees found')) {
                hasData = true;
                return false; // stop loop
            }
        });

        if(!hasData) {
            Swal.fire({
                icon: 'warning',
                title: 'No data available!',
                text: 'There is no data in the table to export.'
            });
            return false;
        }

        let searchValue = $('.dataTables_filter input').val();

        // ✅ Construct the export URL dynamically
        let url = "{{ route('employees.export') }}" + '?type=' + type;
        if (searchValue) {
            url += '&search=' + encodeURIComponent(searchValue);
        }

        
        // Redirect to export
        window.location.href = url;
    });
});

</script>


@endsection

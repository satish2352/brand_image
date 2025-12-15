<!DOCTYPE html>
<html>
<head>
    <title>Employees Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table, th, td { border: 1px solid black; }
        th, td { padding: 5px; text-align: left; word-wrap: break-word; }
        th { background-color: #952419; color: #fff; text-align: center; }

        /* Column widths */
        th:nth-child(1), td:nth-child(1) { width: 3%; }   /* Sr No */
        th:nth-child(2), td:nth-child(2) { width: 15%; }  /* Name */
        th:nth-child(3), td:nth-child(3) { width: 10%; }  /* Employee Code */
        th:nth-child(4), td:nth-child(4) { width: 20%; }  /* Email */
        th:nth-child(5), td:nth-child(5) { width: 12%; }  /* User Name */
        th:nth-child(6), td:nth-child(6) { width: 15%; }  /* Reporting To */
        th:nth-child(7), td:nth-child(7) { width: 10%; }  /* Designation */
        th:nth-child(8), td:nth-child(8) { width: 10%; }  /* Role */
        th:nth-child(9), td:nth-child(9) { width: 5%; }   /* Status */
    </style>
</head>
<body>
    <h3>Employees Report</h3>
    <table>
        <thead>
            <tr>
                <th>Sr No</th>
                <th>Name</th>
                <th>Employee Code</th>
                <th>Email</th>
                <th>User Name</th>
                <th>Reporting To</th>
                <th>Designation</th>
                <th>Role</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php $srNo = 1; @endphp
            @foreach ($employees as $emp)
                <tr>
                    <td>{{ $srNo++ }}</td>
                    <td>{{ $emp->employee_name }}</td>
                    <td>{{ $emp->employee_code }}</td>
                    <td>{{ $emp->employee_email }}</td>
                    <td>{{ $emp->employee_user_name }}</td>
                    <td>{{ $emp->reporting_name ?? '-' }}</td>
                    <td>{{ $emp->designation->designation ?? '-' }}</td>
                    <td>{{ $emp->role->role ?? '-' }}</td>
                    <td>{{ $emp->is_active == 1 ? 'Active' : 'Deactive' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

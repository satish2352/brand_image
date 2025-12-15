<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employee Plant Assignments</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h3 { text-align: center; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table, th, td { border: 1px solid black; }
        th, td { padding: 5px; text-align: left; word-wrap: break-word; }
        th { background-color: #952419; color: #fff; text-align: center; }

        /* Optional column widths */
        th:nth-child(1), td:nth-child(1) { width: 3%; }   /* Sr No */
        th:nth-child(2), td:nth-child(2) { width: 15%; }  /* Employee */
        th:nth-child(3), td:nth-child(3) { width: 15%; }  /* Plant */
        th:nth-child(4), td:nth-child(4) { width: 25%; }  /* Departments */
        th:nth-child(5), td:nth-child(5) { width: 25%; }  /* Projects */
        th:nth-child(6), td:nth-child(6) { width: 10%; }  /* Status */
    </style>
</head>
<body>
    <h3>Employee Plant Assignments</h3>
    <table>
        <thead>
            <tr>
                <th>Sr No</th>
                <th>Employee</th>
                <th>Plant</th>
                <th>Departments</th>
                <th>Projects</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php $srNo = 1; @endphp
            @foreach ($assignments as $data)
            <tr>
                <td>{{ $srNo++ }}</td>
                <td>{{ $data->employee->employee_name ?? '-' }}</td>
                <td>{{ $data->plant->plant_name ?? '-' }}</td>
                <td>{{ $data->departments_names ?? '-' }}</td>
                <td>{{ $data->projects_names ?? '-' }}</td>
                <td>{{ $data->is_active ? 'Active' : 'Inactive' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

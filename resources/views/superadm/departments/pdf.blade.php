<!DOCTYPE html>
<html>
<head>
    <title>Departments Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table, th, td { border: 1px solid black; }
        th, td { padding: 5px; text-align: left; word-wrap: break-word; }
        th { background-color: #952419; color: #fff; text-align: center; }

        /* Column widths */
        th:nth-child(1), td:nth-child(1) { width: 3%; }  /* Sr No */
        th:nth-child(2), td:nth-child(2) { width: 15%; } /* Plant Name */
        th:nth-child(3), td:nth-child(3) { width: 10%; } /* Department Code */
        th:nth-child(4), td:nth-child(4) { width: 20%; } /* Department Name */
        th:nth-child(5), td:nth-child(5) { width: 15%; } /* Short Name */
        th:nth-child(6), td:nth-child(6) { width: 10%; } /* Created By */
        th:nth-child(7), td:nth-child(7) { width: 12%; } /* Created Date */
        th:nth-child(8), td:nth-child(8) { width: 10%; } /* Status */
    </style>
</head>
<body>
    <h3>Departments Report</h3>
    <table>
        <thead>
            <tr>
                <th>Sr No</th>
                <th>Plant Name</th>
                <th>Department Code</th>
                <th>Department Name</th>
                <th>Department Short Name</th>
                <th>Created By</th>
                <th>Created Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php $srNo = 1; @endphp
            @foreach ($departments as $dept)
                <tr>
                    <td>{{ $srNo++ }}</td>
                    <td>{{ $dept->plant_name ?? '-' }}</td>
                    <td>{{ $dept->department_code }}</td>
                    <td>{{ $dept->department_name }}</td>
                    <td>{{ $dept->department_short_name ?? '-' }}</td>
                    <td>{{ $dept->created_by ?? '-' }}</td>
                    <td>{{ $dept->created_at ? \Carbon\Carbon::parse($dept->created_at)->setTimezone('Asia/Kolkata')->format('d-m-Y h:i:s A') : '-' }}</td>
                    <td>{{ $dept->is_active == 1 ? 'Active' : 'Deactive' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

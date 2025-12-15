<!DOCTYPE html>
<html>
<head>
    <title>Plants Report</title>
<style>
    body { 
        font-family: DejaVu Sans, sans-serif; 
        font-size: 10px;  /* smaller font */
    }
    table { 
        width: 100%; 
        border-collapse: collapse; 
        table-layout: fixed; /* important for wide tables */
    }
    th, td { 
        border: 1px solid black; 
        padding: 4px; 
        word-wrap: break-word; /* wrap long text */
        text-align: left;
    }
    th { 
        background-color: #952419; 
        color: #fff; 
        text-align: center; 
    }

    th:nth-child(1), td:nth-child(1) { width: 3%; }  /* Sr No */
    th:nth-child(2), td:nth-child(2) { width: 10%; } /* Plant Code */
    th:nth-child(3), td:nth-child(3) { width: 15%; } /* Plant Name */
    th:nth-child(4), td:nth-child(4) { width: 20%; } /* Address */
    th:nth-child(5), td:nth-child(5) { width: 10%; } /* City */
    th:nth-child(6), td:nth-child(6) { width: 10%; } /* Short Name */
    th:nth-child(7), td:nth-child(7) { width: 10%; } /* Created By */
    th:nth-child(8), td:nth-child(8) { width: 12%; } /* Created Date */
    th:nth-child(9), td:nth-child(9) { width: 10%; } /* Status */

</style>

</head>
<body>
    <h3>Plants Report</h3>
    <table>
        <thead>
            <tr>
                <th>Sr No</th>
                <th>Plant Code</th>
                <th>Plant Name</th>
                <th>Address</th>
                <th>City</th>
                <th>Short Name</th>
                <th>Created By</th>
                <th>Created Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php $srNo = 1; @endphp
            @foreach ($plants as $plant)
                <tr>
                    <td>{{ $srNo++ }}</td>
                    <td>{{ $plant->plant_code }}</td>
                    <td>{{ $plant->plant_name }}</td>
                    <td>{{ $plant->address ?? '-' }}</td>
                    <td>{{ $plant->city ?? '-' }}</td>
                    <td>{{ $plant->plant_short_name ?? '-' }}</td>
                    <td>{{ $plant->created_by ?? '-' }}</td>
                    <td>{{ $plant->created_at ? \Carbon\Carbon::parse($plant->created_at)->setTimezone('Asia/Kolkata')->format('d-m-Y h:i:s A') : '-' }}</td>
                    <td>{{ $plant->is_active == 1 ? 'Active' : 'Deactive' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

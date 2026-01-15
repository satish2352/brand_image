<!DOCTYPE html>
<html>

<head>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 12px;
        }

        th {
            background: #f2f2f2;
        }
    </style>
</head>

<body>

    <h3>Media Utilisation Report</h3>

    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Media Code</th>
                <th>Media Title</th>
                <th>Category</th>
                <th>Size</th>
                <th>From</th>
                <th>To</th>
                <th>Days</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reports as $row)
                <tr>
                    <td>{{ $row->user_name }}</td>
                    <td>{{ $row->media_code }}</td>
                    <td>{{ $row->media_title }}</td>
                    <td>{{ $row->category_name }}</td>
                    <td>{{ $row->width }} x {{ $row->height }}</td>
                    <td>{{ $row->from_date }}</td>
                    <td>{{ $row->to_date }}</td>
                    <td>{{ $row->booked_days }}</td>
                    <td>{{ number_format($row->booking_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>

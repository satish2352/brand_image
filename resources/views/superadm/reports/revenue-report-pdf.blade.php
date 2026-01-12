<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Revenue Report</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }

        h3 {
            text-align: center;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #2F75B5;
            color: #fff;
        }
    </style>
</head>
<body>

<h3>Revenue Report</h3>

<table>
    <thead>
    <tr>
         <th>Sr. No</th>
        @if($type === 'date')
            <th>Period</th>
            <th>Total Bookings</th>
            <th>Booking Type</th>
            {{-- <th>Total Revenue (₹)</th> --}}
            <th>Amount (₹)</th>
            <th>GST (₹)</th>
            <th>Final Total (₹)</th>

        @elseif($type === 'media')
            <th>Media Code</th>
            <th>Category</th>
            <th>Media Title</th>
            <th>State</th>
            <th>District</th>
            <th>City</th>
            <th>Area</th>
            <th>Size (WxH)</th>
            <th>Booking Type</th>
            <th>Total Bookings</th>
            <th>Booked Days</th>
            {{-- <th>Total Revenue (₹)</th> --}}
            <th>Amount (₹)</th>
            <th>GST (₹)</th>
            <th>Final Total (₹)</th>

        @elseif($type === 'user')
            <th>User Name</th>
            <th>Booking Type</th>
            <th>Total Bookings</th>
            <th>Booked Days</th>
            {{-- <th>Total Revenue (₹)</th> --}}
            <th>Amount (₹)</th>
            <th>GST (₹)</th>
            <th>Final Total (₹)</th>
        @endif
    </tr>
    </thead>

    <tbody>
    @foreach($reports as $index => $row)
        <tr>
            <td>{{ $index + 1 }}</td>
            @if($type === 'date')
                <td>{{ $row->period }}</td>
                <td>{{ $row->booking_type }}</td>
                <td>{{ $row->total_bookings }}</td>
                {{-- <td>{{ number_format($row->total_revenue, 2) }}</td> --}}
                <td>₹ {{ number_format($row->total_amount, 2) }}</td>
                <td>₹ {{ number_format($row->gst_amount, 2) }}</td>
                <td><strong>₹ {{ number_format($row->grand_total, 2) }}</strong></td>

            @elseif($type === 'media')
                <td>{{ $row->media_code }}</td>
                <td>{{ $row->category_name }}</td>
                <td>{{ $row->media_title }}</td>
                <td>{{ $row->state_name }}</td>
                <td>{{ $row->district_name }}</td>
                <td>{{ $row->city_name }}</td>
                <td>{{ $row->area_name ?? '-' }}</td>
                <td>{{ $row->width }} x {{ $row->height }}</td>
                <td>{{ $row->booking_type }}</td>
                <td>{{ $row->total_bookings }}</td>
                <td>{{ $row->booked_days }}</td>
                {{-- <td>{{ number_format($row->total_revenue, 2) }}</td> --}}
                <td>₹ {{ number_format($row->total_amount, 2) }}</td>
                <td>₹ {{ number_format($row->gst_amount, 2) }}</td>
                <td><strong>₹ {{ number_format($row->grand_total, 2) }}</strong></td>

            @elseif($type === 'user')
                <td>{{ $row->user_name }}</td>
                <td>{{ $row->booking_type }}</td>
                <td>{{ $row->total_bookings }}</td>
                <td>{{ $row->booked_days }}</td>
                {{-- <td>{{ number_format($row->total_revenue, 2) }}</td> --}}
                <td>₹ {{ number_format($row->total_amount, 2) }}</td>
                <td>₹ {{ number_format($row->gst_amount, 2) }}</td>
                <td><strong>₹ {{ number_format($row->grand_total, 2) }}</strong></td>

            @endif
        </tr>
    @endforeach
    </tbody>
</table>

</body>
</html>

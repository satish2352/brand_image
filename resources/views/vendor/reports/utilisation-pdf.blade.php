<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0F172A; }
        h1 { font-size: 18px; margin: 0; }
        .muted { color: #475569; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th { background: #0F172A; color: #fff; text-align: left; padding: 6px; font-size: 10px; }
        td { padding: 6px; border-bottom: 1px solid #E5E7EB; }
    </style>
</head>
<body>
    <h1>{{ $vendor->vendor_name }}</h1>
    <div class="muted">Media Utilisation Report · {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }} ({{ $days }} days) · Powered by Brand Adda</div>
    <p>Media: <strong>{{ $summary['media'] }}</strong> · Booked days: <strong>{{ $summary['booked'] }}</strong> · Average utilisation: <strong>{{ $summary['avg'] }}%</strong> · Bookings: <strong>{{ $summary['bookings'] }}</strong></p>
    <table>
        <thead><tr><th>#</th><th>Sector Code</th><th>Media Title</th><th>Location</th><th>Booked By</th><th>Booked</th><th>Blocked</th><th>Free</th><th>Utilisation</th></tr></thead>
        <tbody>
            @foreach ($rows as $r)
                <tr><td>{{ $loop->iteration }}</td><td>{{ $r->code }}</td><td>{{ $r->title }}</td><td>{{ $r->location }}</td><td>{{ $r->users ?: '—' }}</td>
                    <td>{{ $r->booked }}</td><td>{{ $r->blocked }}</td><td>{{ $r->free }}</td><td>{{ $r->pct }}%</td></tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

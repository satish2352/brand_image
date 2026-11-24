<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>{{ $title }}</title></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #0F172A;">
    <h2 style="margin-bottom:4px">{{ $title }}</h2>
    <p>Hello {{ $booking->user_name ?: 'there' }},</p>
    <p>Please find attached the {{ strtolower($title) }} for your booking.</p>
    <table cellpadding="8" style="border-collapse:collapse;border:1px solid #E5E7EB">
        <tr><td style="background:#F8FAFC"><strong>Booking No.</strong></td><td>{{ $booking->order_no }}</td></tr>
        <tr><td style="background:#F8FAFC"><strong>Media</strong></td><td>{{ $booking->hoarding_code ?: $booking->media_code }} · {{ $booking->media_title }}{{ $booking->area_name ? ', ' . $booking->area_name : '' }}</td></tr>
        <tr><td style="background:#F8FAFC"><strong>Booking period</strong></td><td>{{ \Carbon\Carbon::parse($booking->from_date)->format('d M Y') }} – {{ \Carbon\Carbon::parse($booking->to_date)->format('d M Y') }}</td></tr>
        @if ($date)
            <tr><td style="background:#F8FAFC"><strong>Photo date</strong></td><td>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</td></tr>
        @endif
        <tr><td style="background:#F8FAFC"><strong>Media owner</strong></td><td>{{ $vendor->vendor_name }}</td></tr>
    </table>
    <p>Attached: Newspaper shot, Long shot and Close shot.</p>
    <p>Regards,<br><strong>{{ $vendor->vendor_name }}</strong> · Powered by Brand Adda</p>
</body>
</html>

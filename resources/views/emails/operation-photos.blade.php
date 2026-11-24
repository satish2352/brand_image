@extends('emails.layouts.brand')
@section('title', $title)
@section('preheader', $title . ' for booking ' . $booking->order_no . ' – ' . ($booking->hoarding_code ?: $booking->media_code) . '.')

@section('content')
    @php
        $cell = 'padding:11px 16px;border-bottom:1px solid #E5E7EB;font-size:14px;vertical-align:top';
        $label = $cell . ';width:140px;color:#64748B;font-weight:bold;background:#F8FAFC';
        $rows = [
            'Booking No.' => $booking->order_no,
            'Media' => trim(($booking->hoarding_code ?: $booking->media_code) . ' · ' . $booking->media_title . ($booking->area_name ? ', ' . $booking->area_name : ''), ' ·'),
            'Booking period' => \Carbon\Carbon::parse($booking->from_date)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($booking->to_date)->format('d M Y'),
        ];
        if ($date) {
            $rows['Photo date'] = \Carbon\Carbon::parse($date)->format('d M Y');
        }
        $rows['Media owner'] = $vendor->vendor_name;
    @endphp

    <h1 style="margin:0 0 6px;font-size:22px;line-height:1.3;color:#0F172A">{{ $title }}</h1>
    <p style="margin:0 0 22px;color:#64748B;font-size:14px">Booking {{ $booking->order_no }}</p>

    <p style="margin:0 0 14px">Hello <strong style="color:#0F172A">{{ $booking->user_name ?: 'there' }}</strong>,</p>
    <p style="margin:0 0 22px">Please find attached the {{ strtolower($title) }} for your booking.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #E5E7EB;border-radius:10px;border-collapse:separate;overflow:hidden;margin:0 0 22px">
        @foreach ($rows as $k => $v)
            <tr>
                <td style="{{ $label }}{{ $loop->last ? ';border-bottom:0' : '' }}">{{ $k }}</td>
                <td style="{{ $cell }}{{ $loop->last ? ';border-bottom:0' : '' }};color:#0F172A">{{ $v }}</td>
            </tr>
        @endforeach
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;margin:0 0 24px">
        <tr>
            <td style="padding:14px 16px;font-size:14px;color:#166534">
                <strong>Attached:</strong> Newspaper shot, Long shot and Close shot.
            </td>
        </tr>
    </table>

    <p style="margin:0">Regards,<br><strong style="color:#0F172A">{{ $vendor->vendor_name }}</strong><br>
        <span style="font-size:13px;color:#64748B">via Brand Adda</span></p>
@endsection

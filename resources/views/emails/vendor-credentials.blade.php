@extends('emails.layouts.brand')
@section('title', 'Your Brand Adda Vendor Panel login')
@section('preheader', 'Your Vendor Panel account for ' . $vendor->vendor_name . ' is ready. Sign in to manage your media inventory.')

@section('content')
    @php
        $loginUrl = route('vendor.login');
        $cell = 'padding:12px 16px;border-bottom:1px solid #E5E7EB;font-size:14px;vertical-align:top';
        $label = $cell . ';width:130px;color:#64748B;font-weight:bold;background:#F8FAFC';
    @endphp

    <h1 style="margin:0 0 6px;font-size:22px;line-height:1.3;color:#0F172A">Welcome to the Brand Adda Vendor Panel</h1>
    <p style="margin:0 0 20px;color:#64748B;font-size:14px">Your vendor account is ready.</p>

    <p style="margin:0 0 14px">Hello <strong style="color:#0F172A">{{ $vendor->vendor_name }}</strong>,</p>
    <p style="margin:0 0 22px">
        Brand Adda has created your Vendor Panel login. Use the details below to sign in and manage your
        media inventory, availability, bookings and operations in one place.
    </p>

    {{-- Login details --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #E5E7EB;border-radius:10px;border-collapse:separate;overflow:hidden;margin:0 0 24px">
        <tr>
            <td colspan="2" style="padding:12px 16px;background:#0F172A;color:#FFFFFF;font-size:13px;font-weight:bold;letter-spacing:.04em;text-transform:uppercase">Your login details</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Login page</td>
            <td style="{{ $cell }}"><a href="{{ $loginUrl }}" style="color:#C2410C;text-decoration:underline;word-break:break-all">{{ $loginUrl }}</a></td>
        </tr>
        <tr>
            <td style="{{ $label }}">Email ID</td>
            <td style="{{ $cell }};color:#0F172A;font-weight:bold;word-break:break-all">{{ $vendor->email }}</td>
        </tr>
        <tr>
            <td style="{{ $label }}">Password</td>
            <td style="{{ $cell }}">
                <span style="display:inline-block;padding:6px 12px;background:#FFF7ED;border:1px dashed #FDBA74;border-radius:6px;font-family:'Courier New',Courier,monospace;font-size:17px;font-weight:bold;letter-spacing:.08em;color:#0F172A">{{ $plainPassword }}</span>
            </td>
        </tr>
        <tr>
            <td style="{{ $label }};border-bottom:0">Vendor ID</td>
            <td style="{{ $cell }};border-bottom:0;color:#0F172A">{{ $vendor->vendor_code }}</td>
        </tr>
    </table>

    {{-- Button --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto 26px">
        <tr>
            <td align="center" style="border-radius:10px;background:#F97316">
                <a href="{{ $loginUrl }}" target="_blank"
                    style="display:inline-block;padding:14px 32px;font-size:15px;font-weight:bold;color:#0F172A;text-decoration:none;border-radius:10px">
                    Login to Vendor Panel &rarr;
                </a>
            </td>
        </tr>
    </table>

    {{-- What you can do --}}
    <p style="margin:0 0 10px;font-weight:bold;color:#0F172A">In your Vendor Panel you can:</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px">
        @foreach ([
            'Add new media and send it to Brand Adda for approval',
            'Update availability: Available, Booked, Blocked or Ask for Availability',
            'Upload installation, monitoring and end-date photos for bookings',
            'See enquiries, reports and notifications for your inventory',
        ] as $point)
            <tr>
                <td width="26" style="vertical-align:top;padding:3px 0;color:#16A34A;font-weight:bold;font-size:15px">&#10003;</td>
                <td style="padding:3px 0;font-size:14px">{{ $point }}</td>
            </tr>
        @endforeach
    </table>

    {{-- Website --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F8FAFC;border-radius:10px;margin:0 0 22px">
        <tr>
            <td style="padding:14px 16px;font-size:14px">
                <strong style="color:#0F172A">Your showcase website:</strong><br>
                <a href="{{ $vendor->website_url }}" style="color:#C2410C;word-break:break-all">{{ $vendor->website_url }}</a>
            </td>
        </tr>
    </table>

    {{-- Security --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;margin:0 0 24px">
        <tr>
            <td style="padding:14px 16px;font-size:13px;line-height:1.6;color:#7F1D1D">
                <strong>Keep these details private.</strong> Brand Adda will never ask for your password by phone or email.
                If you did not expect this email, or think someone else knows your password, contact your Brand Adda account manager
                to have a new password sent.
            </td>
        </tr>
    </table>

    <p style="margin:0">Regards,<br><strong style="color:#0F172A">Brand Adda Team</strong></p>
@endsection

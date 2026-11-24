{{--
    Shared layout for Brand Adda system emails (vendor login, website OTP,
    operation photos). Table-based with inline styles so it renders the same in
    Gmail, Outlook and phone mail apps. The logo is embedded in the email
    (cid:), so it shows even when the site runs on localhost.

    Sections: preheader (inbox preview line), content.
--}}
@php
    $logoPath = public_path('assets/img/logo/brand_adda.png');
    $logoSrc = isset($message) && is_file($logoPath) ? $message->embed($logoPath) : asset('assets/img/logo/brand_adda.png');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>@yield('title', 'Brand Adda')</title>
</head>
<body style="margin:0;padding:0;background:#F1F5F9;font-family:Arial,Helvetica,sans-serif;color:#0F172A;-webkit-text-size-adjust:100%">
    {{-- Inbox preview text (hidden in the email body) --}}
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent">@yield('preheader')</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F1F5F9">
        <tr>
            <td align="center" style="padding:28px 12px">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:100%">

                    {{-- Logo --}}
                    <tr>
                        <td align="center" style="padding:0 0 18px">
                            <img src="{{ $logoSrc }}" alt="Brand Adda" width="170" style="display:block;width:170px;max-width:60%;height:auto;border:0">
                        </td>
                    </tr>

                    {{-- Card --}}
                    <tr>
                        <td style="background:#FFFFFF;border-radius:14px;overflow:hidden;border:1px solid #E5E7EB">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr><td style="height:6px;background:#F97316;font-size:0;line-height:0">&nbsp;</td></tr>
                                <tr>
                                    <td style="padding:32px 36px 28px;font-size:15px;line-height:1.6;color:#334155">
                                        @yield('content')
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding:20px 24px 0;font-size:12px;line-height:1.6;color:#64748B">
                            <strong style="color:#334155">Brand Adda</strong> · Maharashtra’s Outdoor Media Platform<br>
                            This is an automated message. Please do not reply to this email.<br>
                            © {{ date('Y') }} Brand Adda. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

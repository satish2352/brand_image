@extends('emails.layouts.brand')
@section('title', 'Your verification code')
@section('preheader', 'Your code to open the ' . $vendor->display_name . ' website is ' . $otp . '. Valid for ' . $minutes . ' minutes.')

@section('content')
    <h1 style="margin:0 0 6px;font-size:22px;line-height:1.3;color:#0F172A">Your verification code</h1>
    <p style="margin:0 0 22px;color:#64748B;font-size:14px">{{ $vendor->display_name }} · Website access</p>

    <p style="margin:0 0 20px">Someone asked to open the <strong style="color:#0F172A">{{ $vendor->display_name }}</strong> website on Brand Adda.
        Enter this code on the verification screen to continue:</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto 20px">
        <tr>
            <td align="center" style="padding:16px 28px;background:#FFF7ED;border:2px dashed #FDBA74;border-radius:12px;font-family:'Courier New',Courier,monospace;font-size:34px;font-weight:bold;letter-spacing:10px;color:#0F172A">
                {{ $otp }}
            </td>
        </tr>
    </table>

    <p style="margin:0 0 22px;text-align:center;font-size:14px;color:#64748B">This code is valid for <strong style="color:#0F172A">{{ $minutes }} minutes</strong> and can be used once.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F8FAFC;border-radius:10px;margin:0 0 24px">
        <tr>
            <td style="padding:14px 16px;font-size:13px;line-height:1.6;color:#475569">
                Did not ask for this? You can safely ignore this email; nobody can open the website without the code.
                Never share this code with anyone.
            </td>
        </tr>
    </table>

    <p style="margin:0">Regards,<br><strong style="color:#0F172A">Brand Adda Team</strong></p>
@endsection

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Contact Enquiry</title>
</head>

{{-- Tables and inline styles throughout: Outlook and most webmail clients drop
     <style> blocks and modern layout properties, so the mail is built the same
     way the other templates in this folder are. --}}

<body style="margin:0;padding:0;background:#f5f6f8;font-family:Arial,Helvetica,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background:#f5f6f8;padding:30px 0;">
        <tr>
            <td align="center">

                <!-- MAIN CARD -->
                <table width="700" cellpadding="0" cellspacing="0" border="0"
                    style="background:#ffffff;max-width:700px;">

                    <!-- HEADER -->
                    <tr>
                        <td style="background:#0F172A;padding:24px;text-align:center;color:#ffffff;">
                            <h2 style="margin:0;font-size:26px;font-weight:bold;">
                                New Contact Enquiry
                            </h2>
                            <p style="margin:6px 0 0 0;font-size:14px;color:#F97316;">
                                Brand Adda &ndash; Website Contact Form
                            </p>
                        </td>
                    </tr>

                    <!-- BODY -->
                    <tr>
                        <td style="padding:30px;color:#333333;font-size:16px;line-height:1.7;">

                            <p style="margin-top:0;">Dear Sir/Ma'am,</p>

                            <p>
                                A visitor has submitted the contact form on the website. Their
                                details are below.
                            </p>

                            <!-- ENQUIRY DETAILS -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="background:#f9fafc;border:1px solid #e3e6ea;margin:20px 0;">
                                <tr>
                                    <td style="padding:18px;">

                                        <h3 style="margin:0 0 14px 0;font-size:18px;color:#0F172A;">
                                            Enquiry Details
                                        </h3>

                                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                            style="font-size:15px;color:#333333;">

                                            <tr>
                                                <td width="150" valign="top"
                                                    style="padding:8px 0;color:#6b7280;">
                                                    Full Name
                                                </td>
                                                <td valign="top" style="padding:8px 0;">
                                                    <strong>{{ $enquiry['full_name'] }}</strong>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td valign="top" style="padding:8px 0;color:#6b7280;">
                                                    Email
                                                </td>
                                                <td valign="top" style="padding:8px 0;">
                                                    <a href="mailto:{{ $enquiry['email'] }}"
                                                        style="color:#F97316;text-decoration:none;">
                                                        {{ $enquiry['email'] }}
                                                    </a>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td valign="top" style="padding:8px 0;color:#6b7280;">
                                                    Mobile
                                                </td>
                                                <td valign="top" style="padding:8px 0;">
                                                    <a href="tel:{{ $enquiry['mobile_no'] }}"
                                                        style="color:#F97316;text-decoration:none;">
                                                        {{ $enquiry['mobile_no'] }}
                                                    </a>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td valign="top" style="padding:8px 0;color:#6b7280;">
                                                    Address
                                                </td>
                                                <td valign="top" style="padding:8px 0;">
                                                    {{-- nl2br so a multi-line address arrives laid out as it
                                                         was typed; e() first because nl2br does not escape. --}}
                                                    {!! nl2br(e($enquiry['address'])) !!}
                                                </td>
                                            </tr>

                                            @if (!empty($enquiry['media_id']))
                                                <tr>
                                                    <td valign="top" style="padding:8px 0;color:#6b7280;">
                                                        Media ID
                                                    </td>
                                                    <td valign="top" style="padding:8px 0;">
                                                        #{{ $enquiry['media_id'] }}
                                                    </td>
                                                </tr>
                                            @endif

                                            <tr>
                                                <td valign="top" style="padding:8px 0;color:#6b7280;">
                                                    Received On
                                                </td>
                                                <td valign="top" style="padding:8px 0;">
                                                    {{ $enquiry['submitted_at'] }}
                                                </td>
                                            </tr>

                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- REQUIREMENTS -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="background:#f9fafc;border:1px solid #e3e6ea;margin-bottom:20px;">
                                <tr>
                                    <td style="padding:18px;">
                                        <h3 style="margin:0 0 10px 0;font-size:18px;color:#0F172A;">
                                            Requirements
                                        </h3>
                                        <p style="margin:0;font-size:15px;line-height:1.7;">
                                            {!! nl2br(e($enquiry['remark'])) !!}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin-bottom:0;">
                                You can reply directly to this email to reach
                                {{ $enquiry['full_name'] }}.
                            </p>

                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background:#0F172A;padding:18px;text-align:center;color:#ffffff;font-size:13px;">
                            &copy; {{ date('Y') }} Brand Adda Pvt. Ltd. All rights reserved.<br>
                            <span style="color:rgba(255,255,255,0.65);">
                                This is an automated notification from the Brand Adda website.
                            </span>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>

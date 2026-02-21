<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Campaign Created</title>
</head>

<body style="margin:0;padding:0;background:#f5f6f8;font-family:Arial,Helvetica,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f5f6f8;padding:30px 0;">
<tr>
<td align="center">

<!-- MAIN CARD -->
<table width="700" cellpadding="0" cellspacing="0" border="0"
style="background:#ffffff;max-width:700px;">

    <!-- HEADER -->
    <tr>
        <td style="background:#1f4e78;padding:22px;text-align:center;color:#ffffff;">
            <h2 style="margin:0;font-size:28px;font-weight:bold;">
                Campaign Created Successfully
            </h2>
        </td>
    </tr>

    <!-- BODY -->
    <tr>
        <td style="padding:30px;color:#333;font-size:16px;line-height:1.7;">

            <p style="margin-top:0;">Dear Sir/Ma'am,</p>

            <p>
                Greetings from <strong>Brand Image Pvt. Ltd.</strong>
            </p>

            <p>
                We are pleased to inform you that your campaign has been successfully created.
            </p>

            <!-- CAMPAIGN DETAILS -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0"
            style="background:#f9fafc;border:1px solid #e3e6ea;margin:20px 0;">
                <tr>
                    <td style="padding:18px;">
                        <h3 style="margin:0 0 10px 0;font-size:18px;color:#1f4e78;">
                            Campaign Details
                        </h3>

                        <p style="margin:0;font-size:16px;">
                            <strong>Campaign Name:</strong><br>
                            {{ $campaign->campaign_name }}
                        </p>
                    </td>
                </tr>
            </table>

            <!-- ATTACHMENTS -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0"
            style="background:#f9fafc;border:1px solid #e3e6ea;margin-bottom:20px;">
                <tr>
                    <td style="padding:18px;">

                        <h3 style="margin:0 0 10px 0;font-size:18px;color:#1f4e78;">
                            Attachments Included
                        </h3>

                        <p style="margin:8px 0;font-size:16px;">
                            <strong>Campaign.xlsx</strong> — Campaign Excel Report
                        </p>

                        <p style="margin:8px 0;font-size:16px;">
                            <strong>Campaign.pptx</strong> — Campaign Presentation
                        </p>

                    </td>
                </tr>
            </table>

            <p>The files are attached with this email for your reference.</p>

            <p style="margin-bottom:0;">
                Thank you for choosing Brand Image.
            </p>

        </td>
    </tr>

    <!-- FOOTER -->
    <tr>
        <td style="background:#f1f3f6;padding:15px;text-align:center;font-size:13px;color:#777;">
            © {{ date('Y') }} Brand Image Pvt. Ltd. All rights reserved.
        </td>
    </tr>

</table>

</td>
</tr>
</table>

</body>
</html>
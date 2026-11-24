<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Verification code</title></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #0F172A;">
    <h2>Your verification code</h2>
    <p>Someone asked to open the <strong>{{ $vendor->vendor_name }}</strong> website on Brand Adda. Enter this code to continue:</p>
    <h1 style="letter-spacing: 6px;">{{ $otp }}</h1>
    <p>The code is valid for <strong>{{ $minutes }} minutes</strong>. If you did not ask for it, you can ignore this email.</p>
    <p>Regards,<br><strong>Brand Adda Team</strong></p>
</body>
</html>

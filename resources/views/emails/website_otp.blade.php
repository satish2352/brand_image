{{-- <h2>Email Verification</h2>
<p>Your OTP is:</p>

<h1 style="letter-spacing:3px;">{{ $otp }}</h1>

<p>This OTP is valid for 2 minute.</p> --}}

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verify Your Email</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

    <h2>Hello,</h2>

    <p>
        Thank you for choosing <strong>Brand Image</strong>.
    </p>

    <p>
        To complete your email verification, please use the One-Time Password (OTP) given below:
    </p>

    <h1 style="letter-spacing: 4px; color: #000;">
        {{ $otp }}
    </h1>

    <p>
        This OTP is valid for <strong>2 minutes</strong>.
    </p>

    <p style="color: #b00020;">
        Please do not share this code with anyone for security reasons.
    </p>

    <br>

    <p>
        Regards,<br>
        <strong>Brand Image Team</strong>
    </p>

</body>
</html>

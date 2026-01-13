<!DOCTYPE html>
<html>
<body>
<h2>Hello {{ $name }},</h2>

<p>Your booking has been confirmed 🎯</p>

<p>Here are your login credentials:</p>

<p>
    <strong>Email:</strong> {{ $email }} <br>
    <strong>Password:</strong> {{ $password }}
</p>

<p>You can now login here:</p>
<p><a href="{{ url('/website/login') }}">{{ url('/website/login') }}</a></p>

<br>
<p>Thank You,<br>
<b>Brand Image Team</b></p>
</body>
</html>

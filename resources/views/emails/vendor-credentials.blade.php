<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Vendor Panel login</title></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #0F172A;">
    <h2 style="margin-bottom:4px">Welcome to the Brand Adda Vendor Panel</h2>
    <p>Hello {{ $vendor->vendor_name }},</p>
    <p>Your vendor account is ready. Use the details below to sign in and manage your media inventory, availability and operations.</p>
    <table cellpadding="8" style="border-collapse:collapse;border:1px solid #E5E7EB">
        <tr><td style="background:#F8FAFC"><strong>Login page</strong></td><td><a href="{{ route('vendor.login') }}">{{ route('vendor.login') }}</a></td></tr>
        <tr><td style="background:#F8FAFC"><strong>Email ID</strong></td><td>{{ $vendor->email }}</td></tr>
        <tr><td style="background:#F8FAFC"><strong>Password</strong></td><td style="font-family:monospace;font-size:16px">{{ $plainPassword }}</td></tr>
        <tr><td style="background:#F8FAFC"><strong>Your website</strong></td><td><a href="{{ $vendor->website_url }}">{{ $vendor->website_url }}</a></td></tr>
    </table>
    <p style="color:#B91C1C">Please keep these details private.</p>
    <p>Regards,<br><strong>Brand Adda Team</strong></p>
</body>
</html>

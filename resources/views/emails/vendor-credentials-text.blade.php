Welcome to the Brand Adda Vendor Panel

Hello {!! $vendor->vendor_name !!},

Brand Adda has created your Vendor Panel login. Use the details below to sign in and manage your media inventory, availability, bookings and operations.

YOUR LOGIN DETAILS
Login page: {!! route('vendor.login') !!}
Email ID:   {!! $vendor->email !!}
Password:   {!! $plainPassword !!}
Vendor ID:  {!! $vendor->vendor_code !!}

Your showcase website: {!! $vendor->website_url !!}

Keep these details private. Brand Adda will never ask for your password by phone or email. If you did not expect this email, contact your Brand Adda account manager to have a new password sent.

Regards,
Brand Adda Team

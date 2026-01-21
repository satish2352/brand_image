<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class WebsiteUser extends Authenticatable
{
    protected $table = 'website_users';

    protected $fillable = [
        'name', 'email', 'mobile_number', 'organisation', 'gst', 'password', 'otp', 'otp_expires_at', 'is_email_verified', 'is_active',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'otp_expires_at' => 'datetime',
    ];
}

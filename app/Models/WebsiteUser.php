<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class WebsiteUser extends Authenticatable
{
    protected $table = 'website_users';

    protected $fillable = [
        'name', 'email', 'mobile_number', 'organisation', 'gst', 'password', 'is_active',
    ];

    protected $hidden = ['password'];
}

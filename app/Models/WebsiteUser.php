<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class WebsiteUser extends Authenticatable
{
    protected $table = 'website_users';

    protected $fillable = [
        'name', 'email', 'mobile_number', 'organisation', 'city', 'user_type', 'gst', 'password', 'otp', 'otp_expires_at', 'is_email_verified', 'is_active', 'is_deleted',
    ];


    /**
     * What kind of buyer this account is, offered on the registration form.
     *
     * Stored as the label itself rather than a code: the column is a varchar
     * that was sitting unused, and the admin lists read better without a
     * lookup. Kept here so the form, the validator and any report share one
     * list.
     */
    public const USER_TYPES = [
        'Brand / Advertiser',
        'Advertising Agency',
        'Media Planner',
        'Event / Marketing Agency',
        'Media Owner',
        'Other',
    ];
    protected $hidden = ['password'];

    protected $casts = [
        'otp_expires_at' => 'datetime',
    ];
}

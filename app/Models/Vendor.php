<?php

// app/Models/Vendor.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $table = 'vendors';

    protected $fillable = [
        'state_id',
        'district_id',
        'city_id',
        'vendor_name',
        'vendor_code',
        'mobile',
        'email',
        'address',
        'is_active',
        'is_deleted',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_deleted' => 'boolean',
    ];
}

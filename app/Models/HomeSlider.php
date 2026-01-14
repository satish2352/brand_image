<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSlider extends Model
{
    protected $fillable = [
        'desktop_image',
        'mobile_image',
        'is_active',
        'is_deleted'
    ];
}

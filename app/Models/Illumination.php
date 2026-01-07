<?php

// app/Models/Illumination.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Illumination extends Model
{
    protected $fillable = [
        'illumination_name',
        'is_active',
        'is_deleted'
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_deleted' => 'boolean',
    ];
}


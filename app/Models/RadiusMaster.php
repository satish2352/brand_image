<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadiusMaster extends Model
{
    protected $table = 'radius_master';

    protected $fillable = [
        'radius',
        'is_active',
        'is_deleted',
    ];
}

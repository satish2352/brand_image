<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $table = 'areas';

    protected $fillable = [
        'state_id',
        'district_id',
        'city_id',
        'area_name',
        'common_stdiciar_name',
        // 'latitude',
        // 'longitude',
        'is_active',
        'is_deleted',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_deleted' => 'boolean',
    ];
}

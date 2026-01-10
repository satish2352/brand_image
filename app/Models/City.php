<?php

// app/Models/City.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = [
        'district_id',
        'city_name',
        'latitude',
        'longitude',
        'is_active',
        'is_deleted'
    ];

    public function district()
    {
        return $this->belongsTo(District::class);
    }
}

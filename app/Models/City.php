<?php

// app/Models/City.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\MediaManagement;

class City extends Model
{
    protected $fillable = [
        'state_id',
        'district_id',
        'city_name',
        'latitude',
        'longitude',
        'is_active',
        'is_deleted'
    ];
    public function state()
    {
        return $this->belongsTo(State::class);
    }
    public function district()
    {
        return $this->belongsTo(District::class);
    }
    public function media()
    {
        return $this->hasMany(MediaManagement::class, 'city_id');
    }
}

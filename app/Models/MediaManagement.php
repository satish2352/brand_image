<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaManagement extends Model
{
    use HasFactory;

    protected $table = 'media_management';

    protected $fillable = [
        'state_id',
        'district_id',
        'city_id',
        'area_id',
        'category_id',
        'media_code',
        'media_title',
        'address',
        'width',
        'height',
        'illumination_id',
        'facing_id',
        'latitude',
        'longitude',
        'minimum_booking_days',
        'price',
        'vendor_name',
        'is_active',
        'is_deleted',
    ];

    public function images()
    {
        return $this->hasMany(MediaImage::class, 'media_id');
    }
}

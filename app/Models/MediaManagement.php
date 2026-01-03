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
        'category_id',
        'city_id',
        'area_id',
        'width',
        'height',
        'latitude',
        'longitude',
        'price',
        'vendor_name',
        'media_code',
        'media_title',
        'address',
        'illumination_id',
        'facing_id',
        // 'minimum_booking_days',
        'mall_name',
        'media_format',
        'airport_name',
        'zone_type',
        'media_type',
        'transit_type',
        'branding_type',
        'vehicle_count',
        'building_name',
        'wall_length',
        'area_auto',
        'radius_id',
        'area_type',
        'is_active',
        'is_deleted',
    ];

    public function images()
    {
        return $this->hasMany(MediaImage::class, 'media_id');
    }
}

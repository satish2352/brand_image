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
        // 'vendor_name',
        'vendor_id',
        'media_code',
        'media_title',
        'address',
        'illumination_id',
        'facing_id',
        'facing',
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
        'areatype_id',
        'highway_id',
        'hoarding_code',
        // 'video_link',
        'panorama_image',
        'is_active',
        'is_deleted',
    ];

    public function images()
    {
        return $this->hasMany(MediaImage::class, 'media_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Highway this hoarding belongs to (one highway per hoarding).
     */
    public function highway()
    {
        return $this->belongsTo(Highway::class, 'highway_id');
    }

    /**
     * Landmarks tagged on this hoarding (many-to-many).
     */
    public function landmarks()
    {
        return $this->belongsToMany(
            Landmark::class,
            'media_landmark',
            'media_id',
            'landmark_id'
        )->withTimestamps();
    }
}

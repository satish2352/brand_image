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
        // roadstar_* columns are deliberately not fillable: only
        // RoadStarSyncService writes them, never a media form.
    ];

    protected $casts = [
        'roadstar_last_synced_at'  => 'datetime',
        'roadstar_last_attempt_at' => 'datetime',
    ];

    public function images()
    {
        return $this->hasMany(MediaImage::class, 'media_id');
    }

    /**
     * Per-panel dimensions (Bus Shelter's Front / Back / Side). Empty for
     * categories that are a single Width x Height.
     */
    public function locationSizes()
    {
        return $this->hasMany(MediaLocationSize::class, 'media_id');
    }

    /**
     * The panels keyed by position, so a form or a details page can ask for
     * `$media->locationSizesByPosition['front']` without a lookup loop.
     */
    public function getLocationSizesByPositionAttribute()
    {
        return $this->locationSizes->keyBy('position');
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
     * Stored TomTom Traffic Flow (refreshed every 15 days by the scheduler).
     */
    public function trafficData()
    {
        return $this->hasOne(HoardingTrafficData::class, 'media_id');
    }

    /**
     * RoadStar audience, one row per synced period (refreshed every 15 days).
     */
    public function roadStarAudience()
    {
        return $this->hasMany(RoadStarAudienceData::class, 'media_id');
    }

    /**
     * The most recently fetched RoadStar audience row.
     */
    public function latestRoadStarAudience()
    {
        return $this->hasOne(RoadStarAudienceData::class, 'media_id')->latestOfMany('fetched_at');
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

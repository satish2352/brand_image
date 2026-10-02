<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stored TomTom Traffic Flow for one hoarding (media_management row).
 * Written only by HoardingTrafficService (scheduled refresh); read by pages.
 * current_speed / free_flow_speed are road speeds in KMPH, not vehicles/day.
 */
class HoardingTrafficData extends Model
{
    public const SUCCESS = 'success';
    public const FAILED = 'failed';

    protected $table = 'hoarding_traffic_data';

    protected $fillable = [
        'media_id',
        'latitude',
        'longitude',
        'frc',
        'current_speed',
        'free_flow_speed',
        'current_travel_time',
        'free_flow_travel_time',
        'confidence',
        'road_closure',
        'traffic_updated_at',
        'next_refresh_at',
        'last_api_status',
        'last_api_error',
        'last_http_status',
        'last_attempt_at',
        'raw_response',
    ];

    protected $casts = [
        'latitude'              => 'decimal:7',
        'longitude'             => 'decimal:7',
        'current_speed'         => 'decimal:2',
        'free_flow_speed'       => 'decimal:2',
        'current_travel_time'   => 'integer',
        'free_flow_travel_time' => 'integer',
        'confidence'            => 'decimal:4',
        'road_closure'          => 'boolean',
        'last_http_status'      => 'integer',
        'traffic_updated_at'    => 'datetime',
        'next_refresh_at'       => 'datetime',
        'last_attempt_at'       => 'datetime',
        'raw_response'          => 'array',
    ];

    public function hoarding()
    {
        return $this->belongsTo(MediaManagement::class, 'media_id');
    }
}

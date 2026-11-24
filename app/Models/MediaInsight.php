<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Calculated location insights for one media (the PRD's nine points).
 * Every score carries a *_status: estimated | incomplete | unavailable.
 * places_* columns record which coordinates / provider the nearby places
 * were fetched for, and when they expire.
 */
class MediaInsight extends Model
{
    protected $table = 'media_insights';

    protected $fillable = [
        'media_id',
        'place_result_id',
        'visibility_score',
        'visibility_status',
        'premium_score',
        'premium_status',
        'value_score',
        'value_status',
        'audience',
        'recommendations',
        'nearby_places',
        'details',
        'places_provider',
        'places_latitude',
        'places_longitude',
        'places_fetched_at',
        'places_expires_at',
        'failed_attempts',
        'last_attempt_at',
        'calculated_at',
    ];

    protected $casts = [
        'audience'          => 'array',
        'recommendations'   => 'array',
        'nearby_places'     => 'array',
        'details'           => 'array',
        'places_fetched_at' => 'datetime',
        'places_expires_at' => 'datetime',
        'last_attempt_at'   => 'datetime',
        'calculated_at'     => 'datetime',
    ];

    public function placeResult()
    {
        return $this->belongsTo(PlaceResult::class, 'place_result_id');
    }
}

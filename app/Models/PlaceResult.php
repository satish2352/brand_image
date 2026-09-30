<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One saved nearby-places result for a provider + rounded location, shared by
 * every hoarding standing there. `places` holds normalised fields only.
 */
class PlaceResult extends Model
{
    protected $table = 'place_results';

    protected $fillable = [
        'provider',
        'location_key',
        'query',
        'latitude',
        'longitude',
        'radius_m',
        'places',
        'place_count',
        'external_id',
        'fetched_at',
        'expires_at',
    ];

    protected $casts = [
        'places'     => 'array',
        'fetched_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function isFresh(): bool
    {
        return $this->expires_at && $this->expires_at->isFuture();
    }
}

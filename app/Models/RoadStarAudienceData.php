<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * RoadStar audience (POST /sitedata) for one hoarding over one period.
 * Written only by RoadStarSyncService; read by the admin details page.
 * Breakdown columns are lists of ['label' => ..., 'value' => ...] in
 * RoadStar's order — see the migration for the field mapping.
 */
class RoadStarAudienceData extends Model
{
    protected $table = 'roadstar_audience_data';

    protected $fillable = [
        'media_id',
        'roadstar_site_id',
        'period_start',
        'period_end',
        'unique_reach',
        'impressions',
        'frequency',
        'date_wise_impressions',
        'day_wise_avg_impressions',
        'month_wise_impressions',
        'hourly_avg_impressions',
        'effective_frequency',
        'weekday_weekend_impressions',
        'age_groups',
        'gender',
        'mobile_affluence',
        'mobile_brands',
        'raw_response',
        'fetched_at',
    ];

    protected $casts = [
        'period_start'                => 'date',
        'period_end'                  => 'date',
        'unique_reach'                => 'integer',
        'impressions'                 => 'integer',
        'frequency'                   => 'decimal:2',
        'date_wise_impressions'       => 'array',
        'day_wise_avg_impressions'    => 'array',
        'month_wise_impressions'      => 'array',
        'hourly_avg_impressions'      => 'array',
        'effective_frequency'         => 'array',
        'weekday_weekend_impressions' => 'array',
        'age_groups'                  => 'array',
        'gender'                      => 'array',
        'mobile_affluence'            => 'array',
        'mobile_brands'               => 'array',
        'raw_response'                => 'array',
        'fetched_at'                  => 'datetime',
    ];

    public function hoarding()
    {
        return $this->belongsTo(MediaManagement::class, 'media_id');
    }
}

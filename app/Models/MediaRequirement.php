<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A brief sent in through "Share your requirement".
 *
 * @see \App\Http\Controllers\Website\RequirementController
 * @see \App\Http\Controllers\Superadm\MediaRequirementController
 */
class MediaRequirement extends Model
{
    protected $table = 'media_requirements';

    public const STATUS_NEW         = 'new';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_CLOSED      = 'closed';

    protected $fillable = [
        'user_id',
        'full_name',
        'email',
        'mobile_no',
        'campaign_name',
        'city',
        'area_location',
        'media_type',
        'campaign_start_date',
        'campaign_end_date',
        'campaign_duration',
        'required_media_count',
        'approx_budget',
        'target_audience',
        'preferred_location',
        'preferred_media_size',
        'additional_comments',
        'status',
        'source',
    ];

    protected $casts = [
        'campaign_start_date' => 'date',
        'campaign_end_date'   => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(WebsiteUser::class, 'user_id');
    }

    /** Human label for the working state, for the admin list. */
    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_CLOSED      => 'Closed',
            default                  => 'New',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $table = 'campaign';

    protected $fillable = [
        'user_id',
        'campaign_name',
    ];

    /**
     * A cart item belongs to a cart
     */
    public function cart()
    {
        return $this->belongsTo(WebsiteUser::class, 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $table = 'cart_items';

    protected $fillable = [
        'user_id',
        'media_id',
        'campaign_id',   // ✅ ADD THIS
        'cart_type',      // ✅ ADD THIS
        'session_id',
        'price',
        'qty',
        'from_date',
        'to_date',
        'per_day_price',
        'total_price',
        'total_days',
    ];

    /**
     * A cart item belongs to a cart
     */
    public function cart()
    {
        return $this->belongsTo(WebsiteUser::class, 'user_id');
    }
}

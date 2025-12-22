<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $table = 'carts';

    protected $fillable = [
        'user_id',
        'session_id',
        'campaign_name',
    ];

    /**
     * A cart has many items
     */
    public function items()
    {
        return $this->hasMany(CartItem::class, 'cart_id');
    }
}

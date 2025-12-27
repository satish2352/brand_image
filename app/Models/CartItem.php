<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $table = 'cart_items';

    protected $fillable = [
        'user_id',
        'media_id',
        'session_id',
        'price',
        'qty',
    ];

    /**
     * A cart item belongs to a cart
     */
    public function cart()
    {
        return $this->belongsTo(WebsiteUser::class, 'user_id');
    }
}

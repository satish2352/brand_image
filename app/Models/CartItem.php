<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $table = 'cart_items';

    protected $fillable = [
        'cart_id',
        'media_id',
        'price',
        'qty',
    ];

    /**
     * A cart item belongs to a cart
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'order_no',
        'total_amount',
        'payment_status'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}

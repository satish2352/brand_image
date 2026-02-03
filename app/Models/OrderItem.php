<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'media_id',
        'from_date',
        'to_date',
        'qty',
        'price',
        'per_day_price',
        'total_days',
        'total_price',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date'   => 'date',
    ];
// Order item belongs to order
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    // Order item belongs to media
    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}

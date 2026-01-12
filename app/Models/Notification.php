<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['media_id', 'user_id', 'order_id', 'is_read'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function media()
    {
        return $this->belongsTo(MediaManagement::class, 'media_id');
    }

    // Website user (Frontend logged in user)
    public function customer()
    {
        return $this->belongsTo(\App\Models\WebsiteUser::class, 'user_id');
    }
}

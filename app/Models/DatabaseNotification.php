<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification as BaseModel;

class DatabaseNotification extends BaseModel
{
    protected $fillable = [
        'id',
        'user_id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}

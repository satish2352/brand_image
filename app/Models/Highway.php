<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Highway extends Model
{
    protected $table = 'highway';

    protected $fillable = [
        'highway_name',
        'highway_type',
        'is_active',
        'is_deleted'
    ];
}

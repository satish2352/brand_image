<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Landmark extends Model
{
    protected $table = 'landmark';

    protected $fillable = [
        'landmark_name',
        'is_active',
        'is_deleted'
    ];
}

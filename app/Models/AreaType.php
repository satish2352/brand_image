<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AreaType extends Model
{
    protected $table = 'areatype';

    protected $fillable = [
        'areatype_name',
        'is_active',
        'is_deleted'
    ];
}

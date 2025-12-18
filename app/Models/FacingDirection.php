<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FacingDirection extends Model
{
    use HasFactory;

    protected $table = 'facing_direction'; // change if your table name is different

    protected $fillable = [
        'facing_name',
        'is_active',
        'is_deleted',
    ];
}

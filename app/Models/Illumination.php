<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Illumination extends Model
{
    use HasFactory;

    protected $table = 'illumination'; // change if your table name is different

    protected $fillable = [
        'illumination_name',
        'is_active',
        'is_deleted',
    ];
}

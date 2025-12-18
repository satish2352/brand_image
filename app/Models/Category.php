<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'category'; // change if your table name is different

    protected $fillable = [
        'category_name',
        'is_active',
        'is_deleted',
    ];
}

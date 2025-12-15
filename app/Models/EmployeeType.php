<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeType extends Model
{
    public $timestamps = true; 
    protected $fillable = [
        'type_name',
        'description',
        'is_active',
        'is_deleted',
    ];
}

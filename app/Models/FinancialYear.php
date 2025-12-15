<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialYear extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'year',
        'is_active',
        'is_deleted',
    ];
}

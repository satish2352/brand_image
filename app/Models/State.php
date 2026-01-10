<?php

// app/Models/State.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $fillable = [
        'state_name',
        'is_active',
        'is_deleted'
    ];

    public function districts()
    {
        return $this->hasMany(District::class);
    }
}

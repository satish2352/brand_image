<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $fillable = ['state', 'is_active', 'is_deleted'];
}

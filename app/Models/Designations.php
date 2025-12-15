<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Designations extends Model
{
    public $table = 'designations';
    public $timestamps = true;
    protected $fillable = ['designation','short_description', 'is_active'];

    public function employees()
    {
        return $this->hasMany(Employees::class, 'designation_id');
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantMasters extends Model
{
    public $table = 'plant_masters';
    public $timestamps = true;
    protected $fillable = ['plant_code','plant_name','address','city','plant_short_name', 'created_by', 'is_active'];
    
    public function departments()
    {
        return $this->hasMany(Departments::class, 'plant_id', 'id');
    }
}

      
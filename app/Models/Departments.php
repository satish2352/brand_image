<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departments extends Model
{
    public $table = 'departments';
    public $timestamps = true;
    protected $fillable = ['plant_id','department_code','department_name','department_short_name', 'created_by', 'is_active'];

    public function plant()
    {
        return $this->belongsTo(PlantMasters::class, 'plant_id', 'id');
    }
    
}

 
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Projects extends Model
{
     public $table = 'projects';
    public $timestamps = true;
    protected $fillable = ['plant_id','project_name','project_description','project_url', 'is_active'];

    // Project.php
    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class, 'financial_year_id');
    }

}

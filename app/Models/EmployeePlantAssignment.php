<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Departments;
use App\Models\Projects;

class EmployeePlantAssignment extends Model
{
    use HasFactory;

    protected $table = 'employee_plant_assignments';
    protected $fillable = [
        'employee_id',
        'plant_id',
        'department_id',
        'projects_id',
        'is_active',
        'is_deleted',
        'send_api'
    ];

    protected $casts = [
        'department_id' => 'array',
        'projects_id'   => 'array',
    ];

    // Relationships
    public function employee() {
        return $this->belongsTo(Employees::class, 'employee_id');
    }

    public function plant() {
        return $this->belongsTo(PlantMasters::class, 'plant_id');
    }

    // Accessors for department and project names
    public function getDepartmentsNamesAttribute() {
        $ids = $this->department_id; // Already an array
        $ids = is_array($ids) ? $ids : [];
        if(empty($ids)) return '-';
        return Departments::whereIn('id', $ids)->pluck('department_name')->implode(', ');
    }

    public function getProjectsNamesAttribute() {
        $ids = $this->projects_id; // Already an array
        $ids = is_array($ids) ? $ids : [];
        if(empty($ids)) return '-';
        return Projects::whereIn('id', $ids)->pluck('project_name')->implode(', ');
    }




}

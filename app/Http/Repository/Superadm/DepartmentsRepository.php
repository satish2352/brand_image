<?php

namespace App\Http\Repository\Superadm;

use Illuminate\Http\Request;
use App\Models\Departments;
use DB;
use Exception;
use Illuminate\Support\Facades\Log;

class DepartmentsRepository
{
    public function list()
    {
        try {
            return Departments::leftJoin('plant_masters', 'departments.plant_id', '=', 'plant_masters.id')
                    ->where('departments.is_deleted', 0)
                    ->orderBy('departments.id', 'desc')
                    ->select(
                        'departments.*',
                        'plant_masters.plant_name',
                        'plant_masters.plant_code',
                        'plant_masters.city'
                    )
                    ->get();

        } catch (Exception $e) {
            Log::error("Error fetching project list: " . $e->getMessage());
            return collect(); // return empty collection on error
        }
    }

    public function listajaxlist($plant_id)
    {
        try {

            return Departments::where('is_deleted', 0)
				->where('plant_id', $plant_id)
				->where('is_active', 1)
				->orderBy('id', 'desc')
				->get();


        } catch (Exception $e) {
            Log::error("Error fetching project list: " . $e->getMessage());
            return collect(); // return empty collection on error
        }
    }


    public function save($data)
    {
        try {
            return Departments::create($data);
        } catch (Exception $e) {
            Log::error("Error saving project: " . $e->getMessage());
            return false;
        }
    }

    public function edit($id)
    {
        try {
            return Departments::where('id', $id)->first();
        } catch (Exception $e) {
            Log::error("Error editing project ID {$id}: " . $e->getMessage());
            return null;
        }
    }

    public function update($data, $id)
    {
        try {
            return Departments::where('id', $id)->update($data);
        } catch (Exception $e) {
            Log::error("Error updating project ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    public function delete($data, $id)
    {
        try {
            return Departments::where('id', $id)->update($data);
        } catch (Exception $e) {
            Log::error("Error deleting project ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    public function updateStatus($data, $id)
    {
        try {
            return Departments::where('id', $id)->update($data);
        } catch (Exception $e) {
            Log::error("Error updating status for project ID {$id}: " . $e->getMessage());
            return false;
        }
    }
}

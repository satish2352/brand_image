<?php

namespace App\Http\Repository\Superadm;

use Illuminate\Http\Request;
use App\Models\Projects;
use DB;
use Exception;
use Illuminate\Support\Facades\Log;

class ProjectsRepository
{
    public function list()
    {
        try {
            return Projects::leftJoin('plant_masters', 'projects.plant_id', '=', 'plant_masters.id')
                ->where('projects.is_deleted', 0)
                ->orderBy('projects.id', 'desc')
                ->select(
                    'projects.*',
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

    public function listajaxlist($palnt_id)
    {
        try {
            return Projects::where('is_deleted', 0)
                ->where('plant_id', $palnt_id)
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
            // dd($data);
            return Projects::create($data);
        } catch (Exception $e) {
            Log::error("Error saving project: " . $e->getMessage());
            return false;
        }
    }

    public function edit($id)
    {
        try {
            return Projects::where('id', $id)->first();
        } catch (Exception $e) {
            Log::error("Error editing project ID {$id}: " . $e->getMessage());
            return null;
        }
    }

    public function update($data, $id)
    {
        try {
            return Projects::where('id', $id)->update($data);
        } catch (Exception $e) {
            Log::error("Error updating project ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    public function delete($data, $id)
    {
        try {
            return Projects::where('id', $id)->update($data);
        } catch (Exception $e) {
            Log::error("Error deleting project ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    public function updateStatus($data, $id)
    {
        try {
            return Projects::where('id', $id)->update($data);
        } catch (Exception $e) {
            Log::error("Error updating status for project ID {$id}: " . $e->getMessage());
            return false;
        }
    }
}

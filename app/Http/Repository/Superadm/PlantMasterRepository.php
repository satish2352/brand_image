<?php

namespace App\Http\Repository\Superadm;

use Illuminate\Http\Request;
use App\Models\PlantMasters;
use DB;
use Exception;
use Illuminate\Support\Facades\Log;

class PlantMasterRepository
{
    public function list()
    {
        try {
            return PlantMasters::where('is_deleted', 0)
                ->orderBy('id', 'desc')
                ->get();
        } catch (Exception $e) {
            Log::error("Error fetching plant list: " . $e->getMessage());
            return collect(); 
        }
    }

    public function save($data)
    {
        try {
            return PlantMasters::create($data);
            
        } catch (Exception $e) {
            Log::error("Error saving plant: " . $e->getMessage());
            return false;
        }
    }

    public function edit($id)
    {
        try {
            return PlantMasters::where('id', $id)->first();
        } catch (Exception $e) {
            Log::error("Error editing plant ID {$id}: " . $e->getMessage());
            return null;
        }
    }

    public function update($data, $id)
    {
        try {
            return PlantMasters::where('id', $id)->update($data);
        } catch (Exception $e) {
            Log::error("Error updating plant ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    public function delete($data, $id)
    {
        try {
            return PlantMasters::where('id', $id)->update($data);
        } catch (Exception $e) {
            Log::error("Error deleting plant ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    public function updateStatus($data, $id)
    {
        try {
            return PlantMasters::where('id', $id)->update($data);
        } catch (Exception $e) {
            Log::error("Error updating status for plant ID {$id}: " . $e->getMessage());
            return false;
        }
    }
}

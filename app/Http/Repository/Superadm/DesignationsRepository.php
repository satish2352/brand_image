<?php

namespace App\Http\Repository\Superadm;

use Illuminate\Http\Request;
use App\Models\Designations;
use DB;
use Exception;
use Illuminate\Support\Facades\Log;

class DesignationsRepository
{
    public function list()
    {
        try {
            return Designations::where('is_deleted', 0)
                ->orderBy('id', 'desc')
                ->get();
        } catch (Exception $e) {
            Log::error("Error fetching role list: " . $e->getMessage());
            return collect(); 
        }
    }

    public function save($data)
    {
        try {
            return Designations::create($data);
        } catch (Exception $e) {
            Log::error("Error saving role: " . $e->getMessage());
            return false;
        }
    }

    public function edit($id)
    {
        try {
            return Designations::where('id', $id)->first();
        } catch (Exception $e) {
            Log::error("Error editing designation ID {$id}: " . $e->getMessage());
            return null;
        }
    }

    public function update($data, $id)
    {
        try {
            return Designations::where('id', $id)->update($data);
        } catch (Exception $e) {
            Log::error("Error updating designation ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    public function delete($data, $id)
    {
        try {
            return Designations::where('id', $id)->update($data);
        } catch (Exception $e) {
            Log::error("Error deleting designation ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    public function updateStatus($data, $id)
    {
        try {
            return Designations::where('id', $id)->update($data);
        } catch (Exception $e) {
            Log::error("Error updating status for designation ID {$id}: " . $e->getMessage());
            return false;
        }
    }
}

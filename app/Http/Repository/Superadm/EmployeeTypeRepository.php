<?php

namespace App\Http\Repository\Superadm;

use App\Models\EmployeeType;
use Exception;
use Illuminate\Support\Facades\Log;

class EmployeeTypeRepository
{
    // List all employee types
    public function list()
    {
        try {
            return EmployeeType::where('is_deleted', 0)
                ->orderBy('id', 'desc')
                ->get();
        } catch (Exception $e) {
            Log::error("Error fetching employee type list: " . $e->getMessage());
            return collect(); // return empty collection
        }
    }

    // Save new employee type
    public function save($data)
    {
        try {
            return EmployeeType::create($data);
        } catch (Exception $e) {
            Log::error("Error saving employee type: " . $e->getMessage());
            return false;
        }
    }

    // Get employee type by ID
    public function edit($id)
    {
        try {
            return EmployeeType::where('id', $id)->first();
        } catch (Exception $e) {
            Log::error("Error fetching employee type ID {$id}: " . $e->getMessage());
            return null;
        }
    }

    // Update employee type
    public function update($data, $id)
    {
        try {
            return EmployeeType::where('id', $id)->update($data);
        } catch (Exception $e) {
            Log::error("Error updating employee type ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    // Soft delete employee type
    public function delete($data, $id)
    {
        try {
            return EmployeeType::where('id', $id)->update($data);
        } catch (Exception $e) {
            Log::error("Error deleting employee type ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    // Update status
    public function updateStatus($data, $id)
    {
        try {
            return EmployeeType::where('id', $id)->update($data);
        } catch (Exception $e) {
            Log::error("Error updating status for employee type ID {$id}: " . $e->getMessage());
            return false;
        }
    }
}

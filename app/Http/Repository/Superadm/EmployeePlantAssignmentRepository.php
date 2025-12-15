<?php

namespace App\Http\Repository\Superadm;

use App\Models\EmployeePlantAssignment;
use Exception;
use Log;

class EmployeePlantAssignmentRepository
{
    public function list()
    {
        try {
            return EmployeePlantAssignment::with(['employee','plant'])
                ->where('is_deleted',0)
                ->orderBy('id','desc')
                ->get();
        } catch(Exception $e) {
            Log::error("EmployeePlantAssignmentRepository list: ".$e->getMessage());
            return collect();
        }
    }


    public function save($data)
    {
        try {
            return EmployeePlantAssignment::create($data);
        } catch(Exception $e) {
            Log::error("EmployeePlantAssignmentRepository save: ".$e->getMessage());
            return false;
        }
    }

    public function getById($id)
    {
        try {
            return EmployeePlantAssignment::find($id);
        } catch(Exception $e) {
            Log::error("EmployeePlantAssignmentRepository getById: ".$e->getMessage());
            return null;
        }
    }

    public function update($id, $data)
    {
        try {
            return EmployeePlantAssignment::where('id',$id)->update($data);
        } catch(Exception $e) {
            Log::error("EmployeePlantAssignmentRepository update: ".$e->getMessage());
            return false;
        }
    }

    public function delete($id, $data = [])
    {
        try {
            return EmployeePlantAssignment::where('id',$id)->update($data);
        } catch(Exception $e) {
            Log::error("EmployeePlantAssignmentRepository delete: ".$e->getMessage());
            return false;
        }
    }

    public function updateStatus($id, $data)
    {
        try {
            return EmployeePlantAssignment::where('id',$id)->update($data);
        } catch(Exception $e) {
            Log::error("EmployeePlantAssignmentRepository updateStatus: ".$e->getMessage());
            return false;
        }
    }
}

<?php

namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\EmployeePlantAssignmentRepository;
use Exception;
use Log;

class EmployeePlantAssignmentService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new EmployeePlantAssignmentRepository();
    }

    public function list()
    {
        try {
            return $this->repo->list();
        } catch(Exception $e) {
            Log::error("EmployeePlantAssignmentService list: ".$e->getMessage());
            return false;
        }
    }

    public function save($req)
    {
        try {
            $data = [
                'employee_id' => $req->employee_id,
                'plant_id' => $req->plant_id,
                // 'department_id' => implode(',', $req->department_id ?? []),
                // 'projects_id' => implode(',', $req->projects_id ?? []),
'department_id' => $req->department_id ?? [],
'projects_id'   => $req->projects_id ?? [],
                'is_active' => 1,
                'is_deleted' => 0
            ];
            return $this->repo->save($data);
        } catch(Exception $e) {
            Log::error("EmployeePlantAssignmentService save: ".$e->getMessage());
            return false;
        }
    }

    public function getById($id)
    {
        return $this->repo->getById($id);
    }

    // public function update($req, $id)
    // {
    //     try {
    //         $data = [
    //             'employee_id' => $req->employee_id,
    //             'plant_id' => $req->plant_id,
    //             // 'department_id' => implode(',', $req->department_id ?? []),
    //             // 'projects_id' => implode(',', $req->projects_id ?? []),
    //             'department_id' => $req->department_id ?? [],
    //             'projects_id'   => $req->projects_id ?? [],
    //             'is_active' => $req->is_active, // allow status update
    //             'send_api'      => 0,
    //         ];
    //         return $this->repo->update($id, $data);
    //     } catch(Exception $e) {
    //         Log::error("EmployeePlantAssignmentService update: ".$e->getMessage());
    //         return false;
    //     }
    // }

    // change after add send_api col logic
    public function update($req, $id)
    {
        try {
            $assignment = $this->repo->getById($id);

            // Convert DB values to comparable format
            $oldData = [
                'employee_id'   => $assignment->employee_id,
                'plant_id'      => $assignment->plant_id,
                'department_id' => is_array($assignment->department_id) ? $assignment->department_id : (array) $assignment->department_id,
                'projects_id'   => is_array($assignment->projects_id) ? $assignment->projects_id : (array) $assignment->projects_id,
                'is_active'     => $assignment->is_active,
            ];

            $newData = [
                'employee_id'   => $req->employee_id,
                'plant_id'      => $req->plant_id,
                'department_id' => $req->department_id ?? [],
                'projects_id'   => $req->projects_id ?? [],
                'is_active'     => $req->is_active,
            ];

            // Compare data
            $hasChanges = $oldData['employee_id'] != $newData['employee_id'] ||
                        $oldData['plant_id']    != $newData['plant_id'] ||
                        json_encode($oldData['department_id']) != json_encode($newData['department_id']) ||
                        json_encode($oldData['projects_id'])   != json_encode($newData['projects_id']) ||
                        $oldData['is_active']   != $newData['is_active'];

            // If there are changes, reset send_api = 0
            if ($hasChanges) {
                $newData['send_api'] = 0;
            }

            return $this->repo->update($id, $newData);

        } catch(Exception $e) {
            Log::error("EmployeePlantAssignmentService update: " . $e->getMessage());
            return false;
        }
    }


    public function delete($req)
    {
        try {
            $id = base64_decode($req->id);
            return $this->repo->delete($id, ['is_deleted'=>1]);
        } catch(Exception $e) {
            Log::error("EmployeePlantAssignmentService delete: ".$e->getMessage());
            return false;
        }
    }

    public function updateStatus($req)
    {
        try {
            $id = base64_decode($req->id);
            return $this->repo->updateStatus($id, ['is_active'=>$req->is_active]);
        } catch(Exception $e) {
            Log::error("EmployeePlantAssignmentService updateStatus: ".$e->getMessage());
            return false;
        }
    }

    public function exists($employeeId, $plantId, $excludeId = null)
    {
        $query = \DB::table('employee_plant_assignments')
            ->where('employee_id', $employeeId)
            ->where('plant_id', $plantId)
            ->where('is_deleted', 0); // Only consider non-deleted assignments

        if($excludeId){
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }



}

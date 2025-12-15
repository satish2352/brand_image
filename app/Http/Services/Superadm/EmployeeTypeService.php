<?php
namespace App\Http\Services\Superadm;

use Illuminate\Http\Request;
use App\Http\Repository\Superadm\EmployeeTypeRepository;
use Exception;
use Log;

class EmployeeTypeService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new EmployeeTypeRepository();
    }

    public function list()
    {
        try {
            return $this->repo->list();
        } catch (Exception $e) {
            Log::error("EmployeeTypeService list error: " . $e->getMessage());
            return false;
        }
    }

    public function save($req)
    {
        try {
            $data = [
                'type_name' => $req->input('type_name'),
                'description' => $req->input('description'),
            ];
            return $this->repo->save($data);
        } catch (Exception $e) {
            Log::error("EmployeeTypeService save error: " . $e->getMessage());
            return false;
        }
    }

    public function edit($id)
    {
        try {
            return $this->repo->edit($id);
        } catch (Exception $e) {
            Log::error("EmployeeTypeService edit error: " . $e->getMessage());
            return false;
        }
    }

    public function update($req)
    {
        try {
            $id = $req->id;
            $data = [
                'type_name' => $req->input('type_name'),
                'description' => $req->input('description'),
                'is_active' => $req->is_active
            ];

            return $this->repo->update($data, $id);
        } catch (Exception $e) {
            Log::error("EmployeeTypeService update error: " . $e->getMessage());
            return false;
        }
    }

    public function delete($req)
    {
        try {
            $id = base64_decode($req->id);

            // Get employee type details
            $type = $this->repo->edit($id);
            $typeName = $type->type_name ?? 'This employee type';

            // Check if any employee has this type assigned
            $employeeCount = \DB::table('employees')
                ->where('employee_type', $id)
                ->where('is_deleted', 0)
                ->count();

            if ($employeeCount > 0) {
                throw new Exception("Cannot delete the employee type '{$typeName}' because it is assigned to one or more employees.");
            }

            // Soft delete employee type
            $data = ['is_deleted' => 1];
            return $this->repo->delete($data, $id);

        } catch (Exception $e) {
            Log::error("EmployeeTypeService delete error: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateStatus($req)
    {
        try {
            $id = base64_decode($req->id);
            $data = ['is_active' => $req->is_active];

            return $this->repo->updateStatus($data, $id);
        } catch (Exception $e) {
            Log::error("EmployeeTypeService updateStatus error: " . $e->getMessage());
            return false;
        }
    }
}

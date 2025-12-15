<?php
namespace App\Http\Services\Superadm;

use Illuminate\Http\Request;
use App\Http\Repository\Superadm\RoleRepository;
use Exception;
use Log;

class RoleService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new RoleRepository();
    }

    public function list()
    {
        try {
            return $this->repo->list();
        } catch (Exception $e) {
            Log::error("RoleService list error: " . $e->getMessage());
            return false;
        }
    }

    public function save($req)
    {
        try {
               $data = [
                'role' => $req->input('role'),
                'short_description' => $req->input('short_description'),
            ];
            return $this->repo->save($data);
        } catch (Exception $e) {
            Log::error("RoleService save error: " . $e->getMessage());
            return false;
        }
    }

    public function edit($id)
    {
        try {
            return $this->repo->edit($id);
        } catch (Exception $e) {
            Log::error("RoleService edit error: " . $e->getMessage());
            return false;
        }
    }

    public function update($req)
    {
        try {
            $id = $req->id;
            $data = [
                'role' => $req->input('role'),
                'short_description' => $req->input('short_description'),
                'is_active' => $req->is_active
            ];

            return $this->repo->update($data, $id);
        } catch (Exception $e) {
            Log::error("RoleService update error: " . $e->getMessage());
            return false;
        }
    }

    // public function delete($req)
    // {
    //     try {
    //         $id = base64_decode($req->id);
    //         $data = ['is_deleted' => 1];

    //         return $this->repo->delete($data, $id);
    //     } catch (Exception $e) {
    //         Log::error("RoleService delete error: " . $e->getMessage());
    //         return false;
    //     }
    // }

    public function delete($req)
    {
        try {
            $id = base64_decode($req->id);

            // Get role name
            $role = $this->repo->edit($id); // assuming edit() returns role details
            $roleName = $role->role ?? 'This role';

            // Check if any employee has this role assigned
            $employeeCount = \DB::table('employees')
                ->where('role_id', $id)
                ->where('is_deleted', 0)
                ->count();

            if ($employeeCount > 0) {
                // Role is assigned to employees, cannot delete
                throw new Exception("Cannot Delete The Role '{$roleName}' Because It Is Assigned To One Or More Employees.");
            }

            // Soft delete role
            $data = ['is_deleted' => 1];
            return $this->repo->delete($data, $id);

        } catch (Exception $e) {
            \Log::error("RoleService delete error: " . $e->getMessage());
            throw $e; // re-throw so controller can catch and show message
        }
    }


    public function updateStatus($req)
    {
        try {
            $id = base64_decode($req->id);
            $data = ['is_active' => $req->is_active];

            return $this->repo->updateStatus($data, $id);
        } catch (Exception $e) {
            Log::error("RoleService updateStatus error: " . $e->getMessage());
            return false;
        }
    }

//     public function updateStatus($req)
// {
//     try {
//         $id = base64_decode($req->id);
//         $isActive = $req->is_active; // 1 = activate, 0 = deactivate

//         // If trying to deactivate the role
//         if ($isActive == 0) {
//             // Fetch role name
//             $role = $this->repo->edit($id);
//             $roleName = $role->role ?? 'This role';

//             // Check if employees are using this role
//             $employeeCount = \DB::table('employees')
//                 ->where('role_id', $id)
//                 ->where('is_deleted', 0)
//                 ->count();

//             if ($employeeCount > 0) {
//                 throw new Exception("Cannot deactivate the role '{$roleName}' because it is assigned to one or more employees.");
//             }
//         }

//         // If allowed, update status
//         $data = ['is_active' => $isActive];
//         return $this->repo->updateStatus($data, $id);

//     } catch (Exception $e) {
//         \Log::error("RoleService updateStatus error: " . $e->getMessage());
//         throw $e; // Let the controller handle the message
//     }
// }


    public function find($id)
{
    try {
        return $this->repo->edit($id); // reuse existing edit() to fetch role
    } catch (Exception $e) {
        \Log::error("RoleService find error: " . $e->getMessage());
        return null;
    }
}


}

<?php
namespace App\Http\Services\Superadm;

use Illuminate\Http\Request;
use App\Http\Repository\Superadm\DesignationsRepository;
use Exception;
use Log;

class DesignationsService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new DesignationsRepository();
    }

    public function list()
    {
        try {
            return $this->repo->list();
        } catch (Exception $e) {
            Log::error("Designation Service list error: " . $e->getMessage());
            return false;
        }
    }

    public function save($req)
    {
        try {
            $data = ['designation' => $req->input('designation'), 'short_description' => $req->input('short_description')];
            return $this->repo->save($data);
        } catch (Exception $e) {
            Log::error("Designation Service save error: " . $e->getMessage());
            return false;
        }
    }

    public function edit($id)
    {
        try {
            return $this->repo->edit($id);
        } catch (Exception $e) {
            Log::error("Designation Service edit error: " . $e->getMessage());
            return false;
        }
    }

    public function update($req)
    {

        try {
            $id = $req->id;
            $data = [
                'designation' => $req->input('designation'),
                'short_description' => $req->input('short_description'),
                'is_active' => $req->is_active
            ];

            return $this->repo->update($data, $id);
        } catch (Exception $e) {
            Log::error("Designation Service update error: " . $e->getMessage());
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
    //         Log::error("Designation Service delete error: " . $e->getMessage());
    //         return false;
    //     }
    // }

    public function delete($req)
{
    try {
        $id = base64_decode($req->id);

        // Get designation name
        $designation = $this->repo->edit($id); // assuming edit() returns designation details
        $designationName = $designation->designation ?? 'This designation';

        // Check if any employee has this designation assigned
        $employeeCount = \DB::table('employees')
            ->where('designation_id', $id)
            ->where('is_deleted', 0)
            ->count();

        if ($employeeCount > 0) {
            // Designation is assigned to employees, cannot delete
            throw new Exception("Cannot delete the designation '{$designationName}' because it is assigned to one or more employees.");
        }

        // Soft delete designation
        $data = ['is_deleted' => 1];
        return $this->repo->delete($data, $id);

    } catch (Exception $e) {
        \Log::error("DesignationsService delete error: " . $e->getMessage());
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
            Log::error("Designation Service updateStatus error: " . $e->getMessage());
            return false;
        }
    }

//     public function updateStatus($req)
// {
//     try {
//         $id = base64_decode($req->id);
//         $isActive = $req->is_active; // 1 = activate, 0 = deactivate

//         // If trying to deactivate the designation
//         if ($isActive == 0) {
//             // Fetch designation name
//             $designation = $this->repo->edit($id);
//             $designationName = $designation->designation ?? 'This designation';

//             // Check if employees are using this designation
//             $employeeCount = \DB::table('employees')
//                 ->where('designation_id', $id)
//                 ->where('is_deleted', 0)
//                 ->count();

//             if ($employeeCount > 0) {
//                 throw new Exception("Cannot deactivate the designation '{$designationName}' because it is assigned to one or more employees.");
//             }
//         }

//         // If allowed, update status
//         $data = ['is_active' => $isActive];
//         return $this->repo->updateStatus($data, $id);

//     } catch (Exception $e) {
//         \Log::error("DesignationsService updateStatus error: " . $e->getMessage());
//         throw $e; // Let controller catch and return the message
//     }
// }


    public function find($id)
    {
        try {
            return $this->repo->edit($id); // reuse existing edit() method
        } catch (Exception $e) {
            \Log::error("DesignationsService find error: " . $e->getMessage());
            return null;
        }
    }


}

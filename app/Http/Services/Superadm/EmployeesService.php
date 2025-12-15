<?php
namespace App\Http\Services\Superadm;

use Illuminate\Http\Request;
use App\Http\Repository\Superadm\EmployeesRepository;
use App\Models\Employees;
use Exception;
use Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeesService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new EmployeesRepository();
    }

public function list($search = null)
{
    try {
        $query = Employees::where('is_deleted', 0)
            ->where('id', '!=', 1)  // ID 1 do not show
            ->with(['plant', 'department', 'designation', 'role'])
            ->orderBy('id', 'desc');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%")
                  ->orWhere('employee_email', 'like', "%{$search}%")
                  ->orWhere('employee_user_name', 'like', "%{$search}%");
            });
        }

        return $query->paginate(10);

    } catch (Exception $e) {
        Log::error("Employees Service list error: " . $e->getMessage());

        // Return an empty paginator to avoid errors in the view
        return new LengthAwarePaginator([], 0, 10, 1, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }
}


    public function save($req)
    {
        try {
            $plainPassword = $req->input('employee_password');
            $data = [
                // 'plant_id' => $req->input('plant_id'),
                // 'department_id' => implode(",", $req->input('department_id')),
                // 'projects_id' => implode(",", $req->input('projects_id')),
                'designation_id' => $req->input('designation_id'),
                'role_id' => $req->input('role_id'),
                'employee_code' => $req->input('employee_code'),
                'employee_name' => $req->input('employee_name'),
                'employee_type' => $req->input('employee_type'),
                'employee_email' => $req->input('employee_email'),
                'employee_user_name' => $req->input('employee_user_name'),
                // 'employee_password' => Hash::make($req->input('employee_password')),
                'employee_password'   => Hash::make($plainPassword),
                'plain_password'      => encrypt($plainPassword), // store encrypted plain password
                'reporting_to' => $req->input('reporting_to'),

                
            ];
            // dd($data);
            // die();
            return $this->repo->save($data);
        } catch (Exception $e) {
            Log::error("Employees Service save error: " . $e->getMessage());
            return false;
        }
    }

    public function edit($id)
    {
        try {
            return $this->repo->edit($id);
        } catch (Exception $e) {
            Log::error("Employees Service edit error: " . $e->getMessage());
            return false;
        }
    }

   

    public function update($req, $id)
    {

        // dd($req);
        try {
            $data = [
                // 'plant_id' => $req->input('plant_id'),
                // 'department_id' => implode(",", $req->input('department_id')),
                // 'projects_id' => implode(",", $req->input('projects_id')),
                'designation_id' => $req->input('designation_id'),
                'role_id' => $req->input('role_id'),
                'employee_code' => $req->input('employee_code'),
                'employee_name' => $req->input('employee_name'),
                'employee_type' => $req->input('employee_type'),
                'employee_email' => $req->input('employee_email'),
                'employee_user_name' => $req->input('employee_user_name'),
                'reporting_to' => $req->input('reporting_to'),
            ];

            if ($req->filled('employee_password')) {
                // $data['employee_password'] = Hash::make($req->input('employee_password'));
                $plainPassword = $req->input('employee_password');
                $data['employee_password'] = Hash::make($plainPassword);
                $data['plain_password']    = encrypt($plainPassword); // store encrypted plain password
            }

            return $this->repo->update($id, $data);

        } catch (Exception $e) {
            Log::error("Employees Service update error: " . $e->getMessage());
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
    //         Log::error("Employees Service delete error: " . $e->getMessage());
    //         return false;
    //     }
    // }

    // public function updateStatus($req)
    // {
    //     try {
    //         $id = base64_decode($req->id);
    //         $data = ['is_active' => $req->is_active];

    //         return $this->repo->updateStatus($data, $id);
    //     } catch (Exception $e) {
    //         Log::error("Employees Service updateStatus error: " . $e->getMessage());
    //         return false;
    //     }
    // }
// EmployeesService.php
public function updateStatus($req)
{
    try {
        $id = base64_decode($req->id);
        $data = ['is_active' => $req->is_active];

        return $this->repo->updateStatus($id, $data);
    } catch (\Exception $e) {
        \Log::error("Service updateStatus error: " . $e->getMessage());
        return false;
    }
}

public function delete($req)
{
    try {
        $id = base64_decode($req->id);
        $data = ['is_deleted' => 1]; // soft delete

        return $this->repo->delete($id, $data);
    } catch (\Exception $e) {
        \Log::error("Service delete error: " . $e->getMessage());
        return false;
    }
}

    public function listajaxlist($req)
	{  
		 try {
            return $this->repo->listajaxlist($req['plant_id']);
        } catch (Exception $e) {
            Log::error("Employees Service list error: " . $e->getMessage());
            return false;
        }
	}

    

}

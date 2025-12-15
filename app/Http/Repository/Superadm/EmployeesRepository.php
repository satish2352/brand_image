<?php

namespace App\Http\Repository\Superadm;

use Illuminate\Http\Request;
use App\Models\Employees;
use DB;
use Exception;
use Illuminate\Support\Facades\Log;

class EmployeesRepository
{
  public function list($search = null)
    {
        try {
             $perPage = config('fileConstants.PAGINATION');

            $query = Employees::leftJoin('plant_masters', 'employees.plant_id', '=', 'plant_masters.id')
                ->leftJoin('designations', 'employees.designation_id', '=', 'designations.id')
                ->leftJoin('roles', 'employees.role_id', '=', 'roles.id')
                ->leftJoin('employees as reporting', 'employees.reporting_to', '=', 'reporting.id')
                ->leftJoin('departments', function ($join) {
                    $join->whereRaw("FIND_IN_SET(departments.id, employees.department_id)");
                })
                ->leftJoin('projects', function ($join) {
                    $join->whereRaw("FIND_IN_SET(projects.id, employees.projects_id)");
                })
                ->where('employees.is_deleted', 0)
                ->select(
                    'employees.id',
                    'employees.employee_name',
                    'employees.employee_type',
                    'employees.employee_email',
                    'employees.employee_user_name',
                    'employees.employee_code',
                    'reporting.employee_name as reporting_name',
                    'plant_masters.plant_name',
                    'designations.designation',
                    'roles.role',
                    'employees.is_active',
                    DB::raw("GROUP_CONCAT(DISTINCT departments.department_name ORDER BY departments.department_name SEPARATOR ', ') as department_names"),
                    DB::raw("GROUP_CONCAT(DISTINCT projects.project_name ORDER BY projects.project_name SEPARATOR ', ') as project_names")
                )
                ->groupBy(
                    'employees.id',
                    'employees.employee_name',
                    'employees.employee_type',
                    'employees.employee_email',
                    'employees.employee_user_name',
                    'employees.employee_code',
                    'reporting.employee_name',
                    'plant_masters.plant_name',
                    'designations.designation',
                    'roles.role',
                    'employees.is_active'
                )
                ->orderBy('employees.id', 'desc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('employees.employee_name', 'like', "%$search%")
                        ->orWhere('employees.employee_code', 'like', "%$search%")
                        ->orWhere('employees.employee_email', 'like', "%$search%")
                        ->orWhere('employees.employee_user_name', 'like', "%$search%");
                });
            }

            return $query->paginate('10');
        } catch (Exception $e) {
            \Log::error("Employees Repository list error: ".$e->getMessage());
            return collect();
        }
    }

    public function save($data)
    {
        try {
            return Employees::create($data);
        } catch (Exception $e) {
            Log::error("Error saving project: " . $e->getMessage());
            return false;
        }
    }

    public function edit($id)
    {
        try {
            return Employees::where('id', $id)->first();
        } catch (Exception $e) {
            Log::error("Error editing project ID {$id}: " . $e->getMessage());
            return null;
        }
    }
    public function update($id, $data)
    {
        try {
            $employee = Employees::findOrFail($id);
            return $employee->update($data);
        } catch (Exception $e) {
            Log::error("Error updating employee: " . $e->getMessage());
            return false;
        }
    }


    // public function delete($data, $id)
    // {
    //     try {
    //         return Employees::where('id', $id)->update($data);
    //     } catch (Exception $e) {
    //         Log::error("Error deleting project ID {$id}: " . $e->getMessage());
    //         return false;
    //     }
    // }

    // public function updateStatus($data, $id)
    // {
    //     try {
            
    //         return Employees::where('id', $id)->update($data);

    //     } catch (Exception $e) {
    //         Log::error("Error updating status for project ID {$id}: " . $e->getMessage());
    //         return false;
    //     }
    // }

// public function updateStatus(Request $request)
// {
//     try {
//         $id = base64_decode($request->id);
//         Employees::where('id', $id)->update(['is_active' => $request->is_active]);
//         return response()->json(['status' => true, 'message' => 'Status updated successfully']);
//     } catch (\Exception $e) {
//         \Log::error($e->getMessage());
//         return response()->json(['status' => false, 'message' => 'Failed to update status'], 500);
//     }
// }

// public function delete(Request $request)
// {
//     try {
//         $id = base64_decode($request->id);
//         Employees::where('id', $id)->delete();
//         return response()->json(['status' => true, 'message' => 'Employee deleted successfully']);
//     } catch (\Exception $e) {
//         \Log::error($e->getMessage());
//         return response()->json(['status' => false, 'message' => 'Failed to delete employee'], 500);
//     }
// }
public function updateStatus($id, $data)
{
    try {
        return Employees::where('id', $id)->update($data);
    } catch (\Exception $e) {
        \Log::error("Repository updateStatus error: " . $e->getMessage());
        return false;
    }
}

public function delete($id, $data = [])
{
    try {
        // Soft delete: use $data = ['is_deleted' => 1] if you want
        return Employees::where('id', $id)->update($data); 
        // or ->delete() if you want hard delete
    } catch (\Exception $e) {
        \Log::error("Repository delete error: " . $e->getMessage());
        return false;
    }
}
    public function listajaxlist($palnt_id)
    {
        try {
            return Employees::where('is_deleted', 0)
                ->where('plant_id', $palnt_id)
				->where('is_active', 1)
                ->select('id','employee_name')
				->orderBy('id', 'desc')
				->get();

        } catch (Exception $e) {
            Log::error("Error fetching project list: " . $e->getMessage());
            return collect(); // return empty collection on error
        }
    }

}

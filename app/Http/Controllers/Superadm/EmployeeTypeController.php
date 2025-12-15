<?php
namespace App\Http\Controllers\Superadm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\EmployeeTypeService;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Validation\Rule;
use Exception;

class EmployeeTypeController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new EmployeeTypeService();
    }

    public function index()
    {
        try {
            $employeeTypes = $this->service->list();
            return view('superadm.employee_type.list', compact('employeeTypes'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function create(Request $req)
    {
        try {
            return view('superadm.employee_type.create');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function save(Request $req)
    {
        $req->validate([
            'type_name' => [
                'required',
                'max:255',
                'regex:/^[a-zA-Z\s-]+$/', // Only letters, spaces, and hyphens,
                Rule::unique('employee_types', 'type_name')->where(fn($query) => $query->where('is_deleted', 0)),
            ],
            'description' => 'required|max:255',
        ], [
            'type_name.required' => 'Enter Employee Type Name',
            'type_name.regex' => 'Employee Type must contain only letters, spaces, and hyphens.',
            'type_name.unique' => 'This Employee Type already exists.',
            'type_name.max' => 'Employee Type name must not exceed 255 characters.',
            'description.required' => 'Enter Description',
            'description.max' => 'Description must not exceed 255 characters.',
        ]);

        try {
            $this->service->save($req);
            return redirect()->route('employee-types.list')->with('success', 'Employee Type added successfully.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit($encodedId)
    {
        try {
            $id = base64_decode($encodedId);
            $data = $this->service->edit($id);
            return view('superadm.employee_type.edit', compact('data', 'encodedId'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function update(Request $req)
    {
        $req->validate([
            'type_name' => [
                'required',
                'max:255',
                'regex:/^[a-zA-Z\s-]+$/', // Only letters, spaces, and hyphens
                Rule::unique('employee_types', 'type_name')
                    ->where(fn($query) => $query->where('is_deleted', 0))
                    ->ignore($req->id),
            ],
            'description' => 'required|max:255',
            'id' => 'required',
            'is_active' => 'required'
        ], [
            'type_name.required' => 'Enter Employee Type Name',
            'type_name.regex' => 'Employee Type must contain only letters, spaces, and hyphens.',
            'type_name.unique' => 'This Employee Type already exists.',
            'type_name.max' => 'Employee Type name must not exceed 255 characters.',
            'id.required' => 'ID required',
            'is_active.required' => 'Select active or inactive',
            'description.required' => 'Enter Description',
            'description.max' => 'Description must not exceed 255 characters.',
        ]);

        try {
            $this->service->update($req);
            return redirect()->route('employee-types.list')->with('success', 'Employee Type updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function delete(Request $req)
    {
        try {
            $req->validate(['id' => 'required'], ['id.required' => 'ID required']);
            $this->service->delete($req);
            return redirect()->route('employee-types.list')->with('success', 'Employee Type deleted successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // public function updateStatus(Request $req)
    // {
    //     try {
    //         $this->service->updateStatus($req);
    //         return redirect()->route('employee-types.list')->with('success', 'Employee Type status updated successfully.');
    //     } catch (Exception $e) {
    //         return redirect()->back()->with('error', 'Failed to update status: ' . $e->getMessage());
    //     }
    // }

    public function updateStatus(Request $req)
    {
        try {
            $id = base64_decode($req->id);
            $type = $this->service->edit($id);

            if (!$type) {
                return response()->json(['status' => false, 'message' => 'Employee type not found'], 404);
            }

            $is_active = $req->is_active ? 1 : 0;
            $this->service->updateStatus($req);

            $statusText = $is_active ? 'activated' : 'deactivated';

            return response()->json([
                'status' => true,
                'message' => "Employee type '{$type->type_name}' status {$statusText} successfully"
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

//     public function updateStatus(Request $req)
// {
//     try {
//         $id = base64_decode($req->id);
//         $type = $this->service->edit($id);

//         if (!$type) {
//             return response()->json(['status' => false, 'message' => 'Employee type not found'], 404);
//         }

//         $is_active = $req->is_active ? 1 : 0;

//         // ✅ If trying to deactivate, check for employees
//         if ($is_active == 0) {
//             $employeeExists = \DB::table('employees')
//                 ->where('employee_type', $id)
//                 ->where('is_deleted', 0)
//                 ->exists();

//             if ($employeeExists) {
//                 return response()->json([
//                     'status' => false,
//                     'message' => "Cannot deactivate the employee type '{$type->type_name}' because employees are assigned to it."
//                 ], 400);
//             }
//         }

//         // ✅ Update status
//         $this->service->updateStatus($req);

//         $statusText = $is_active ? 'activated' : 'deactivated';
//         return response()->json([
//             'status' => true,
//             'message' => "Employee type '{$type->type_name}' status {$statusText} successfully"
//         ]);

//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => false,
//             'message' => 'Failed to update status: ' . $e->getMessage()
//         ], 500);
//     }
// }


}

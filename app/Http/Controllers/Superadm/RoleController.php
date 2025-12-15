<?php
namespace App\Http\Controllers\Superadm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\RoleService;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Validation\Rule;
use Exception;

class RoleController extends Controller
{
	protected $service;
	function __construct()
	{
		$this->service = new RoleService();
	}

	public function index()
	{
		try {
			$roles = $this->service->list();
			return view('superadm.role.list', compact('roles'));
		} catch (Exception $e) {
			return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
		}
	}

	public function create(Request $req)
	{
		try {
			return view('superadm.role.create');
		} catch (Exception $e) {
			return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
		}
	}

	public function save(Request $req)
	{

		$req->validate([
			'role' => [
				'required',
				'max:255',
				// 'regex:/^[a-zA-Z0-9\s]+$/',
				Rule::unique('roles', 'role')->where(function ($query) {
					return $query->where('is_deleted', 0);

				}),
			],
			 'short_description' => 'required|max:255',
		], [
			'role.required' => 'Enter Role Name',
			// 'role.regex' => 'Role Must Contain Only Letters, Numbers, And Spaces.',
			'role.unique' => 'This Role Already Exists.',
			'role.max' => 'Role Name Must Not Exceed 255 Characters.',
			'short_description.required' => 'Enter Description',
			 'short_description.max' => 'Description Must Not Exceed 255 Characters.',
		]);

		try {
			$this->service->save($req);
			return redirect()->route('roles.list')->with('success', 'Role Added Successfully.');
		} catch (Exception $e) {
			return redirect()->back()->withInput()->with('error', 'Something went wrong: ' . $e->getMessage());
		}

	}

	public function edit($encodedId)
	{
		try {
			$id = base64_decode($encodedId);
			$data = $this->service->edit($id);
			return view('superadm.role.edit', compact('data', 'encodedId'));
		} catch (Exception $e) {
			return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
		}
	}

	public function update(Request $req)
	{
		$req->validate([
			'role' => [
				'required',
				'max:255',
				Rule::unique('roles', 'role')
					->where(fn($query) => $query->where('is_deleted', 0))
					->ignore($req->id),
			],
			 'short_description' => 'required|max:255',
			'id' => 'required',
			'is_active' => 'required'
		], [
			'role.required' => 'Enter Role Name',
			'role.unique' => 'This Role Already Exists.',
			'role.max' => 'Role Name Must Not Exceed 255 Characters.',
			'id.required' => 'ID Required',
			'is_active.required' => 'Select Active Or Inactive Required',
			'short_description.required' => 'Enter Description',
			'short_description.max' => 'Description Must Not Exceed 255 Characters.',
		]);

		try {
			$this->service->update($req);
			return redirect()->route('roles.list')->with('success', 'Role Updated Successfully.');
		} catch (Exception $e) {
			return redirect()->back()->withInput()->with('error', 'Something went wrong: ' . $e->getMessage());
		}
	}


	// public function delete(Request $req)
	// {
	// 	try {
	// 		$req->validate([
	// 			'id' => 'required',
	// 		], [
	// 			'id.required' => 'ID required'
	// 		]);

	// 		$this->service->delete($req);
	// 		return redirect()->route('roles.list')->with('success', 'Role deleted successfully.');
	// 	} catch (Exception $e) {
	// 		return redirect()->back()->with('error', 'Failed to delete role: ' . $e->getMessage());
	// 	}
	// }

	public function delete(Request $req)
	{
		try {
			$req->validate([
				'id' => 'required',
			], [
				'id.required' => 'ID required'
			]);

			$this->service->delete($req);
			return redirect()->route('roles.list')->with('success', 'Role Deleted Successfully.');
		} catch (Exception $e) {
			// Show the custom message if role is assigned to employees
			return redirect()->back()->with('error', $e->getMessage());
		}
	}

	public function validateData(Request $req)
	{

	}

	// public function updateStatus(Request $req)
	// {
	// 	try {
	// 		$this->service->updateStatus($req);
	// 		return redirect()->route('roles.list')->with('success', 'Role status updated successfully.');
	// 	} catch (Exception $e) {
	// 		return redirect()->back()->with('error', 'Failed to update status: ' . $e->getMessage());
	// 	}
	// }

	public function updateStatus(Request $request)
{
    try {
        $id = base64_decode($request->id);
        $role = $this->service->find($id); // Add find() method in RoleService

        if (!$role) {
            return response()->json(['status' => false, 'message' => 'Role not found'], 404);
        }

        $role->is_active = $request->is_active;
        $role->save();

        $statusText = $role->is_active ? 'Activated ' : 'Deactivated ';
        $message = "Role '{$role->role}' Status {$statusText} Successfully";

        return response()->json(['status' => true, 'message' => $message]);
    } catch (Exception $e) {
        return response()->json(['status' => false, 'message' => 'Failed to update status: ' . $e->getMessage()], 500);
    }
}

// public function updateStatus(Request $request)
// {
//     try {
//         $id = base64_decode($request->id);
//         $role = $this->service->find($id);

//         if (!$role) {
//             return response()->json(['status' => false, 'message' => 'Role not found'], 404);
//         }

//         $request->is_active = $request->is_active; // ensure the same value is passed
//         $this->service->updateStatus($request);

//         $statusText = $request->is_active ? 'activated' : 'deactivated';
//         $message = "Role '{$role->role}' status {$statusText} successfully";

//         return response()->json(['status' => true, 'message' => $message]);
//     } catch (Exception $e) {
//         return response()->json(['status' => false, 'message' => $e->getMessage()], 400);
//     }
// }


}

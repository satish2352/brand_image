<?php
namespace App\Http\Controllers\Superadm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\DesignationsService;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Validation\Rule;
use Exception;

class DesignationsController extends Controller
{
	protected $service;
	function __construct()
	{
		$this->service = new DesignationsService();
	}

	public function index()
	{
		try {
			$designation = $this->service->list();
			return view('superadm.designation.list', compact('designation'));
		} catch (Exception $e) {
			return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
		}
	}

	public function create(Request $req)
	{
		try {
			return view('superadm.designation.create');
		} catch (Exception $e) {
			return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
		}
	}

	public function save(Request $req)
	{

		$req->validate([
			'designation' => [
				'required',
				'max:255',
				Rule::unique('designations', 'designation')->where(function ($query) {
					return $query->where('is_deleted', 0);
				}),
			],
			'short_description' => 'required|max:255',

			
		], [
			'designation.required' => 'Enter Designation Name',
			'designation.unique' => 'This Designation Already Exists.',
			'short_description.required' => 'This Short Description Required.',
			'short_description.max' => 'Description Must Not Exceed 255 Characters.',
		]);

		try {
			$this->service->save($req);
			return redirect()->route('designations.list')->with('success', 'Designation Added Successfully.');
		} catch (Exception $e) {
			return redirect()->back()->withInput()->with('error', 'Something went wrong: ' . $e->getMessage());
		}

	}

	public function edit($encodedId)
	{
		try {
			$id = base64_decode($encodedId);
			$data = $this->service->edit($id);
			return view('superadm.designation.edit', compact('data', 'encodedId'));
		} catch (Exception $e) {
			return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
		}
	}

	public function update(Request $req)
	{
		$req->validate([
			'designation' => [
				'required',
				'max:255',
				Rule::unique('designations', 'designation')
					->where(fn($query) => $query->where('is_deleted', 0))
					->ignore($req->id),
			],
			'short_description' => 'required|max:255',
			'id' => 'required',
			'is_active' => 'required'
		], [
			'designation.required' => 'Enter Designation Name',
			'designation.unique' => 'This designation already Exists.',
			'short_description.required' => 'This Short Description Required.',
			'id.required' => 'ID Required',
			'is_active.required' => 'Select Active Or Inactive Required',
			 'short_description.max' => 'Description Must Not Exceed 255 Characters.',
		]);

		try {
			$this->service->update($req);
			return redirect()->route('designations.list')->with('success', 'Designation Updated Successfully.');
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
	// 		return redirect()->route('designations.list')->with('success', 'Designation deleted successfully.');
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
        return redirect()->route('designations.list')->with('success', 'Designation Deleted Successfully.');
    } catch (Exception $e) {
        // Show the custom message if designation is assigned to employees
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
	// 		return redirect()->route('designations.list')->with('success', 'Designation status updated successfully.');
	// 	} catch (Exception $e) {
	// 		return redirect()->back()->with('error', 'Failed to update status: ' . $e->getMessage());
	// 	}
	// }

	public function updateStatus(Request $request)
{
    try {
        $id = base64_decode($request->id);
        $designation = $this->service->find($id); // add find() method in service

        if (!$designation) {
            return response()->json(['status' => false, 'message' => 'Designation not found'], 404);
        }

        $designation->is_active = $request->is_active;
        $designation->save();

        $statusText = $designation->is_active ? 'Activated' : 'Deactivated';
        $message = "Designation '{$designation->designation}' Status {$statusText} Successfully";

        return response()->json(['status' => true, 'message' => $message]);
    } catch (Exception $e) {
        return response()->json(['status' => false, 'message' => 'Failed to update status: ' . $e->getMessage()], 500);
    }
}

// public function updateStatus(Request $request)
// {
//     try {
//         $id = base64_decode($request->id);
//         $designation = $this->service->find($id);

//         if (!$designation) {
//             return response()->json(['status' => false, 'message' => 'Designation not found'], 404);
//         }

//         // Delegate logic to service (which now includes the restriction)
//         $this->service->updateStatus($request);

//         $statusText = $request->is_active ? 'activated' : 'deactivated';
//         $message = "Designation '{$designation->designation}' status {$statusText} successfully";

//         return response()->json(['status' => true, 'message' => $message]);

//     } catch (Exception $e) {
//         return response()->json([
//             'status' => false,
//             'message' => $e->getMessage()
//         ], 400);
//     }
// }


}

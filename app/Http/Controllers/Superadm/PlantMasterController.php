<?php
namespace App\Http\Controllers\Superadm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\PlantMasterService;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Validation\Rule;
use Exception;
use App\Exports\PlantsExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class PlantMasterController extends Controller
{
	protected $service;
	function __construct()
	{
		$this->service = new PlantMasterService();
	}

	public function index()
	{
		try {
			$data_all = $this->service->list();
			return view('superadm.plantmaster.list', compact('data_all'));
		} catch (Exception $e) {
			return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
		}
	}

	public function create(Request $req)
	{
		try {
			return view('superadm.plantmaster.create');
		} catch (Exception $e) {
			return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
		}
	}

	public function save(Request $req)
	{

	$req->validate([
    'plant_code' => [
        'required',
        'max:255',
        Rule::unique('plant_masters', 'plant_code')->where(function ($query) {
            return $query->where('is_deleted', 0);
        }),
    ],
		'plant_name' => [
				'required',
				'max:255',
				'max:255',
				// 'regex:/^[a-zA-Z0-9\s]+$/',
				// 'regex:/^[a-zA-Z0-9\s\-\_\&\.]+$/',
				'regex:/^.+$/',
				Rule::unique('plant_masters', 'plant_name')->where(function ($query) {
					return $query->where('is_deleted', 0);
				}),
			],
    // 'address' => 'required',
    'city' => [
            'required',
            'regex:/^[a-zA-Z\s]+$/', 
        ],
    // 'plant_short_name' => 'nullable|max:255', 
], [
    'plant_code.required' => 'Enter plant code',
    'plant_code.unique' => 'This plant code already exists.',
    'plant_code.max' => 'Plant code must not exceed 255 characters.',
    'plant_name.required' => 'Enter plant name',
    'plant_name.max' => 'Plant name must not exceed 255 characters.',
     'plant_name.regex' => 'Plant Name can contain any characters.',
    // 'address.required' => 'Enter address for plant',
   'city.required' => 'Enter city for plant',
        'city.regex' => 'City name must contain only letters and spaces.',

    // 'plant_short_name.required' => 'Enter plant short name',
]);


		try {
			// $this->service->save($req);
			// return redirect()->route('plantmaster.list')->with('success', 'Plant details added successfully.');
		$createdBy = session('employee_user_name'); // or whatever session key
		$data = array_merge($req->all(), ['created_by' => $createdBy]);

		$result = $this->service->save($data);

		if (!$result) {
			return redirect()->back()->withInput()->with('error', 'Failed to insert plant details.');
		}

		return redirect()->route('plantmaster.list')->with('success', 'Plant details added successfully.');

		} catch (Exception $e) {
			return redirect()->back()->withInput()->with('error', 'Something went wrong: ' . $e->getMessage());
		}

	}

	public function edit($encodedId)
	{
		try {
			$id = base64_decode($encodedId);
			$data = $this->service->edit($id);
			return view('superadm.plantmaster.edit', compact('data', 'encodedId'));
		} catch (Exception $e) {
			return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
		}
	}

	public function update(Request $req)
	{
		$req->validate([
			'plant_code' => [
				'required',
				Rule::unique('plant_masters', 'plant_code')
					->where(fn($query) => $query->where('is_deleted', 0))
					->ignore($req->id),
			],
			'plant_name' => 'required',
			// 'address' => 'required',
			 'city' => [
            'required',
            'regex:/^[a-zA-Z\s]+$/', // only letters and spaces
        ],
			// 'plant_short_name' => 'required',
			'id' => 'required',
			'is_active' => 'required'

		], [
			'plant_code.required' => 'Enter plant code',
			'plant_code.unique' => 'This plant code already exists.',
			'id.required' => 'ID required',
			'plant_name.required' => 'Enter plant name',
			// 'address.required' => 'Enter address for plant',
			 'city.required' => 'Enter city for plant',
        'city.regex' => 'City name must contain only letters and spaces.',
			// 'plant_short_name.required' => 'Enter plant short name',
			'is_active.required' => 'Select active or inactive required'
		]);

		try {
			$this->service->update($req);
			return redirect()->route('plantmaster.list')->with('success', 'Plant details updated successfully.');
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
	// 		return redirect()->route('plantmaster.list')->with('success', 'Plant details deleted successfully.');
	// 	} catch (Exception $e) {
	// 		return redirect()->back()->with('error', 'Failed to delete plant: ' . $e->getMessage());
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

			$id = base64_decode($req->id);

			// ✅ Get the plant
			$plant = \DB::table('plant_masters')
				->where('id', $id)
				->where('is_deleted', 0)
				->first();

			if (!$plant) {
				return redirect()->route('plantmaster.list')
					->with('error', 'Plant not found or already deleted.');
			}

			// ✅ Check if employees exist for this plant
			$employeeExists = \DB::table('employee_plant_assignments')
				->where('plant_id', $id)
				->where('is_deleted', 0)
				->exists();

			if ($employeeExists) {
				return redirect()->route('plantmaster.list')
					->with('error', "Cannot delete the plant '{$plant->plant_name}' because employees are assigned to it.");
			}

			// ✅ Check if projects exist for this plant
			$projectExists = \DB::table('projects')
				->where('plant_id', $id)
				->where('is_deleted', 0)
				->exists();

			if ($projectExists) {
				return redirect()->route('plantmaster.list')
					->with('error', "Cannot delete the plant '{$plant->plant_name}' because projects are assigned to it.");
			}

			// ✅ Check if departments exist for this plant
			$departmentExists = \DB::table('departments')
				->where('plant_id', $id)
				->where('is_deleted', 0)
				->exists();

			if ($departmentExists) {
				return redirect()->route('plantmaster.list')
					->with('error', "Cannot delete the plant '{$plant->plant_name}' because departments are assigned to it.");
			}

			// ✅ If no dependencies, soft delete
			$this->service->delete($req);

			return redirect()->route('plantmaster.list')
				->with('success', "Plant '{$plant->plant_name}' deleted successfully.");

		} catch (Exception $e) {
			return redirect()->back()
				->with('error', 'Failed to delete plant: ' . $e->getMessage());
		}
	}

	public function validateData(Request $req)
	{

	}

	// public function updateStatus(Request $req)
	// {
	// 	try {
	// 		$this->service->updateStatus($req);
	// 		return redirect()->route('plantmaster.list')->with('success', 'Plant details status updated successfully.');
	// 	} catch (Exception $e) {
	// 		return redirect()->back()->with('error', 'Failed to update status: ' . $e->getMessage());
	// 	}
	// }

	public function updateStatus(Request $request)
{
    try {
        $id = base64_decode($request->id);
        $plant = \DB::table('plant_masters')->where('id', $id)->first();

        if (!$plant) {
            return response()->json(['status' => false, 'message' => 'Plant not found'], 404);
        }

        $is_active = $request->is_active ? 1 : 0;
        \DB::table('plant_masters')->where('id', $id)->update(['is_active' => $is_active]);

        $statusText = $is_active ? 'activated' : 'deactivated';
        $message = "Plant '{$plant->plant_name}' status {$statusText} successfully";

        return response()->json(['status' => true, 'message' => $message]);
    } catch (Exception $e) {
        return response()->json(['status' => false, 'message' => 'Failed to update status: ' . $e->getMessage()], 500);
    }
}

public function export(Request $request)
{
    $search = $request->search;
    $type = $request->type ?? 'excel'; // default to excel

    $query = \DB::table('plant_masters')->where('is_deleted', 0);

    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('plant_code', 'like', "%{$search}%")
              ->orWhere('plant_name', 'like', "%{$search}%")
              ->orWhere('address', 'like', "%{$search}%")
              ->orWhere('city', 'like', "%{$search}%")
              ->orWhere('plant_short_name', 'like', "%{$search}%");
        });
    }

    $plants = $query->get();

    if ($plants->isEmpty()) {
        return redirect()->back()->with('error', 'No data available to export.');
    }

    if ($type == 'excel') {
        $fileName = 'plants_' . date('Y_m_d') . '.xlsx';
        return Excel::download(new PlantsExport($search), $fileName);
    }

	if ($type == 'pdf') {
		$pdf = Pdf::loadView('superadm.plantmaster.pdf', compact('plants'))
				->setPaper('A4', 'landscape'); // <-- Landscape for wide tables
		return $pdf->download('plants_' . date('Y_m_d') . '.pdf');
	}

    return redirect()->back()->with('error', 'Invalid export type.');
}



// public function updateStatus(Request $request)
// {
//     try {
//         $id = base64_decode($request->id);

//         // Fetch the plant
//         $plant = \DB::table('plant_masters')
//             ->where('id', $id)
//             ->where('is_deleted', 0)
//             ->first();

//         if (!$plant) {
//             return response()->json(['status' => false, 'message' => 'Plant not found'], 404);
//         }

//         $is_active = $request->is_active ? 1 : 0;

//         // ✅ If trying to deactivate, check dependencies
//         if ($is_active == 0) {
//             $employeeExists = \DB::table('employees')
//                 ->where('plant_id', $id)
//                 ->where('is_deleted', 0)
//                 ->exists();

//             $projectExists = \DB::table('projects')
//                 ->where('plant_id', $id)
//                 ->where('is_deleted', 0)
//                 ->exists();

//             $departmentExists = \DB::table('departments')
//                 ->where('plant_id', $id)
//                 ->where('is_deleted', 0)
//                 ->exists();

//             if ($employeeExists || $projectExists || $departmentExists) {
//                 $message = "Cannot deactivate the plant '{$plant->plant_name}' because it has assigned ";
//                 $parts = [];
//                 if ($employeeExists) $parts[] = "employees";
//                 if ($projectExists) $parts[] = "projects";
//                 if ($departmentExists) $parts[] = "departments";
//                 $message .= implode(', ', $parts) . ".";
                
//                 return response()->json(['status' => false, 'message' => $message], 400);
//             }
//         }

//         // ✅ Update status
//         \DB::table('plant_masters')->where('id', $id)->update(['is_active' => $is_active]);

//         $statusText = $is_active ? 'activated' : 'deactivated';
//         $message = "Plant '{$plant->plant_name}' status {$statusText} successfully";

//         return response()->json(['status' => true, 'message' => $message]);

//     } catch (Exception $e) {
//         return response()->json(['status' => false, 'message' => 'Failed to update status: ' . $e->getMessage()], 500);
//     }
// }


}

<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Services\Superadm\AreaService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AreaController extends Controller
{

    protected $areaService;

    public function __construct()
    {
        $this->areaService = new AreaService();
    }

    /*
    |--------------------------------------------------------------------------
    | LOCATION TYPE MAP (as per your DB)
    |--------------------------------------------------------------------------
    | 0 = Country
    | 1 = State
    | 2 = District
    | 3 = Taluka
    | 4 = City / Village
    | 5 = Area
    |--------------------------------------------------------------------------
    */

    /* =========================
       LIST PAGE
    ========================== */
    public function index()
    {
        try {
            $areas = $this->areaService->getAllAreas();
            return view('superadm.area.list', compact('areas'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }
    /* =========================
       CREATE PAGE
    ========================== */
    public function create()
    {
        return view('superadm.area.create');
    }

    /* =========================
       STORE AREA
    ========================== */
    public function store(Request $request)
    {
        // ✅ Validation rules
        $rules = [
            'state_id'              => 'required|integer|exists:tbl_location,location_id',
            'district_id'           => 'required|integer|exists:tbl_location,location_id',
            'city_id'               => 'required|integer|exists:tbl_location,location_id',
            'area_name'             => 'required|string|max:255',
            'common_stdiciar_name'  => 'required|string|max:255',
        ];

        // ✅ Custom validation messages
        $messages = [
            'state_id.required'     => 'Please select a state.',
            'state_id.integer'      => 'Invalid state selected.',
            'state_id.exists'       => 'Selected state does not exist.',

            'district_id.required'  => 'Please select a district.',
            'district_id.integer'   => 'Invalid district selected.',
            'district_id.exists'    => 'Selected district does not exist.',

            'city_id.required'      => 'Please select a city.',
            'city_id.integer'       => 'Invalid city selected.',
            'city_id.exists'        => 'Selected city does not exist.',

            'area_name.required'    => 'Please enter the area name.',
            'area_name.string'      => 'Area name must be a valid text.',
            'area_name.max'         => 'Area name must not exceed 255 characters.',

            'common_stdiciar_name.required' => 'Please enter the common standard name.',
            'common_stdiciar_name.string'   => 'Common standard name must be valid text.',
            'common_stdiciar_name.max'      => 'Common standard name must not exceed 255 characters.',
        ];

        // ✅ Validate request
        $validated = $request->validate($rules, $messages);

        try {
            // ✅ Service call
            $this->areaService->storeArea($validated);

            return redirect()
                ->route('area.list')
                ->with('success', 'Area added successfully.');
        } catch (\Exception $e) {

            Log::error('Area Store Error: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to add area. Please try again.');
        }
    }


    /* =========================
   EDIT PAGE
========================== */
    public function edit($encodedId)
    {
        try {
            $id = base64_decode($encodedId);
            $area = $this->areaService->getAreaById($id);

            return view('superadm.area.edit', compact('area', 'encodedId'));
        } catch (Exception $e) {
            return redirect()->route('area.list')->with('error', 'Area not found');
        }
    }

    /* =========================
   UPDATE AREA
========================== */
    public function update(Request $request, $encodedId)
    {
        $id = base64_decode($encodedId);

        $validated = $request->validate([
            'state_id'             => 'required|integer|exists:tbl_location,location_id',
            'district_id'          => 'required|integer|exists:tbl_location,location_id',
            'city_id'              => 'required|integer|exists:tbl_location,location_id',
            'area_name'            => 'required|string|max:255',
            'common_stdiciar_name' => 'required|string|max:255',
        ]);

        try {
            $this->areaService->updateArea($id, $validated);

            return redirect()
                ->route('area.list')
                ->with('success', 'Area updated successfully.');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /* =========================
   UPDATE STATUS
========================== */
    public function updateStatus(Request $request)
    {
        try {
            $id = base64_decode($request->id);

            $this->areaService->toggleStatus($id);

            return response()->json([
                'status' => true,
                'message' => 'Status updated successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Status update failed'
            ], 500);
        }
    }

    /* =========================
   DELETE (SOFT)
========================== */
    public function delete(Request $request)
    {
        try {
            $id = base64_decode($request->id);

            $this->areaService->deleteArea($id);

            return response()->json([
                'status' => true,
                'message' => 'Area deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Delete failed'
            ], 500);
        }
    }

    /* =========================
       AJAX : STATE (ONLY MAHARASHTRA)
    ========================== */
    public function getStates()
    {
        return response()->json(
            DB::table('tbl_location')
                ->where('location_type', 1)     // STATE
                ->where('is_active', 1)
                ->where('name', 'Maharashtra')
                ->select('location_id', 'name')
                ->get()
        );
    }

    /* =========================
       AJAX : DISTRICT
    ========================== */
    public function getDistricts($stateId)
    {
        return response()->json(
            DB::table('tbl_location')
                ->where('location_type', 2)     // DISTRICT
                ->where('parent_id', $stateId) // Maharashtra ID
                ->where('is_active', 1)
                ->select('location_id', 'name')
                ->get()
        );
    }

    /* =========================
       AJAX : TALUKA
    ========================== */
    public function getCities($districtId)
    {
        return response()->json(
            DB::table('tbl_location')
                ->where('location_type', 3)     // TALUKA
                ->where('parent_id', $districtId)
                ->where('is_active', 1)
                ->select('location_id', 'name')
                ->get()
        );
    }

    /* =========================
       AJAX : VILLAGE / CITY
    ========================== */
    public function getAreas($cityId)
    {
        return response()->json(
            DB::table('tbl_location')
                ->where('location_type', 4)     // CITY / VILLAGE
                ->where('parent_id', $cityId)
                ->where('is_active', 1)
                ->select('location_id', 'name')
                ->get()
        );
    }
}

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

    public function index()
    {
        try {
            $areas = $this->areaService->getAllAreas();
            return view('superadm.area.list', compact('areas'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }
    public function create()
    {
        return view('superadm.area.create');
    }


    public function store(Request $request)
    {
        $rules = [
            'state_id'             => 'required|integer|exists:states,id',
            'district_id'          => 'required|integer|exists:districts,id',
            'city_id'              => 'required|integer|exists:cities,id',
            'area_name'             => 'required|string|max:255',
            'common_stdiciar_name'  => 'required|string|max:255',
            'latitude'             => 'required|numeric',
            'longitude'            => 'required|numeric',
        ];

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
            'latitude.required'  => 'Latitude is required.',
            'latitude.numeric'   => 'Latitude must be numeric.',
            'longitude.required' => 'Longitude is required.',
            'longitude.numeric'  => 'Longitude must be numeric.',
        ];


        $validated = $request->validate($rules, $messages);

        try {
            //  Service call
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
    public function update(Request $request, $encodedId)
    {
        $id = base64_decode($encodedId);

        $validated = $request->validate([
            'state_id'             => 'required|integer|exists:states,id',
            'district_id'          => 'required|integer|exists:districts,id',
            'city_id'              => 'required|integer|exists:cities,id',
            'area_name'            => 'required|string|max:255',
            'common_stdiciar_name' => 'required|string|max:255',
            'latitude'             => 'required|numeric',
            'longitude'            => 'required|numeric',
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
    public function getStates()
    {
        return response()->json(
            DB::table('states')
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->orderBy('state_name')
                ->select('id', 'state_name')
                ->get()
        );
    }
    public function getDistricts(Request $request)
    {
        return response()->json(
            DB::table('districts')
                ->where('state_id', $request->state_id)
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->orderBy('district_name')
                ->select('id', 'district_name')
                ->get()
        );
    }
    public function getCities(Request $request)
    {
        return response()->json(
            DB::table('cities')
                ->where('district_id', $request->district_id)
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->orderBy('city_name')
                ->select('id', 'city_name')
                ->get()
        );
    }
    public function getAreas(Request $request)
    {
        return response()->json(
            DB::table('areas')
                ->where('city_id', $request->city_id)
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->orderBy('area_name')
                ->select('id', 'area_name')
                ->get()
        );
    }
}

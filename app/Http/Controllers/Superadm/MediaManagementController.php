<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Services\Superadm\MediaManagementService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\{
    Category,
    FacingDirection,
    Illumination
};

class MediaManagementController extends Controller
{

    protected $mediaService;

    public function __construct(MediaManagementService $mediaService)
    {
        $this->mediaService = $mediaService;
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
            $mediaList = $this->mediaService->getAll();
            return view('superadm.mediamanagement.list', compact('mediaList'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }

    public function getAllAreas()
    {
        return response()->json(
            DB::table('areas')
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->select(
                    'id',
                    'state_id',
                    'district_id',
                    'city_id',
                    'common_stdiciar_name'
                )
                ->get()
        );
    }

    public function getAreaParents($areaId)
    {
        $city = DB::table('tbl_location')->where('location_id', $areaId)->first();
        $district = DB::table('tbl_location')->where('location_id', $city->parent_id)->first();
        $state = DB::table('tbl_location')->where('location_id', $district->parent_id)->first();

        return response()->json([
            'city_id' => $city->location_id,
            'district_id' => $district->location_id,
            'state_id' => $state->location_id,
        ]);
    }

    /* =========================
       CREATE PAGE
    ========================== */
    public function create()
    {
        $categories = Category::where('is_active', 1)
            ->where('is_deleted', 0)
            ->get();

        $facings = FacingDirection::where('is_active', 1)
            ->where('is_deleted', 0)
            ->get();

        $illuminations = Illumination::where('is_active', 1)
            ->where('is_deleted', 0)
            ->get();

        return view('superadm.mediamanagement.create', compact(
            'categories',
            'facings',
            'illuminations'
        ));
    }


    /* =========================
       STORE
    ========================== */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Location
            'state_id'    => 'required|integer',
            'district_id' => 'required|integer',
            'city_id'     => 'required|integer',
            'area_id'     => 'required|integer',

            // Media basic
            'category_id' => 'required|integer',
            'media_code' => 'required|string|max:255|unique:media_management,media_code,NULL,id,is_deleted,0',

            'media_title' => 'required|string|max:255',

            // Address
            'address' => 'required|string',

            // Dimensions
            'width'  => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',

            // Facing & illumination
            'facing_id'       => 'required|string',
            'illumination_id' => 'required|integer',

            // Geo
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',

            // Booking & price
            'minimum_booking_days' => 'required|integer|min:2',
            'price' => 'required|numeric|min:0',

            // Vendor
            'vendor_name' => 'required|string|max:255',

            // Images
            // 'images'   => 'required|array|min:1',
            // 'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        try {
            $this->mediaService->store($request);
            return redirect()
                ->route('media.list')
                ->with('success', 'Media added successfully');
        } catch (\Exception $e) {
            Log::error($e);
            return back()->withInput()->with('error', $e->getMessage());
        }
    }



    public function edit($encodedId)
    {
        try {
            $id = base64_decode($encodedId);

            $media = DB::table('media_management')->where('id', $id)->first();

            if (!$media) {
                return redirect()->route('media.list')->with('error', 'Media not found');
            }

            $categories = Category::where('is_active', 1)->get();
            $facings = FacingDirection::where('is_active', 1)->get();
            $illuminations = Illumination::where('is_active', 1)->get();
            $areas = DB::table('areas')
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->get();
            return view('superadm.mediamanagement.edit', compact(
                'media',
                'categories',
                'facings',
                'illuminations',
                'encodedId',
                'areas'
            ));
        } catch (\Exception $e) {
            return redirect()->route('media.list')->with('error', 'Invalid media ID');
        }
    }


    public function update(Request $request, $encodedId)
    {
        $id = base64_decode($encodedId);

        $validated = $request->validate([
            // Location


            // Media basic
            'media_code'  => 'required|string|max:255|unique:media_management,media_code,' . $id,
            'media_title' => 'required|string|max:255',

            // Address
            'address' => 'required|string',

            // Dimensions
            'width'  => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',

            // Facing & illumination
            'facing_id'       => 'required|integer',
            'illumination_id' => 'required|integer',

            // Geo
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',

            // Booking & price
            'minimum_booking_days' => 'required|integer|min:2',
            'price' => 'required|numeric|min:0',

            // Vendor
            'vendor_name' => 'required|string|max:255',

            // Images (optional in update)
            // 'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        try {
            $this->mediaService->update($id, $request);

            return redirect()
                ->route('media.list')
                ->with('success', 'Media updated successfully');
        } catch (\Exception $e) {
            Log::error($e);
            return back()->withInput()->with('error', 'Update failed');
        }
    }


    /* =========================
   UPDATE STATUS
========================== */
    public function updateStatus(Request $request)
    {
        try {
            $id = base64_decode($request->id);

            $this->mediaService->toggleStatus($id);

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

            $this->mediaService->delete($id);

            return response()->json([
                'status'  => true,
                'message' => 'Media deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
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

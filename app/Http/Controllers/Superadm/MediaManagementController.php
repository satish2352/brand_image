<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Services\Superadm\MediaManagementService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\{
    Category,
    FacingDirection,
    Illumination,
    MediaManagement,
    MediaImage,
    RadiusMaster
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
    public function view($encodedId)
    {
        try {
            $id = base64_decode($encodedId);

            $media = MediaManagement::with(['images' => function ($q) {
                $q->where('is_deleted', 0);
            }])->findOrFail($id);

            return view('superadm.mediamanagement.view', compact('media'));
        } catch (\Exception $e) {
            abort(404);
        }
    }
    public function viewDetails($encodedId)
    {
        $id = base64_decode($encodedId, true);

        if (!$id || !is_numeric($id)) {
            abort(404);
        }

        logger('Decoded Media ID:', [$id]); // 👈 ADD THIS

        $media = $this->mediaService->viewDetails($id);

        if (!$media) {
            abort(404);
        }

        return view('superadm.mediamanagement.viewDetails', compact('media'));
    }

    // public function viewDetails($encodedId)
    // {
    //     try {
    //         $id = base64_decode($encodedId);

    //         $media = $this->mediaService->viewDetails($id);

    //         if (!$media) {
    //             abort(404);
    //         }

    //         return view('superadm.mediamanagement.viewDetails', compact('media'));
    //     } catch (\Exception $e) {
    //         abort(404);
    //     }
    // }
    public function deleteImage(Request $request)
    {
        try {

            $request->validate([
                'image_id' => 'required|integer'
            ]);

            // 1️⃣ Get image record
            $image = MediaImage::where('id', $request->image_id)
                ->where('is_deleted', 0)
                ->firstOrFail();

            // 2️⃣ DELETE FILE FIRST (IMPORTANT)
            removeImage(
                $image->images,
                config('fileConstants.IMAGE_DELETE')
            );

            // 3️⃣ SOFT DELETE DB RECORD
            $image->update([
                'is_active'  => 0,
                'is_deleted' => 1,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Image deleted successfully'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Image delete failed'
            ], 500);
        }
    }
    public function uploadImage(Request $request)
    {
        $request->validate(
            [
                'media_id'   => 'required|integer',
                'images'     => 'required|array|max:10',
                'images.*'   => 'image|mimes:webp,jpg,jpeg,png|max:1024',
            ],
            [
                'media_id.required' => 'Media ID is required.',
                'media_id.exists'   => 'Invalid media ID.',

                'images.required' => 'Please upload at least one image.',
                'images.array'    => 'Images must be an array.',
                'images.max'      => 'You can upload a maximum of 10 images only.',

                'images.*.image'  => 'Each file must be an image.',
                'images.*.mimes'  => 'Only WebP, JPG, JPEG, and PNG images are allowed.',
                'images.*.max'    => 'Each image must be less than 1MB.',
            ]
        );

        try {

            foreach ($request->file('images') as $image) {

                $fileName = uploadImage(
                    $image,
                    config('fileConstants.IMAGE_ADD')
                );

                MediaImage::create([
                    'media_id'   => $request->media_id,
                    'images'     => $fileName,
                    'is_active'  => 1,
                    'is_deleted' => 0,
                ]);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Images uploaded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Upload failed'
            ], 500);
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
        $radius = RadiusMaster::where('is_active', 1)
            ->where('is_deleted', 0)
            ->get();

        return view('superadm.mediamanagement.create', compact(
            'categories',
            'facings',
            'illuminations',
            'radius'
        ));
    }


    /* =========================
       STORE
    ========================== */
    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         // Location
    //         'state_id'    => 'required|integer',
    //         'district_id' => 'required|integer',
    //         'city_id'     => 'required|integer',
    //         'area_id'     => 'required|integer',

    //         // Media basic
    //         'category_id' => 'required|integer',
    //         'media_code' => 'required|string|max:255|unique:media_management,media_code,NULL,id,is_deleted,0',

    //         'media_title' => 'required|string|max:255',

    //         // Address
    //         'address' => 'required|string',

    //         // Dimensions
    //         'width'  => 'required|numeric|min:0',
    //         'height' => 'required|numeric|min:0',

    //         // Facing & illumination
    //         'facing_id'       => 'required|string',
    //         'illumination_id' => 'required|integer',

    //         // Geo
    //         'latitude'  => 'required|numeric|between:-90,90',
    //         'longitude' => 'required|numeric|between:-180,180',

    //         // Booking & price
    //         'minimum_booking_days' => 'required|integer|min:2',
    //         'price' => 'required|numeric|min:0',

    //         // Vendor
    //         'vendor_name' => 'required|string|max:255',

    //         // Images
    //         // 'images'   => 'required|array|min:1',
    //         // 'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
    //     ]);

    //     try {
    //         $this->mediaService->store($request);
    //         return redirect()
    //             ->route('media.list')
    //             ->with('success', 'Media added successfully');
    //     } catch (\Exception $e) {
    //         Log::error($e);
    //         return back()->withInput()->with('error', $e->getMessage());
    //     }
    // }

    // public function store(Request $request)
    // {
    //     // Get category slug
    //     $category = Category::findOrFail($request->category_id);
    //     $slug = $category->slug;

    //     /**
    //      * -------------------------
    //      * COMMON VALIDATION (ALL)
    //      * -------------------------
    //      */
    //     $rules = [
    //         // Location
    //         'area_id'     => 'required|integer',

    //         // Category
    //         'category_id' => 'required|integer',

    //         // Size
    //         'width'  => 'required|numeric|min:0',
    //         'height' => 'required|numeric|min:0',

    //         // Geo
    //         'latitude'  => 'required|numeric|between:-90,90',
    //         'longitude' => 'required|numeric|between:-180,180',
    //         'vendor_name'     => 'required|string|max:255',
    //         // Price
    //         'price' => 'required|numeric|min:0',
    //         // ✅ MULTIPLE IMAGE VALIDATION
    //         'images'      => 'nullable|array|max:10',
    //         'images.*'    => 'image|max:1024',
    //     ];

    //     /**
    //      * -------------------------
    //      * CATEGORY-WISE VALIDATION
    //      * -------------------------
    //      */
    //     switch ($slug) {

    //         // ✅ Hoardings / Billboards
    //         case 'hoardings':

    //             $rules += [
    //                 'facing_id'       => 'required|string',
    //                 'illumination_id' => 'required|integer',
    //                 'radius_id' => 'required|integer',
    //                 'minimum_booking_days' => 'required|integer|min:1',


    //                 // Media
    //                 'media_code' => 'required|string|max:255|unique:media_management,media_code,NULL,id,is_deleted,0',

    //                 'media_title' => 'required|string|max:255',
    //                 'area_type' => 'required|string|max:255',
    //                 // Address
    //                 'address' => 'required|string',


    //             ];
    //             break;

    //         // ✅ Mall Media
    //         case 'mall-media':
    //             $rules += [
    //                 'mall_name'    => 'required|string|max:255',
    //                 'media_format' => 'required|string',
    //             ];
    //             break;

    //         // ✅ Airport Branding
    //         case 'airport-branding':
    //             $rules += [
    //                 'airport_name' => 'required|string|max:255',
    //                 'zone_type'    => 'required|in:Arrival,Departure',
    //                 'media_type'   => 'required|string',
    //             ];
    //             break;

    //         // ✅ Transit Media
    //         case 'transmit-media':
    //             $rules += [
    //                 'transit_type'  => 'required|string',
    //                 'branding_type' => 'required|string',
    //                 'vehicle_count' => 'required|integer|min:1',
    //             ];
    //             break;

    //         // ✅ Office Branding
    //         case 'office-branding':
    //             $rules += [
    //                 'building_name' => 'required|string|max:255',
    //                 'wall_length'   => 'required|string',
    //             ];
    //             break;

    //         // ✅ Wall Wrap
    //         case 'wall-wrap':
    //             $rules += [
    //                 'area_auto' => 'required|numeric|min:1',
    //             ];
    //             break;

    //         // ✅ Digital Wall (NO extra fields)
    //         case 'digital-wall':
    //             // Only common validation
    //             break;
    //     }

    //     $messages = [
    //         'images.max' => 'You can upload maximum 10 images only.',
    //         // 'images.*.image' => 'Each file must be an image.',
    //         // 'images.*.mimes' => 'Allowed formats: jpg, jpeg, png.',
    //         'images.*.max' => 'Each image must be less than 1MB.',

    //         // Common fields (optional but recommended)
    //         'area_id.required' => 'Please select an area.',
    //         'category_id.required' => 'Please select a category.',
    //         'width.required' => 'Width is required.',
    //         'height.required' => 'Height is required.',
    //         'price.required' => 'Price is required.',
    //     ];
    //     $validated = $request->validate($rules, $messages);


    //     try {
    //         $this->mediaService->store($request);

    //         return redirect()
    //             ->route('media.list')
    //             ->with('success', 'Media added successfully');
    //     } catch (\Exception $e) {
    //         Log::error($e);
    //         return back()->withInput()->with('error', $e->getMessage());
    //     }
    // }
    public function store(Request $request)
    {
        /**
         * -------------------------------------------------
         * STEP 1: Get category & normalize slug
         * -------------------------------------------------
         */
        $category = Category::findOrFail($request->category_id);

        // Works even if slug column is NULL
        $slug = Str::slug($category->slug ?? $category->category_name);

        /**
         * -------------------------------------------------
         * STEP 2: COMMON VALIDATION (ALL CATEGORIES)
         * -------------------------------------------------
         */
        $rules = [
            'area_id'     => 'required|integer',
            'category_id' => 'required|integer',

            'width'       => 'required|numeric|min:0',
            'height'      => 'required|numeric|min:0',

            'latitude'    => 'required|numeric|between:-90,90',
            'longitude'   => 'required|numeric|between:-180,180',

            'price'       => 'required|numeric|min:0',
            'vendor_name' => 'required|string|max:255',

            'images'      => 'nullable|array|max:10',
            'images.*'    => 'image|mimes:webp,jpg,jpeg,png|max:1024',
        ];

        /**
         * -------------------------------------------------
         * STEP 3: CATEGORY-WISE VALIDATION
         * -------------------------------------------------
         * Using str_contains() to match UI slug
         */
        switch (true) {

            // ✅ Hoardings / Billboards
            case str_contains($slug, 'hoardings'):
                $rules += [
                    'media_code' => 'required|string|max:255|unique:media_management,media_code,NULL,id,is_deleted,0',
                    'media_title' => 'required|string|max:255',
                    'facing_id' => 'required',
                    'illumination_id' => 'required',
                    'radius_id' => 'required',
                    // 'minimum_booking_days' => 'required|integer|min:1',
                    'area_type' => 'required',
                    'address' => 'required',
                ];
                break;

            // ✅ Mall Media
            case str_contains($slug, 'mall'):
                $rules += [
                    'mall_name' => 'required|string|max:255',
                    'media_format' => 'required|string',
                ];
                break;

            // ✅ Airport Branding
            case str_contains($slug, 'airport'):
                $rules += [
                    'airport_name' => 'required|string|max:255',
                    'zone_type' => 'required|in:Arrival,Departure',
                    'media_type' => 'required|string',
                ];
                break;

            // ✅ Transit Media
            case str_contains($slug, 'transit'):
                $rules += [
                    'transit_type' => 'required|string',
                    'branding_type' => 'required|string',
                    'vehicle_count' => 'required|integer|min:1',
                ];
                break;

            // ✅ Office Branding
            case str_contains($slug, 'office'):
                $rules += [
                    'building_name' => 'required|string|max:255',
                    'wall_length' => 'required|string',
                ];
                break;

            // ✅ Wall Wrap
            case str_contains($slug, 'wall'):
                $rules += [
                    'radius_id' => 'required',
                    // 'area_auto' => 'required|numeric|min:1',
                ];
                break;
        }

        /**
         * -------------------------------------------------
         * STEP 4: CUSTOM ERROR MESSAGES
         * -------------------------------------------------
         */
        $messages = [
            'area_id.required' => 'Please select an area.',
            'category_id.required' => 'Please select a category.',
            'width.required' => 'Width is required.',
            'height.required' => 'Height is required.',
            'price.required' => 'Price is required.',
            'vendor_name.required' => 'Vendor name is required.',
            'images.max' => 'You can upload a maximum of 10 images.',
            'images.*.mimes' => 'Only WebP, JPG, JPEG, and PNG images are allowed.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.max' => 'Each image must be less than 1MB.',
        ];

        /**
         * -------------------------------------------------
         * STEP 5: VALIDATE REQUEST
         * -------------------------------------------------
         */
        $request->validate($rules, $messages);

        /**
         * -------------------------------------------------
         * STEP 6: SAVE DATA
         * -------------------------------------------------
         */
        try {
            $this->mediaService->store($request);

            return redirect()
                ->route('media.list')
                ->with('success', 'Media added successfully');
        } catch (\Exception $e) {
            Log::error($e);
            return back()->withInput()->with('error', 'Something went wrong');
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

            $categories = Category::where('is_active', 1)->where('is_deleted', 0)->get();
            $facings = FacingDirection::where('is_active', 1)->where('is_deleted', 0)->get();
            $illuminations = Illumination::where('is_active', 1)->where('is_deleted', 0)->get();
            $radius = RadiusMaster::where('is_active', 1)
                ->where('is_deleted', 0)
                ->get();
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
                'areas',
                'radius'
            ));
        } catch (\Exception $e) {
            return redirect()->route('media.list')->with('error', 'Invalid media ID');
        }
    }


    // public function update(Request $request, $encodedId)
    // {
    //     $id = base64_decode($encodedId);

    //     $request->validate([
    //         // 'area_id'     => 'required|integer',
    //         // 'media_code'  => 'required|string|max:255|unique:media_management,media_code,' . $id,
    //         // 'media_title' => 'required|string|max:255',
    //         // 'address'     => 'required|string',

    //         // 'width'  => 'required|numeric|min:0',
    //         // 'height' => 'required|numeric|min:0',

    //         // 'facing_id'       => 'required|integer',
    //         // 'illumination_id' => 'required|integer',

    //         // 'latitude'  => 'required|numeric|between:-90,90',
    //         // 'longitude' => 'required|numeric|between:-180,180',

    //         // 'minimum_booking_days' => 'required|integer|min:1',
    //         // 'price'       => 'required|numeric|min:0',
    //         // 'vendor_name' => 'required|string|max:255',

    //         // 'images'   => 'nullable|array|max:10',
    //         // 'images.*' => 'image|max:1024',
    //     ]);

    //     try {
    //         $this->mediaService->update($id, $request);

    //         return redirect()
    //             ->route('media.list')
    //             ->with('success', 'Media updated successfully');
    //     } catch (\Exception $e) {
    //         Log::error($e);
    //         return back()->withInput()->with('error', 'Update failed');
    //     }
    // }


    public function update(Request $request, $encodedId)
    {
        $id = base64_decode($encodedId);

        /**
         * -------------------------------------------------
         * STEP 1: Get category & normalize slug
         * -------------------------------------------------
         */
        $category = Category::findOrFail($request->category_id);
        $slug = Str::slug($category->slug ?? $category->category_name);

        /**
         * -------------------------------------------------
         * STEP 2: COMMON VALIDATION
         * -------------------------------------------------
         */
        $rules = [
            'area_id'     => 'required|integer',
            'category_id' => 'required|integer',

            'width'       => 'required|numeric|min:0',
            'height'      => 'required|numeric|min:0',

            'latitude'    => 'required|numeric|between:-90,90',
            'longitude'   => 'required|numeric|between:-180,180',

            'price'       => 'required|numeric|min:0',
            'vendor_name' => 'required|string|max:255',
        ];

        /**
         * -------------------------------------------------
         * STEP 3: CATEGORY-WISE VALIDATION
         * -------------------------------------------------
         */
        switch (true) {

            case str_contains($slug, 'hoardings'):
                $rules += [
                    'media_code' => 'required|string|max:255|unique:media_management,media_code,' . $id . ',id,is_deleted,0',
                    'media_title' => 'required|string|max:255',
                    'facing_id' => 'required',
                    'illumination_id' => 'required',
                    'radius_id' => 'required',
                    // 'minimum_booking_days' => 'required|integer|min:1',
                    'area_type' => 'required',
                    'address' => 'required',
                ];
                break;

            case str_contains($slug, 'mall'):
                $rules += [
                    'mall_name' => 'required|string|max:255',
                    'media_format' => 'required|string',
                ];
                break;

            case str_contains($slug, 'airport'):
                $rules += [
                    'airport_name' => 'required|string|max:255',
                    'zone_type' => 'required|in:Arrival,Departure',
                    'media_type' => 'required|string',
                ];
                break;

            case str_contains($slug, 'transit'):
                $rules += [
                    'transit_type' => 'required|string',
                    'branding_type' => 'required|string',
                    'vehicle_count' => 'required|integer|min:1',
                ];
                break;

            case str_contains($slug, 'office'):
                $rules += [
                    'building_name' => 'required|string|max:255',
                    'wall_length' => 'required|string',
                ];
                break;

            case str_contains($slug, 'wall'):
                $rules += [
                    'area_auto' => 'required|numeric|min:1',
                ];
                break;
        }

        /**
         * -------------------------------------------------
         * STEP 4: VALIDATE
         * -------------------------------------------------
         */
        $request->validate($rules);

        /**
         * -------------------------------------------------
         * STEP 5: UPDATE DATA
         * -------------------------------------------------
         */
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

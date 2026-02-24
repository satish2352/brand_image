<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Services\Superadm\MediaManagementService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\{
    Category,
    FacingDirection,
    Illumination,
    MediaManagement,
    MediaImage,
    Vendor,
    State,
    City
};

class MediaManagementController extends Controller
{
    protected $mediaService;
    public function __construct(MediaManagementService $mediaService)
    {
        $this->mediaService = $mediaService;
    }
    // public function index()
    // {
    //     try {
    //         $mediaList = $this->mediaService->getAll();
    //         return view('superadm.mediamanagement.list', compact('mediaList'));
    //     } catch (\Exception $e) {
    //         return redirect()->back()->with('error', 'Something went wrong');
    //     }
    // }
    public function index(Request $request)
    {
        try {
            $filters = [
                'vendor_id'   => $request->vendor_id,
                'category_id' => $request->category_id,
                'district_id' => $request->district_id,
                'city_id'     => $request->city_id,
                'month'       => $request->month,
                'year'        => $request->year,
                'from_date'   => $request->from_date,
                'to_date'     => $request->to_date,
            ];

            $mediaList  = $this->mediaService->getAll($filters);
            $vendors    = Vendor::where('is_active', 1)->where('is_deleted', 0)->get();
            $categories = Category::where('is_active', 1)->where('is_deleted', 0)->get();
            $districts  = DB::table('districts')->where('is_active', 1)->where('is_deleted', 0)->get();

            $years  = getYears();
            $months = getMonths();

            return view('superadm.mediamanagement.list', compact(
                'mediaList',
                'vendors',
                'categories',
                'districts',
                'years',
                'months'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong');
        }
    }


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
        // FETCH VENDORS
        $vendors = Vendor::where('is_active', 1)
            ->where('is_deleted', 0)
            ->orderBy('vendor_name')
            ->get();

        return view('superadm.mediamanagement.create', compact(
            'categories',
            'facings',
            'illuminations',
            'vendors'
        ));
    }
    public function store(Request $request)
    {
        $category = Category::findOrFail($request->category_id);

        $slug = Str::slug($category->slug ?? $category->category_name);

        $rules = [
            'area_id'     => 'required|integer',
            'category_id' => 'required|integer',

            'width'       => 'required|numeric|min:0',
            'height'      => 'required|numeric|min:0',

            'latitude'    => 'required|numeric|between:-90,90',
            'longitude'   => 'required|numeric|between:-180,180',

            'price'       => 'required|numeric|min:0',
            // 'vendor_name' => 'required|string|max:255',
            'vendor_id' => 'required|integer|exists:vendors,id',

            'images'      => 'nullable|array|max:10',
            'images.*'    => 'image|mimes:webp,jpg,jpeg,png|max:1024|dimensions:width=500,height=600',
            // 'panorama_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];

        switch (true) {

            //  Hoardings / Billboards
            case str_contains($slug, 'hoardings'):
                $rules += [
                    // 'media_code' => 'required|string|max:255|unique:media_management,media_code,NULL,id,is_deleted,0',
                    'media_title' => 'required|string|max:255',
                    // 'facing_id' => 'required',
                    'facing' => 'required',
                    'illumination_id' => 'required',

                    // 'minimum_booking_days' => 'required|integer|min:1',
                    'area_type' => 'required',
                    'address' => 'required',
                ];
                break;

            //  Mall Media
            case str_contains($slug, 'mall'):
                $rules += [
                    'mall_name' => 'required|string|max:255',
                    'media_format' => 'required|string',
                ];
                break;

            //  Airport Branding
            case str_contains($slug, 'airport'):
                $rules += [
                    'airport_name' => 'required|string|max:255',
                    'zone_type' => 'required|in:Arrival,Departure',
                    'media_type' => 'required|string',
                ];
                break;

            //  Transit Media
            case str_contains($slug, 'transit'):
                $rules += [
                    'transit_type' => 'required|string',
                    'branding_type' => 'required|string',
                    'vehicle_count' => 'required|integer|min:1',
                ];
                break;

            //  Office Branding
            case str_contains($slug, 'office'):
                $rules += [
                    'building_name' => 'required|string|max:255',
                    'wall_length' => 'required|string',
                ];
                break;

            //  Wall Wrap
            case str_contains($slug, 'wall'):
                $rules += [

                    // 'area_auto' => 'required|numeric|min:1',
                ];
                break;
        }
        $messages = [
            'area_id.required' => 'Please select an area.',
            'category_id.required' => 'Please select a category.',
            'width.required' => 'Width is required.',
            'height.required' => 'Height is required.',
            'price.required' => 'Price is required.',
            // 'vendor_name.required' => 'Vendor name is required.',
            'vendor_id.required' => 'Please select a vendor',
            // 'vendor_id.exists'   => 'Invalid vendor selected',
            'images.max' => 'You can upload a maximum of 10 images.',
            'images.*.mimes' => 'Only WebP, JPG, JPEG, and PNG images are allowed.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.max' => 'Each image must be less than 1MB.',
            'images.*.dimensions' => 'Please upload each image with width 500px and height 600px.',
        ];
        $request->validate($rules, $messages);

        try {
            $this->mediaService->store($request, $slug);

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
            // $radius = RadiusMaster::where('is_active', 1)
            //     ->where('is_deleted', 0)
            //     ->get();
            $areas = DB::table('areas')
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->get();

            $vendors = Vendor::where('is_active', 1)
                ->where('is_deleted', 0)
                ->orderBy('vendor_name')
                ->get();

            return view('superadm.mediamanagement.edit', compact(
                'media',
                'categories',
                'facings',
                'illuminations',
                'encodedId',
                'areas',
                // 'radius',
                'vendors'
            ));
        } catch (\Exception $e) {
            return redirect()->route('media.list')->with('error', 'Invalid media ID');
        }
    }
    public function update(Request $request, $encodedId)
    {
        $id = base64_decode($encodedId);
        $category = Category::findOrFail($request->category_id);
        $slug = Str::slug($category->slug ?? $category->category_name);

        $rules = [
            'area_id'     => 'required|integer',
            'category_id' => 'required|integer',

            'width'       => 'required|numeric|min:0',
            'height'      => 'required|numeric|min:0',

            'latitude'    => 'required|numeric|between:-90,90',
            'longitude'   => 'required|numeric|between:-180,180',

            'price'       => 'required|numeric|min:0',
            // 'vendor_name' => 'required|string|max:255',
            'vendor_id' => 'required|integer|exists:vendors,id',
        ];

        switch (true) {

            case str_contains($slug, 'hoardings'):
                $rules += [
                    // 'media_code' => 'required|string|max:255|unique:media_management,media_code,' . $id . ',id,is_deleted,0',
                    'media_title' => 'required|string|max:255',
                    // 'facing_id' => 'required',
                    'facing' => 'required',
                    'illumination_id' => 'required',

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
        $request->validate($rules);
        try {
            $this->mediaService->update($id, $request, $slug);

            return redirect()
                ->route('media.list')
                ->with('success', 'Media updated successfully');
        } catch (\Exception $e) {
            Log::error($e);
            return back()->withInput()->with('error', 'Update failed');
        }
    }
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

        logger('Decoded Media ID:', [$id]);

        $media = $this->mediaService->viewDetails($id);

        if (!$media) {
            abort(404);
        }

        return view('superadm.mediamanagement.viewDetails', compact('media'));
    }
    public function deleteImage(Request $request)
    {
        try {

            $request->validate([
                'image_id' => 'required|integer'
            ]);

            // Get image record
            $image = MediaImage::where('id', $request->image_id)
                ->where('is_deleted', 0)
                ->firstOrFail();

            // DELETE FILE FIRST (IMPORTANT)
            removeImage(
                $image->images,
                config('fileConstants.IMAGE_DELETE')
            );

            // SOFT DELETE DB RECORD
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
                'images.*'   => 'image|mimes:webp,jpg,jpeg,png|max:1024|dimensions:width=500,height=600',
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
                'images.*.dimensions' => 'Please upload each image with width 500px and height 600px.',
            ]
        );

        // NEW VALIDATION : TOTAL IMAGE LIMIT PER MEDIA
        $existingCount = MediaImage::where('media_id', $request->media_id)
            ->where('is_deleted', 0)
            ->count();

        $newCount = count($request->file('images'));

        if (($existingCount + $newCount) > 10) {
            return response()->json([
                'status'  => false,
                'message' => 'You can upload maximum 10 images per media.'
            ], 422);
        }

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
        $area = DB::table('areas')->where('id', $areaId)->firstOrFail();

        return response()->json([
            'city_id'     => $area->city_id,
            'district_id' => $area->district_id,
            'state_id'    => $area->state_id,
        ]);
    }
    public function getStates()
    {
        return response()->json(
            DB::table('states')
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->select('id', 'state_name')
                ->orderBy('state_name')
                ->get()
        );
    }
    public function getDistricts($stateId)
    {
        return response()->json(
            DB::table('districts')
                ->where('state_id', $stateId)
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->select('id', 'district_name')
                ->orderBy('district_name')
                ->get()
        );
    }
    public function getCities($districtId)
    {
        return response()->json(
            DB::table('cities')
                ->where('district_id', $districtId)
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->select('id', 'city_name')
                ->orderBy('city_name')
                ->get()
        );
    }
    public function getAreas($cityId)
    {
        return response()->json(
            DB::table('areas')
                ->where('city_id', $cityId)
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->select('id', 'area_name')
                ->orderBy('area_name')
                ->get()
        );
    }
    // public function getNextMediaCode($vendorId)
    // {
    //     $vendor = Vendor::where('id', $vendorId)
    //         ->where('is_deleted', 0)
    //         ->firstOrFail();

    //     $vendorCode = $vendor->vendor_code;

    //     // Get LAST sequence number safely
    //     $lastMedia = MediaManagement::where('vendor_id', $vendorId)
    //         ->where('is_deleted', 0)
    //         ->where('media_code', 'LIKE', $vendorCode . '\_%')
    //         ->orderByRaw("
    //             CAST(
    //                 SUBSTRING_INDEX(media_code, '_', -1
    //             ) AS UNSIGNED
    //         ) DESC
    //         ")
    //         ->first();

    //     if ($lastMedia) {
    //         $lastNumber = (int) substr(strrchr($lastMedia->media_code, '_'), 1);
    //         $next = $lastNumber + 1;
    //     } else {
    //         $next = 1;
    //     }

    //     return response()->json([
    //         'media_code' => $vendorCode . '_' . str_pad($next, 2, '0', STR_PAD_LEFT)
    //     ]);
    // }

    public function getNextMediaCode(Request $request)
    {
        $vendorId = $request->vendor_id;
        $stateId  = $request->state_id;
        $cityId   = $request->city_id;

        // ===== GET DATA =====
        $vendor = Vendor::where('id', $vendorId)
            ->where('is_deleted', 0)
            ->firstOrFail();

        $state = State::findOrFail($stateId);
        $city  = City::findOrFail($cityId);

        // ===== CREATE CODES =====
        $stateCode = strtoupper(substr($state->state_name, 0, 3)); // MSH
        $cityCode  = strtoupper(substr($city->city_name, 0, 3));   // NSK
        $vendorCode = strtoupper($vendor->vendor_code);

        // PREFIX
        $prefix = $stateCode . '_' . $cityCode . '_' . $vendorCode;

        // ===== FIND LAST MEDIA =====
        $lastMedia = MediaManagement::where('vendor_id', $vendorId)
            ->where('state_id', $stateId)
            ->where('city_id', $cityId)
            ->where('is_deleted', 0)
            ->where('media_code', 'LIKE', $prefix . '\_%')
            ->orderByRaw("
            CAST(
                SUBSTRING_INDEX(media_code, '_', -1)
            AS UNSIGNED
        ) DESC
        ")
            ->first();

        if ($lastMedia) {
            $lastNumber = (int) substr(strrchr($lastMedia->media_code, '_'), 1);
            $next = $lastNumber + 1;
        } else {
            $next = 1;
        }

        return response()->json([
            'media_code' => $prefix . '_' . str_pad($next, 2, '0', STR_PAD_LEFT)
        ]);
    }
}

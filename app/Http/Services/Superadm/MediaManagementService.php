<?php

namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\MediaManagementRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\MediaImage;
use Illuminate\Support\Facades\Storage;

class MediaManagementService
{
    protected $repo;

    public function __construct(MediaManagementRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getAll($filters = [])
    {
        return $this->repo->getAll($filters);
    }

    public function store(Request $request, string $slug)
    {
        DB::beginTransaction();

        try {
            $mediaData = $request->only([
                'state_id',
                'district_id',
                'city_id',
                'area_id',
                'category_id',
                // 'media_code',
                'media_title',
                'address',
                'width',
                'height',
                'illumination_id',
                'facing_id',
                'facing',
                'latitude',
                'longitude',
                'minimum_booking_days',
                'price',
                // 'vendor_name',
                'vendor_id',
                // 'video_link',
                'panorama_image'

            ]);

            // ONLY HOARDINGS
            if (str_contains($slug, 'hoardings')) {
                $mediaData['media_code'] = $request->media_code;
            } else {
                $mediaData['media_code'] = null;
            }

            // AUTO GENERATE MEDIA CODE
            // $mediaData['media_code'] = $this->generateMediaCode($request->vendor_id);

            /** -------------------------
             * OPTIONAL FIELDS
             * ------------------------*/
            $optionalFields = [
                'mall_name',
                'media_format',
                'airport_name',
                'zone_type',
                'media_type',
                'transit_type',
                'branding_type',
                'vehicle_count',
                'building_name',
                'wall_length',
                'area_auto',
                'radius_id',
                'areatype_id',
                'highway_id',
                // 'area_type',
                'video_link',
                'panorama_image'
            ];
            foreach ($optionalFields as $field) {
                $mediaData[$field] = $request->input($field);
            }

            // AUTO-GENERATE UNIQUE HOARDING CODE (HD000001, HD000002, ...)
            // Only for Hoardings/Billboards — HD###### names a hoarding site, so
            // a mall, airport, transit or wall record is saved without one
            // rather than being handed a number nothing ever shows. Matches the
            // bulk import, which issues codes on the same rule.
            $mediaData['hoarding_code'] = $this->takesHoardingCode($slug)
                ? $this->generateHoardingCode()
                : null;
            // foreach ($optionalFields as $field) {
            //     if ($request->has($field)) {
            //         $mediaData[$field] = $request->$field;
            //     }
            // }

            $mediaData['is_active']  = 1;
            $mediaData['is_deleted'] = 0;

            /** -------------------------
             * SAVE MEDIA
             * ------------------------*/
            $media = $this->repo->store($mediaData);

            /** SYNC LANDMARKS (many-to-many) */
            $landmarkIds = array_filter((array) $request->input('landmark_ids', []));
            $media->landmarks()->sync($landmarkIds);

            /**  SAVE IMAGES */
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {

                    $fileName = uploadImage(
                        $image,
                        config('fileConstants.IMAGE_ADD')
                    );
                    /** -------------------------
                     * SAVE PANORAMA IMAGE
                     * ------------------------*/
                    if ($request->hasFile('panorama_image')) {

                        $panoramaName = uploadImage(
                            $request->file('panorama_image'),
                            config('fileConstants.IMAGE_ADD')
                        );

                        $media->update([
                            'panorama_image' => $panoramaName
                        ]);
                    }
                    MediaImage::create([
                        'media_id'  => $media->id,
                        'images'    => $fileName,
                        'is_active' => 1,
                        'is_deleted' => 0,
                    ]);
                }
            }

            DB::commit();
            return $media;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function update($id, Request $request, string $slug)
    {
        DB::beginTransaction();

        try {

            // ⭐ FETCH OLD MEDIA FIRST
            $media = $this->repo->find($id);

            $updateData = $request->only([
                'state_id',
                'district_id',
                'city_id',
                'area_id',
                'category_id',
                'media_title',
                'address',
                'width',
                'height',
                'illumination_id',
                'areatype_id',
                'facing',
                'latitude',
                'longitude',
                'minimum_booking_days',
                'price',
                'vendor_id',
                'area_auto',
                'highway_id'
            ]);

            // MEDIA CODE + HOARDING CODE
            if ($this->takesHoardingCode($slug)) {
                $updateData['media_code'] = $request->media_code;

                // Hoarding code is now editable: use the entered value, or
                // auto-generate one when blank (covers existing hoardings that
                // were added before auto-codes and still have no code).
                $hoardingCode = trim((string) $request->input('hoarding_code'));
                if ($hoardingCode === '') {
                    $hoardingCode = $media->hoarding_code ?: $this->generateHoardingCode();
                }
                $updateData['hoarding_code'] = $hoardingCode;
            } else {
                $updateData['media_code'] = null;
            }

            /** UPDATE BASIC DATA */
            $this->repo->update($id, $updateData);

            /** SYNC LANDMARKS (many-to-many) */
            $landmarkIds = array_filter((array) $request->input('landmark_ids', []));
            $media->landmarks()->sync($landmarkIds);

            /** 🔥 PANORAMA UPDATE */
            if ($request->hasFile('panorama_image')) {

                // REMOVE OLD FILE
                if (!empty($media->panorama_image)) {

                    removeImage(
                        $media->panorama_image,
                        config('fileConstants.IMAGE_DELETE')
                    );
                }

                // UPLOAD NEW
                $panoramaName = uploadImage(
                    $request->file('panorama_image'),
                    config('fileConstants.IMAGE_ADD')
                );

                // UPDATE DB
                $this->repo->update($id, [
                    'panorama_image' => $panoramaName
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function toggleStatus($id)
    {
        $this->repo->toggleStatus($id);
    }
    public function delete($id)
    {
        DB::beginTransaction();

        try {

            // 🔹 Get all active images of this media
            $images = MediaImage::where('media_id', $id)
                ->where('is_deleted', 0)
                ->get();

            // 🔹 Delete image files first
            foreach ($images as $img) {
                removeImage(
                    $img->images,
                    config('fileConstants.IMAGE_DELETE')
                );
            }

            // 🔹 Soft delete image records
            MediaImage::where('media_id', $id)->update([
                'is_deleted' => 1,
                'is_active'  => 0
            ]);

            // 🔹 Soft delete media
            $this->repo->softDelete($id);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    private function generateMediaCode(int $vendorId): string
    {
        // Get vendor code
        $vendor = DB::table('vendors')->where('id', $vendorId)->first();

        if (!$vendor) {
            throw new \Exception('Vendor not found');
        }

        $vendorCode = $vendor->vendor_code;

        // Count existing media for this vendor
        $count = DB::table('media_management')
            ->where('vendor_id', $vendorId)
            ->where('is_deleted', 0)
            ->count();

        // Next sequence
        $next = str_pad($count + 1, 2, '0', STR_PAD_LEFT);

        return $vendorCode . '_' . $next;
    }

    /**
     * Does this category's media carry a Hoarding Code at all?
     *
     * Only Hoardings/Billboards do. Matched on the slug, the same test the rest
     * of the form already uses for the Media Code, so a renamed or additional
     * hoarding-like category still resolves.
     */
    private function takesHoardingCode(string $slug): bool
    {
        return str_contains($slug, 'hoarding') || str_contains($slug, 'billboard');
    }

    /**
     * Generate the next globally-unique hoarding code: HD000001, HD000002, ...
     * Runs inside the caller's DB transaction; the UNIQUE constraint on the
     * column is the final guard against concurrent collisions.
     */
    private function generateHoardingCode(): string
    {
        $maxCode = DB::table('media_management')
            ->whereNotNull('hoarding_code')
            ->where('hoarding_code', 'like', 'HD%')
            ->orderByRaw('CAST(SUBSTRING(hoarding_code, 3) AS UNSIGNED) DESC')
            ->value('hoarding_code');

        $next = $maxCode ? ((int) substr($maxCode, 2)) + 1 : 1;

        return 'HD' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }

    public function viewDetails($id)
    {
        $rows = $this->repo->getDetailsById($id);
        if ($rows->isEmpty()) {
            return null;
        }
        $media = $rows->first();
        $media->images = $rows
            ->whereNotNull('image_name')
            ->map(function ($row) {
                return [
                    'id'    => $row->image_id,
                    'image' => $row->image_name,
                ];
            })
            ->values();

        // Attach tagged landmark names (many-to-many)
        $media->landmark_names = DB::table('media_landmark as ml')
            ->join('landmark as l', 'l.id', '=', 'ml.landmark_id')
            ->where('ml.media_id', $id)
            ->where('l.is_deleted', 0)
            ->pluck('l.landmark_name')
            ->toArray();

        return $media;
    }
}

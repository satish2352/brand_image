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

    public function getAll()
    {
        $data_output = $this->repo->getAll();

        return $data_output;
    }
    public function viewDetails($id)
    {
        $rows = $this->repo->getDetailsById($id);

        if ($rows->isEmpty()) {
            return null;
        }

        /* ---------- FORMAT DATA ---------- */
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

        return $media;
    }


    /* =========================
       STORE
    ========================== */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            /** -------------------------
             * REQUIRED FIELDS
             * ------------------------*/
            $mediaData = $request->only([
                'state_id',
                'district_id',
                'city_id',
                'area_id',
                'category_id',
                'media_code',
                'media_title',
                'address',
                'width',
                'height',
                'illumination_id',
                'facing_id',
                'latitude',
                'longitude',
                'minimum_booking_days',
                'price',
                'vendor_name',
            ]);

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
            ];

            foreach ($optionalFields as $field) {
                if ($request->filled($field)) {
                    $mediaData[$field] = $request->$field;
                }
            }

            $mediaData['is_active']  = 1;
            $mediaData['is_deleted'] = 0;

            /** -------------------------
             * SAVE MEDIA
             * ------------------------*/
            $media = $this->repo->store($mediaData);


            /** ✅ SAVE IMAGES */
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {

                    $fileName = uploadImage(
                        $image,
                        config('fileConstants.IMAGE_ADD')
                    );

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

    /* =========================
       UPDATE
    ========================== */
    public function update($id, Request $request)
    {
        DB::beginTransaction();

        try {

            $updateData = $request->only([
                'state_id',
                'district_id',
                'city_id',
                'area_id',
                'category_id',

                'media_code',
                'media_title',
                'address',

                'width',
                'height',

                'illumination_id',
                'facing_id',

                'latitude',
                'longitude',

                'minimum_booking_days',
                'price',
                'vendor_name',
            ]);

            // Optional category fields
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
            ];

            foreach ($optionalFields as $field) {
                if ($request->filled($field)) {
                    $updateData[$field] = $request->$field;
                }
            }

            /** 🔹 UPDATE MEDIA */
            $this->repo->update($id, $updateData);

            /** 🔹 REPLACE IMAGES (REMOVE FIRST, THEN ADD) */
            if ($request->hasFile('images')) {

                // 1️⃣ Fetch old images
                $oldImages = MediaImage::where('media_id', $id)
                    ->where('is_deleted', 0)
                    ->get();

                // 2️⃣ REMOVE FILES USING IMAGE_DELETE
                foreach ($oldImages as $old) {
                    removeImage(
                        $old->images,
                        config('fileConstants.IMAGE_DELETE')
                    );
                }

                // 3️⃣ SOFT DELETE DB RECORDS
                MediaImage::where('media_id', $id)->update([
                    'is_active'  => 0,
                    'is_deleted' => 1
                ]);

                // 4️⃣ UPLOAD NEW FILES USING IMAGE_ADD
                foreach ($request->file('images') as $image) {

                    $fileName = uploadImage(
                        $image,
                        config('fileConstants.IMAGE_ADD')
                    );

                    MediaImage::create([
                        'media_id'   => $id,
                        'images'     => $fileName,
                        'is_active'  => 1,
                        'is_deleted' => 0,
                    ]);
                }
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
}

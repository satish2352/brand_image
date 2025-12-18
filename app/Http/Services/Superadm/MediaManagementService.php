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

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            // 1️⃣ Save media
            $media = $this->repo->store(array_merge(
                $request->only([
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
                    'vendor_name'
                ]),
                [
                    'is_active'  => 1,
                    'is_deleted' => 0
                ]
            ));

            // 2️⃣ Handle images
            if ($request->hasFile('images')) {

                foreach ($request->file('images') as $file) {

                    $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                    // ✅ Store file
                    $file->storeAs(
                        'public/upload/images/media',
                        $fileName
                    );

                    // ✅ Save DB record
                    MediaImage::create([
                        'media_id'  => $media->id,
                        'images'    => $fileName,   // ✔ correct column
                        'is_active' => 1,
                        'is_deleted' => 0
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




    public function update($id, Request $request)
    {
        DB::beginTransaction();

        try {

            $media = $this->repo->find($id);

            // Update media data
            $this->repo->update($id, $request->only([
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
            ]));

            // Replace images if new uploaded
            if ($request->hasFile('images')) {

                // Soft delete old images
                MediaImage::where('media_id', $id)
                    ->update(['is_deleted' => 1, 'is_active' => 0]);

                foreach ($request->file('images') as $file) {

                    $name = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('uploads/media'), $name);

                    MediaImage::create([
                        'media_id' => $id,
                        'images' => $name,
                        'is_active' => 1,
                        'is_deleted' => 0
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
        $this->repo->softDelete($id);
    }
}

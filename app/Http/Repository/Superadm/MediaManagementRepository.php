<?php

namespace App\Http\Repository\Superadm;

use App\Models\MediaManagement;
use Illuminate\Support\Facades\DB;

class MediaManagementRepository
{
    public function getAll()
    {
        return DB::table('media_management as m')
            ->leftJoin('tbl_location as state', 'state.location_id', '=', 'm.state_id')
            ->leftJoin('tbl_location as district', 'district.location_id', '=', 'm.district_id')
            ->leftJoin('tbl_location as city', 'city.location_id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
            ->leftJoin('category', 'm.category_id', '=', 'category.id')
            ->select([
                'm.id',
                'm.media_code',
                'm.media_title',
                'm.price',
                'm.is_active',
                'm.category_id',
                'm.created_at',
                'category.category_name',
                'state.name as state_name',
                'district.name as district_name',
                'city.name as city_name',
                'a.common_stdiciar_name as area_name',
                'c.category_name',
            ])
            ->where('m.is_deleted', 0)
            ->orderBy('m.id', 'desc')
            ->get();
    }

    public function getDetailsById($id)
    {

        return DB::table('media_management as mm')

            /* ---------- LOCATION JOINS ---------- */
            ->leftJoin('tbl_location as st', 'st.location_id', '=', 'mm.state_id')
            ->leftJoin('tbl_location as dt', 'dt.location_id', '=', 'mm.district_id')
            ->leftJoin('tbl_location as ct', 'ct.location_id', '=', 'mm.city_id')
            ->leftJoin('tbl_location as ar', 'ar.location_id', '=', 'mm.area_id')

            /* ---------- MASTER JOINS ---------- */
            ->leftJoin('category as cat', 'cat.id', '=', 'mm.category_id')
            ->leftJoin('facing_direction as fd', 'fd.id', '=', 'mm.facing_id')
            ->leftJoin('illumination as il', 'il.id', '=', 'mm.illumination_id')

            /* ---------- IMAGES JOIN ---------- */
            ->leftJoin('media_images as mi', function ($join) {
                $join->on('mi.media_id', '=', 'mm.id')
                    ->where('mi.is_deleted', 0);
            })

            /* ---------- FILTER ---------- */
            ->where('mm.category_id', $id)
            ->where('mm.is_deleted', 0)

            /* ---------- SELECT ---------- */
            ->select(
                'mm.*',

                'cat.category_name',
                'cat.slug as category_slug',

                'st.name as state_name',
                'dt.name as district_name',
                'ct.name as city_name',
                'ar.name as area_name',

                'fd.facing_name',
                'il.illumination_name',

                'mi.id as image_id',
                'mi.images as image_name'
            )


            ->get();
    }

    public function store(array $data)
    {
        return MediaManagement::create($data);
    }

    public function update($id, array $data)
    {
        return MediaManagement::where('id', $id)->update($data);
    }


    public function find($id)
    {
        return MediaManagement::findOrFail($id);
    }

    public function toggleStatus($id)
    {
        $media = $this->find($id);
        $media->update(['is_active' => !$media->is_active]);
    }

    public function softDelete($id)
    {
        return MediaManagement::where('id', $id)->update([
            'is_deleted' => 1,
            'is_active'  => 0
        ]);
    }
}

<?php

namespace App\Http\Repository\Website;


use App\Models\MediaManagement;
use Illuminate\Support\Facades\DB;

class HomeRepository
{
    public function getAllMediaCartsData()
    {
        return DB::table('media_management as m')

            // 🔹 Location joins
            ->leftJoin('tbl_location as state', 'state.location_id', '=', 'm.state_id')
            ->leftJoin('tbl_location as district', 'district.location_id', '=', 'm.district_id')
            ->leftJoin('tbl_location as city', 'city.location_id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')

            // 🔹 Category
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')

            // 🔹 FIRST IMAGE JOIN (IMPORTANT PART)
            ->leftJoin(
                DB::raw('
                (SELECT media_id, MIN(images) as first_image
                 FROM media_images
                 WHERE is_deleted = 0 AND is_active = 1
                 GROUP BY media_id) as mi
            '),
                'mi.media_id',
                '=',
                'm.id'
            )

            ->select([
                'm.id',
                'm.media_code',
                'm.media_title',
                'm.price',
                'm.is_active',
                'm.category_id',
                'm.created_at',

                // category
                'c.category_name',

                // location
                'state.name as state_name',
                'district.name as district_name',
                'city.name as city_name',
                'a.common_stdiciar_name as area_name',

                // ✅ FIRST IMAGE
                'mi.first_image'
            ])

            ->where('m.is_deleted', 0)
            ->orderBy('m.id', 'desc')
            ->get();
    }
}

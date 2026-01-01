<?php

namespace App\Http\Repository\Website;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomeRepository
{
    // public function searchMedia(array $filters)
    // {
    //     $query = DB::table('media_management as m')
    //         ->leftJoin('tbl_location as city', 'city.location_id', '=', 'm.city_id')
    //         ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
    //         ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
    //         ->leftJoin('radius_master as rd', 'rd.id', '=', 'm.radius_id')
    //         ->leftJoin(DB::raw('
    //         (SELECT media_id, MIN(images) AS first_image
    //          FROM media_images
    //          WHERE is_deleted = 0 AND is_active = 1
    //          GROUP BY media_id) mi
    //     '), 'mi.media_id', '=', 'm.id')

    //         ->where('m.is_deleted', 0)
    //         ->where('m.is_active', 1)

    //         ->select([
    //             'm.id',
    //             'm.media_title',
    //             'm.price',
    //             'c.category_name',
    //             'm.area_type',
    //             'city.name as city_name',
    //             'a.common_stdiciar_name as area_name',
    //             'mi.first_image',
    //             DB::raw('ROUND(m.price / DAY(LAST_DAY(CURDATE())), 2) as per_day_price')
    //         ]);

    //     /* CATEGORY */
    //     if (!empty($filters['category_id'])) {
    //         $query->where('m.category_id', $filters['category_id']);
    //     }
    //     if (!empty($filters['radius_id'])) {
    //         $query->where('rd.radius', $filters['radius_id']);
    //     }
    //     /* AREA TYPE (RURAL / URBAN) */
    //     if (!empty($filters['area_type'])) {
    //         $query->where('m.area_type', $filters['area_type']);
    //     }

    //     /* LOCATION */
    //     if (!empty($filters['state_id'])) {
    //         $query->where('m.state_id', $filters['state_id']);
    //     }

    //     if (!empty($filters['district_id'])) {
    //         $query->where('m.district_id', $filters['district_id']);
    //     }

    //     if (!empty($filters['city_id'])) {
    //         $query->where('m.city_id', $filters['city_id']);
    //     }

    //     if (!empty($filters['area_id'])) {
    //         $query->where('m.area_id', $filters['area_id']);
    //     }
    //     /* AVAILABLE DAYS (7 / 15 → updated_at) */
    //     if (!empty($filters['available_days'])) {
    //         $query->where(
    //             'm.updated_at',
    //             '>=',
    //             now()->subDays((int)$filters['available_days'])
    //         );
    //     }
    //     /* DATE FILTER – USE updated_at (as you requested) */
    //     if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
    //         $query->whereBetween(
    //             DB::raw('DATE(m.updated_at)'),
    //             [$filters['from_date'], $filters['to_date']]
    //         );
    //     }

    //     return $query->orderBy('m.id', 'DESC')->get();
    // }


    public function searchMedia(array $filters)
    {
        $query = DB::table('media_management as m')
            ->leftJoin('tbl_location as city', 'city.location_id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
            ->leftJoin('radius_master as rd', 'rd.id', '=', 'm.radius_id')
            ->leftJoin(DB::raw('
            (SELECT media_id, MIN(images) AS first_image
             FROM media_images
             WHERE is_deleted = 0 AND is_active = 1
             GROUP BY media_id
            ) mi
        '), 'mi.media_id', '=', 'm.id')

            ->where('m.is_deleted', 0)
            ->where('m.is_active', 1)

            ->select([
                'm.id',
                'm.media_title',
                'm.price',
                'm.category_id',
                'c.category_name',
                'm.area_type',
                'city.name as city_name',
                'a.common_stdiciar_name as area_name',
                'mi.first_image',
                DB::raw('ROUND(m.price / DAY(LAST_DAY(CURDATE())), 2) as per_day_price')


            ]);

        /* FILTERS */
        if (!empty($filters['category_id'])) {
            $query->where('m.category_id', $filters['category_id']);
        }

        if (!empty($filters['radius_id'])) {
            $query->where('rd.radius', $filters['radius_id']);
        }

        if (!empty($filters['area_type'])) {
            $query->where('m.area_type', $filters['area_type']);
        }

        if (!empty($filters['state_id'])) {
            $query->where('m.state_id', $filters['state_id']);
        }

        if (!empty($filters['district_id'])) {
            $query->where('m.district_id', $filters['district_id']);
        }

        if (!empty($filters['city_id'])) {
            $query->where('m.city_id', $filters['city_id']);
        }

        if (!empty($filters['area_id'])) {
            $query->where('m.area_id', $filters['area_id']);
        }

        if (!empty($filters['available_days'])) {

            $days = (int) $filters['available_days'];
            $today = now()->toDateString();

            $query->addSelect(DB::raw("
        CASE
            WHEN NOT EXISTS (
                SELECT 1 FROM media_booked_date mbd
                WHERE mbd.media_id = m.id
                AND mbd.is_active = 1
                AND mbd.is_deleted = 0
            )
            THEN 1

            WHEN EXISTS (
                SELECT 1 FROM media_booked_date mbd
                WHERE mbd.media_id = m.id
                AND mbd.is_active = 1
                AND mbd.is_deleted = 0
                AND DATEDIFF(mbd.from_date, '{$today}') >= {$days}
            )
            THEN 1

            ELSE 0
        END AS is_available_days
    "));
        }


        // if (!empty($filters['available_days'])) {
        //     $query->where(
        //         'm.updated_at',
        //         '>=',
        //         now()->subDays((int)$filters['available_days'])
        //     );
        // }

        // if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
        //     $query->whereBetween(
        //         DB::raw('DATE(m.updated_at)'),
        //         [$filters['from_date'], $filters['to_date']]
        //     );
        // }
        /* ✅ BOOKING STATUS LOGIC */
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {

            $fromDate = $filters['from_date'];
            $toDate   = $filters['to_date'];

            $query->addSelect(DB::raw("
            CASE
                WHEN EXISTS (
                    SELECT 1 FROM media_booked_date mbd
                    WHERE mbd.media_id = m.id
                    AND mbd.is_deleted = 0
                    AND mbd.is_active = 1
                    AND mbd.from_date <= '{$toDate}'
                    AND mbd.to_date >= '{$fromDate}'
                )
                THEN 1 ELSE 0
            END AS is_booked
        "));
        } else {

            $query->addSelect(DB::raw("
            CASE
                WHEN EXISTS (
                    SELECT 1 FROM media_booked_date mbd
                    WHERE mbd.media_id = m.id
                    AND mbd.is_deleted = 0
                    AND mbd.is_active = 1
                )
                THEN 1 ELSE 0
            END AS is_booked
        "));
        }




        //  PAGINATION (REQUIRED FOR LAZY LOADING)
        return $query->orderBy('m.id', 'DESC')->paginate(1);
    }




    public function getMediaDetails($mediaId)
    {
        $media = DB::table('media_management as m')
            ->leftJoin('tbl_location as state', 'state.location_id', '=', 'm.state_id')
            ->leftJoin('tbl_location as district', 'district.location_id', '=', 'm.district_id')
            ->leftJoin('tbl_location as city', 'city.location_id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
            ->where('m.id', $mediaId)
            ->where('m.is_deleted', 0)
            ->select([
                'm.*',
                'c.category_name',
                'state.name as state_name',
                'district.name as district_name',
                'city.name as city_name',
                'a.common_stdiciar_name as area_name',
                DB::raw('ROUND(m.price / DAY(LAST_DAY(CURDATE())),2) as per_day_price')
            ])
            ->first();

        if ($media) {
            $media->images = DB::table('media_images')
                ->where('media_id', $mediaId)
                ->where('is_deleted', 0)
                ->where('is_active', 1)
                ->get();
        }

        return $media;
    }
}
// namespace App\Http\Repository\Website;


// use App\Models\MediaManagement;
// use Illuminate\Support\Facades\DB;

// class HomeRepository
// {
//     public function getAllMediaCartsData()
//     {
//         return DB::table('media_management as m')

//             // 🔹 Location joins
//             ->leftJoin('tbl_location as state', 'state.location_id', '=', 'm.state_id')
//             ->leftJoin('tbl_location as district', 'district.location_id', '=', 'm.district_id')
//             ->leftJoin('tbl_location as city', 'city.location_id', '=', 'm.city_id')
//             ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')

//             // 🔹 Category
//             ->leftJoin('category as c', 'c.id', '=', 'm.category_id')

//             // 🔹 FIRST IMAGE JOIN (IMPORTANT PART)
//             ->leftJoin(
//                 DB::raw('
//                 (SELECT media_id, MIN(images) as first_image
//                  FROM media_images
//                  WHERE is_deleted = 0 AND is_active = 1
//                  GROUP BY media_id) as mi
//             '),
//                 'mi.media_id',
//                 '=',
//                 'm.id'
//             )

//             ->select([
//                 'm.id',
//                 'm.media_code',
//                 'm.media_title',
//                 'm.price',
//                 'm.is_active',
//                 'm.category_id',
//                 'm.created_at',

//                 // category
//                 'c.category_name',

//                 // location
//                 'state.name as state_name',
//                 'district.name as district_name',
//                 'city.name as city_name',
//                 'a.common_stdiciar_name as area_name',

//                 // ✅ FIRST IMAGE
//                 'mi.first_image',
//                 DB::raw('
//         ROUND(
//             m.price / DAY(LAST_DAY(CURDATE())),
//             2
//         ) as per_day_price
//     ')
//             ])

//             ->where('m.is_deleted', 0)
//             ->orderBy('m.id', 'desc')
//             ->get();
//     }

//     public function searchMedia(array $filters)
//     {
//         $query = DB::table('media_management as m')

//             // Locations
//             ->leftJoin('tbl_location as state', 'state.location_id', '=', 'm.state_id')
//             ->leftJoin('tbl_location as district', 'district.location_id', '=', 'm.district_id')
//             ->leftJoin('tbl_location as city', 'city.location_id', '=', 'm.city_id')
//             ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')

//             // Category
//             ->leftJoin('category as c', 'c.id', '=', 'm.category_id')

//             // First image
//             ->leftJoin(DB::raw('
//                 (SELECT media_id, MIN(images) as first_image
//                  FROM media_images
//                  WHERE is_deleted = 0 AND is_active = 1
//                  GROUP BY media_id) mi
//             '), 'mi.media_id', '=', 'm.id')

//             ->where('m.is_deleted', 0)
//             ->where('m.is_active', 1)

//             ->select([
//                 'm.id',
//                 'm.media_code',
//                 'm.media_title',
//                 'm.price',
//                 'c.category_name',
//                 'state.name as state_name',
//                 'city.name as city_name',
//                 'a.common_stdiciar_name as area_name',
//                 'mi.first_image',
//                 DB::raw('ROUND(m.price / DAY(LAST_DAY(CURDATE())), 2) as per_day_price')
//             ]);

//         /* 🔍 Dynamic Filters */

//         if (!empty($filters['state_id'])) {
//             $query->where('m.state_id', $filters['state_id']);
//         }

//         if (!empty($filters['city_id'])) {
//             $query->where('m.city_id', $filters['city_id']);
//         }

//         if (!empty($filters['area_id'])) {
//             $query->where('m.area_id', $filters['area_id']);
//         }

//         if (!empty($filters['category_id'])) {
//             $query->where('m.category_id', $filters['category_id']);
//         }

//         if (!empty($filters['keyword'])) {
//             $query->where(function ($q) use ($filters) {
//                 $q->where('m.media_title', 'like', '%' . $filters['keyword'] . '%')
//                     ->orWhere('m.media_code', 'like', '%' . $filters['keyword'] . '%');
//             });
//         }

//         return $query->orderBy('m.id', 'desc')->get();
//     }



   
// }

<?php

namespace App\Http\Repository\Website;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomeRepository
{
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
                'm.latitude',
                'm.longitude',
                'c.category_name',
                'm.area_type',
                'city.name as city_name',
                'a.common_stdiciar_name as area_name',
                'mi.first_image',
                DB::raw('ROUND(m.price / DAY(LAST_DAY(CURDATE())), 2) as per_day_price')


            ]);


        $centerLat = null;
        $centerLng = null;

        if (!empty($filters['city_id'])) {

            $center = DB::table('media_management')
                ->where('city_id', $filters['city_id'])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->select('latitude', 'longitude')
                ->first();

            if ($center) {
                $centerLat = $center->latitude;
                $centerLng = $center->longitude;
            }
        }


        /* FILTERS */
        if (!empty($filters['category_id'])) {
            $query->where('m.category_id', $filters['category_id']);
        }

        // if (!empty($filters['radius_id'])) {
        //     $query->where('rd.radius', $filters['radius_id']);
        // }

        if (!empty($filters['radius_id']) && $centerLat && $centerLng) {

            $radiusKm = (float) $filters['radius_id'];

            // 🔹 Bounding box (FAST)
            $latRange = $radiusKm / 111;
            $lngRange = $radiusKm / (111 * cos(deg2rad($centerLat)));

            $query->whereBetween('m.latitude', [
                $centerLat - $latRange,
                $centerLat + $latRange
            ])
                ->whereBetween('m.longitude', [
                    $centerLng - $lngRange,
                    $centerLng + $lngRange
                ]);

            // 🔹 Exact distance (Haversine)
            $query->addSelect(DB::raw("
        (6371 * acos(
            cos(radians($centerLat))
            * cos(radians(m.latitude))
            * cos(radians(m.longitude) - radians($centerLng))
            + sin(radians($centerLat))
            * sin(radians(m.latitude))
        )) AS distance
    "));

            $query->having('distance', '<=', $radiusKm)
                ->orderBy('distance', 'asc');
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
        return $query->orderBy('m.id', 'DESC')->paginate(10);
    }




    public function getMediaDetails($mediaId)
    {
        $media = DB::table('media_management as m')
            ->leftJoin('tbl_location as state', 'state.location_id', '=', 'm.state_id')
            ->leftJoin('tbl_location as district', 'district.location_id', '=', 'm.district_id')
            ->leftJoin('tbl_location as city', 'city.location_id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
            ->leftJoin('facing_direction as fd', 'fd.id', '=', 'm.facing_id')
            ->leftJoin('illumination as il', 'il.id', '=', 'm.illumination_id')
            // ->leftJoin('radius_master as rm', 'rm.id', '=', 'm.radius_id')
            ->where('m.id', $mediaId)
            ->where('m.is_deleted', 0)
            ->select([
                'm.*',
                'c.category_name',
                'state.name as state_name',
                'district.name as district_name',
                'city.name as city_name',
                'a.common_stdiciar_name as area_name',
                'fd.facing_name',
                'il.illumination_name',
                // 'rm.radius',
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

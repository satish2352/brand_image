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
            ->leftJoin('cities as city', 'city.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->leftJoin('category as ct', 'ct.id', '=', 'm.category_id')
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
                'm.width',
                'm.height',
                'm.facing',
                'm.video_link',
                'ct.category_name',
                'a.area_name',
                's.state_name as state_name',
                'd.district_name as district_name',
                'city.city_name as city_name',
                'm.area_type',
                'a.common_stdiciar_name as common_area_name',
                'mi.first_image',
                DB::raw('ROUND(m.price / DAY(LAST_DAY(CURDATE())), 2) as per_day_price')


            ]);

        /* ──────────────────────────────
         1 FIND CENTER POINT (CITY)
         ───────────────────────────────*/
        $centerLat = null;
        $centerLng = null;

        if (!empty($filters['city_id'])) {
            $center = DB::table('cities')
                ->where('id', $filters['city_id'])
                ->select('latitude', 'longitude')
                ->first();

            if ($center && $center->latitude && $center->longitude) {
                $centerLat = $center->latitude;
                $centerLng = $center->longitude;

                Log::info(" Using city lat/lng only", [
                    'city_id' => $filters['city_id'],
                    'lat'     => $centerLat,
                    'lng'     => $centerLng
                ]);
            } else {
                Log::warning(" City missing lat/lng — radius disabled", [
                    'city_id' => $filters['city_id']
                ]);
            }
        }




        /* FILTERS */
        if (!empty($filters['category_id'])) {
            $query->where('m.category_id', $filters['category_id']);
        }


        if (!empty($filters['radius_id']) && $centerLat && $centerLng) {

            $radiusKm = (float)$filters['radius_id'];

            $query->whereNotNull('m.latitude')
                ->whereNotNull('m.longitude');

            $query->addSelect(DB::raw("
        (6371 * acos(
            cos(radians($centerLat))
            * cos(radians(m.latitude))
            * cos(radians(m.longitude) - radians($centerLng))
            + sin(radians($centerLat))
            * sin(radians(m.latitude))
        )) AS distance
    "))
                ->having('distance', '<=', $radiusKm)
                ->orderBy('distance', 'asc');

            Log::info(' Radius Filter Applied', [
                'center_lat' => $centerLat,
                'center_lng' => $centerLng,
                'radius_km'  => $radiusKm
            ]);
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
        if (!empty($filters['city_id']) && empty($filters['radius_id'])) {
            $query->where('m.city_id', $filters['city_id']);
        }
        // if (!empty($filters['city_id'])) {
        //     $query->where('m.city_id', $filters['city_id']);
        // }

        if (!empty($filters['area_id'])) {
            $query->where('m.area_id', $filters['area_id']);
        }

        //     if (!empty($filters['available_days'])) {

        //         $days = (int) $filters['available_days'];
        //         $today = now()->toDateString();

        //         $query->addSelect(DB::raw("
        //     CASE
        //         WHEN NOT EXISTS (
        //             SELECT 1 FROM media_booked_date mbd
        //             WHERE mbd.media_id = m.id
        //             AND mbd.is_active = 1
        //             AND mbd.is_deleted = 0
        //         )
        //         THEN 1

        //         WHEN EXISTS (
        //             SELECT 1 FROM media_booked_date mbd
        //             WHERE mbd.media_id = m.id
        //             AND mbd.is_active = 1
        //             AND mbd.is_deleted = 0
        //             AND DATEDIFF(mbd.from_date, '{$today}') >= {$days}
        //         )
        //         THEN 1

        //         ELSE 0
        //     END AS is_available_days
        // "));
        //     }
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

            //  THIS LINE IS MISSING
            $query->having('is_available_days', 1);
        }

        /*  BOOKING STATUS LOGIC */
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
        // PRICE FILTER ONLY IF CATEGORY = 1
        //         if (!empty($filters['category_id']) && (int)$filters['category_id'] === 1) {

        //            if (isset($filters['min_price']) && $filters['min_price'] !== '') {
        //     $query->whereRaw('CAST(m.price AS UNSIGNED) >= ?', [(int)$filters['min_price']]);
        // }

        // if (isset($filters['max_price']) && $filters['max_price'] !== '') {
        //     $query->whereRaw('CAST(m.price AS UNSIGNED) <= ?', [(int)$filters['max_price']]);
        // }

        //         }
        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $query->whereRaw('CAST(m.price AS UNSIGNED) >= ?', [(int)$filters['min_price']]);
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $query->whereRaw('CAST(m.price AS UNSIGNED) <= ?', [(int)$filters['max_price']]);
        }


        //  PAGINATION (REQUIRED FOR LAZY LOADING)
        $results = $query
            ->orderBy('m.id', 'DESC')
            ->paginate(config('fileConstants.PAGINATION'));

        return $results;
    }



    public function getMediaDetails($mediaId)
    {
        $media = DB::table('media_management as m')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('cities as c', 'c.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('category as ct', 'ct.id', '=', 'm.category_id')
            ->leftJoin('illuminations as il', 'il.id', '=', 'm.illumination_id')
            ->where('m.id', $mediaId)
            ->where('m.is_deleted', 0)
            ->select([
                'm.*',
                'ct.category_name',
                's.state_name as state_name',
                'd.district_name as district_name',
                'c.city_name as city_name',
                'a.area_name as area_name',
                'a.common_stdiciar_name as common_area_name',
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

    public function getLatestOtherMediaByCategory()
    {
        return DB::table('media_management as m')
            ->leftJoin('cities as city', 'city.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->leftJoin('category as ct', 'ct.id', '=', 'm.category_id')
            ->leftJoin(DB::raw('
                (SELECT media_id, MIN(images) AS first_image
                FROM media_images
                WHERE is_deleted = 0 AND is_active = 1
                GROUP BY media_id
                ) mi
            '), 'mi.media_id', '=', 'm.id')
            ->where('m.is_deleted', 0)
            ->where('m.is_active', 1)
            ->where('m.category_id', '!=', 1) // Billboards exclude
            ->whereIn('m.id', function ($q) {
                $q->select(DB::raw('MAX(id)'))
                    ->from('media_management')
                    ->where('is_deleted', 0)
                    ->where('is_active', 1)
                    ->where('category_id', '!=', 1)
                    ->groupBy('category_id');
            })
            ->select([
                'm.id',
                'm.media_title',
                'm.price',
                'm.category_id',
                DB::raw('IFNULL(m.width, 0)  as width'),
                DB::raw('IFNULL(m.height, 0) as height'),
                DB::raw('IFNULL(m.facing, "") as facing'),
                DB::raw('IFNULL(m.video_link, "") as video_link'),
                DB::raw('IFNULL(m.area_type, "") as area_type'),
                'ct.category_name',
                'a.area_name',
                'city.city_name',
                'mi.first_image',
                'm.latitude',
                'm.longitude'
            ])
            ->orderBy('m.created_at', 'DESC')
            ->get();
    }
}

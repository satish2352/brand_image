<?php

namespace App\Http\Repository\Superadm;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use App\Models\WebsiteUser;

class HordingBookRepository
{
    public function searchMedia(array $filters)
    {
        $perPage = config('fileConstants.PAGINATION', 10);

        $query = DB::table('media_management as m')
            ->leftJoin('cities as city', 'city.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
            ->leftJoin(DB::raw('(
    SELECT *
    FROM category
    WHERE is_active = 1
      AND is_deleted = 0
    ORDER BY id LIMIT 1
) as cat2'), 'cat2.id', '=', 'm.category_id')

            // ->leftJoin(DB::raw('(SELECT * FROM category WHERE is_active = 1 AND is_deleted = 0 ORDER BY id LIMIT 1) as c'), 'c.id', '=', 'm.category_id')
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
                'city.city_name as city_name',
                'a.common_stdiciar_name as area_name',
                'mi.first_image',
                DB::raw('ROUND(m.price / DAY(LAST_DAY(CURDATE())), 2) as per_day_price'),
                DB::raw('(SELECT from_date FROM media_booked_date mbd 
              WHERE mbd.media_id = m.id 
              AND mbd.is_active = 1 
              AND mbd.is_deleted = 0 
              ORDER BY mbd.id DESC LIMIT 1) as from_date'),

                DB::raw('(SELECT to_date FROM media_booked_date mbd 
              WHERE mbd.media_id = m.id 
              AND mbd.is_active = 1 
              AND mbd.is_deleted = 0 
              ORDER BY mbd.id DESC LIMIT 1) as to_date')
            ]);
        $firstCategory = DB::table('category')
            ->where('is_active', 1)
            ->where('is_deleted', 0)
            ->orderBy('id')
            ->value('id');


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


        //     /* FILTERS */
        // if (!empty($filters['category_id'])) {
        //     $query->where('m.category_id', $filters['category_id']);
        // }
        if (!empty($filters['category_id'])) {
            $query->where('m.category_id', $filters['category_id']);
        } else {
            $query->where('m.category_id', $firstCategory);
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


        if (!empty($filters['available_days'])) {
            $query->where(
                'm.updated_at',
                '>=',
                now()->subDays((int)$filters['available_days'])
            );
        }

        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->whereBetween(
                DB::raw('DATE(m.updated_at)'),
                [$filters['from_date'], $filters['to_date']]
            );
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




        //  PAGINATION (REQUIRED FOR LAZY LOADING)
        return $query->orderBy('m.id', 'DESC')->paginate($perPage);
    }

    public function getMediaDetailsAdmin($mediaId)
    {
        $media = DB::table('media_management as m')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('cities as c', 'c.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('category as ct', 'ct.id', '=', 'm.category_id')
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

    /* ================= USER ================= */
    /* ================= USER ================= */
    public function createOrGetUser($name, $email, $mobile)
    {
        return WebsiteUser::updateOrCreate(
            ['email' => $email],   // 🔍 find by email
            [
                'name'          => $name,
                'mobile_number' => $mobile,
                'is_active'     => 1,
                'is_deleted'    => 0,
                'updated_at'    => now(),
            ]
        );
    }


    /* ================= ORDER ================= */
    public function createOrder($userId)
    {
        $orderNo = 'ORD-' . time();

        return DB::table('orders')->insertGetId([
            'user_id'        => $userId,
            'order_no'       => $orderNo,
            'total_amount'   => 0,
            'gst_amount'     => 0,
            'grand_total'    => 0,
            'payment_status' => 'ADMIN_BOOKED',
            'created_at'     => now(),
        ]);
    }


    /* ================= ORDER ITEM ================= */
    public function createOrderItem($orderId, $mediaId, $from, $to)
    {
        DB::table('order_items')->insert([
            'order_id'  => $orderId,
            'media_id'  => $mediaId,
            'from_date' => $from,
            'to_date'   => $to,
            'price'     => 0,
            'qty'     => 0,

            'created_at' => now(),
        ]);
    }


    /* ================= MEDIA BLOCK ================= */
    public function blockMediaDates($mediaId, $from, $to)
    {
        $existing = DB::table('media_booked_date')
            ->where('media_id', $mediaId)
            ->where('is_active', 1)
            ->where('is_deleted', 0)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('from_date', [$from, $to])
                    ->orWhereBetween('to_date', [$from, $to])
                    ->orWhere(function ($q2) use ($from, $to) {
                        $q2->where('from_date', '<=', $from)
                            ->where('to_date', '>=', $to);
                    });
            })
            ->first();

        if ($existing) {
            //  UPDATE ONLY to_date
            DB::table('media_booked_date')
                ->where('id', $existing->id)
                ->update([
                    'to_date'    => $to,
                    'updated_at' => now(),
                ]);
        } else {
            //  INSERT NEW
            DB::table('media_booked_date')->insert([
                'media_id'   => $mediaId,
                'from_date'  => $from,
                'to_date'    => $to,
                'is_active'  => 1,
                'is_deleted' => 0,
                'created_at' => now(),
            ]);
        }
    }

    public function bookingList()
    {
        return DB::table('orders as o')
            ->join('website_users as u', 'u.id', '=', 'o.user_id')
            ->select(
                'o.id',
                'o.order_no',
                'o.total_amount',
                'o.payment_status',
                'o.payment_id',
                'o.created_at',
                'u.name',
                'u.email',
                'u.mobile_number'
            )
            ->orderBy('o.id', 'desc')
            ->get();
    }

    public function bookingDetailsList($orderId)
    {
        $order = DB::table('orders as o')
            ->join('website_users as u', 'u.id', '=', 'o.user_id')
            ->where('o.id', $orderId)
            ->select(
                'o.*',
                'u.name',
                'u.email',
                'u.mobile_number'
            )
            ->first();

        $items = DB::table('order_items as oi')
            ->join('media_management as mm', 'mm.id', '=', 'oi.media_id')
            ->where('oi.order_id', $orderId)
            ->select(
                'oi.id',
                'oi.price',
                'oi.qty',
                'oi.from_date',
                'oi.to_date',
                'mm.media_title',
                'mm.width',
                'mm.height',
                'mm.address',
                DB::raw('(oi.price * oi.qty) as total')
            )
            ->get();

        $order->items = $items;

        return $order;
    }
}

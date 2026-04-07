<?php

namespace App\Http\Repository\Superadm;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
            ->leftJoin('areatype as at', 'at.id', '=', 'm.areatype_id')
            ->leftJoin(DB::raw('
             (SELECT media_id, MIN(images) AS first_image
              FROM media_images
              WHERE is_deleted = 0 AND is_active = 1
              GROUP BY media_id
             ) mi
         '), 'mi.media_id', '=', 'm.id')
            ->leftJoin(DB::raw('(
                SELECT mbd.media_id, mbd.from_date, mbd.to_date
                FROM media_booked_date mbd
                INNER JOIN (
                    SELECT media_id, MAX(id) as max_id
                    FROM media_booked_date
                    WHERE is_active = 1 AND is_deleted = 0
                    GROUP BY media_id
                ) lmbd ON lmbd.media_id = mbd.media_id AND lmbd.max_id = mbd.id
            ) as mbd_latest'), 'mbd_latest.media_id', '=', 'm.id')

            ->where('m.is_deleted', 0)
            ->where('m.is_active', 1)

            ->select([
                'm.id',
                'm.media_title',
                'm.price',
                'm.category_id',
                'c.category_name',
                // 'm.area_type',
                'm.width',
                'm.height',
                'm.facing',
                'at.areatype_name',
                'a.area_name as area_name',
                'city.city_name as city_name',
                'a.common_stdiciar_name as common_area_name',
                'mi.first_image',
                DB::raw('ROUND(m.price / DAY(LAST_DAY(CURDATE())), 2) as per_day_price'),
                'mbd_latest.from_date',
                'mbd_latest.to_date',
            ]);
        $firstCategory = DB::table('category')
            ->where('is_active', 1)
            ->where('is_deleted', 0)
            ->orderBy('id')
            ->value('id');


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

                Log::info("🎯 Using city lat/lng only", [
                    'city_id' => $filters['city_id'],
                    'lat'     => $centerLat,
                    'lng'     => $centerLng
                ]);
            } else {
                Log::warning("⚠ City missing lat/lng — radius disabled", [
                    'city_id' => $filters['city_id']
                ]);
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

            Log::info('🎯 Radius Filter Applied', [
                'center_lat' => $centerLat,
                'center_lng' => $centerLng,
                'radius_km'  => $radiusKm
            ]);
        }

        if (!empty($filters['areatype_id'])) {
            $query->where('m.areatype_id', $filters['areatype_id']);
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
        if (!empty($filters['area_id'])) {
            $query->where('m.area_id', $filters['area_id']);
        }

        /* SIZE FILTER */
        // if (!empty($filters['size_id'])) {

        //     // size comes like "32 x 23"
        //     $parts = explode(' x ', $filters['size_id']);

        //     if (count($parts) == 2) {

        //         $width  = (float) $parts[0];
        //         $height = (float) $parts[1];

        //         $query->where('m.width', $width)
        //             ->where('m.height', $height);
        //     }
        // }
        if (!empty($filters['min_area']) || !empty($filters['max_area'])) {

            $min = $filters['min_area'] ?? 0;
            $max = $filters['max_area'] ?? 999999999;

            $query->whereBetween('m.area_auto', [$min, $max]);
        }
        /* ================= AVAILABLE DAYS FILTER ================= */
        // if (!empty($filters['available_days'])) {
        if (isset($filters['available_days']) && $filters['available_days'] !== '') {
            $days  = (int) $filters['available_days'];
            $today = now()->toDateString();

            $query->whereRaw("
        (
            NOT EXISTS (
                SELECT 1 FROM media_booked_date mbd
                WHERE mbd.media_id = m.id
                AND mbd.is_active = 1
                AND mbd.is_deleted = 0
            )
            OR EXISTS (
                SELECT 1 FROM media_booked_date mbd
                WHERE mbd.media_id = m.id
                AND mbd.is_active = 1
                AND mbd.is_deleted = 0
                AND DATEDIFF(mbd.from_date, ?) >= ?
            )
        )
    ", [$today, $days]);
        }

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
        $results = $query->orderBy('m.id', 'DESC')->paginate($perPage);

        return [
            'data'        => $results,
            'total_count' => $results->total(),
        ];
    }
    public function getUniqueSizes()
    {
        return DB::table('media_management')
            ->where('is_deleted', 0)
            ->where('is_active', 1)
            ->whereNotNull('width')
            ->whereNotNull('height')
            ->select(
                DB::raw('MIN(id) as id'),   // key
                'width',
                'height'
            )
            ->groupBy('width', 'height')   // ⭐ remove duplicates
            ->orderBy('width')
            ->get()
            ->mapWithKeys(function ($item) {

                $size = (float)$item->width . ' x ' . (float)$item->height;

                return [
                    $item->id => $size   // key => value
                ];
            });
    }
    public function getMediaDetailsAdmin($mediaId)
    {
        $media = DB::table('media_management as m')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('cities as c', 'c.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('areatype as at', 'at.id', '=', 'm.areatype_id')
            ->leftJoin('category as ct', 'ct.id', '=', 'm.category_id')
            ->leftJoin('illuminations as il', 'il.id', '=', 'm.illumination_id')
            ->leftJoin('vendors as v', 'v.id', '=', 'm.vendor_id')
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
                'at.areatype_name as areatype_name',
                'v.vendor_name as vendor_name',
                'v.vendor_code as vendor_code',
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
            ->leftJoin('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->leftJoin('media_management as mm', 'mm.id', '=', 'oi.media_id')
            ->leftJoin('vendors as v', 'v.id', '=', 'mm.vendor_id')
            ->select(
                'o.id',
                'o.order_no',
                'o.total_amount',
                'o.payment_status',
                'o.payment_id',
                'o.created_at',
                'u.name',
                'u.email',
                'u.mobile_number',
                'o.grand_total',
                'v.vendor_name',
                'v.vendor_code',
            )
            ->orderBy('o.id', 'desc')
            ->get();
    }
    public function bookingDetailsList($orderId)
    {
        $gstPercent = 18; // GST %

        // 🔹 Order header
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

        // 🔹 Order items
        $items = DB::table('order_items as oi')
            ->join('media_management as mm', 'mm.id', '=', 'oi.media_id')
            ->leftJoin('orders as od', 'od.id', '=', 'oi.order_id')
            ->where('oi.order_id', $orderId)
            ->select(
                'oi.id',
                'oi.price as order_price',
                'oi.per_day_price',
                'oi.total_days',
                'oi.total_price',
                'oi.qty',
                'oi.from_date',
                'oi.to_date',
                'mm.media_title',
                'mm.width',
                'mm.height',
                'mm.address',
                'mm.price',
                'od.total_amount',
                'od.payment_status',
                'od.gst_amount',
                'od.grand_total'
            )
            ->get();

        // 🔹 Calculate GST & Final Amount per item
        foreach ($items as $item) {
            $item->gst_amount   = round(($item->total_price * $gstPercent) / 100, 2);
            $item->final_amount = round($item->total_price + $item->gst_amount, 2);
        }

        $order->items = $items;

        return $order;
    }
}

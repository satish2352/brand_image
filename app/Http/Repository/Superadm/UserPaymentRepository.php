<?php

namespace App\Http\Repository\Superadm;

use Illuminate\Support\Facades\DB;

class UserPaymentRepository
{
    public function list()
    {
        return DB::table('orders as o')
            ->join('website_users as u', 'u.id', '=', 'o.user_id')
            ->leftJoin('campaign as camp', 'camp.id', '=', 'o.campaign_id')
            ->leftJoin('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->leftJoin('media_management as mm', 'mm.id', '=', 'oi.media_id')
            ->leftJoin('vendors as v', 'v.id', '=', 'mm.vendor_id')
            ->select(
                'o.id',
                'o.order_no',
                'o.total_amount',
                'o.gst_amount',
                'o.grand_total',
                'o.payment_status',
                'o.payment_id',
                'o.created_at',
                'camp.campaign_name',
                'u.name',
                'u.email',
                'u.mobile_number',
                'v.vendor_name',
                'v.vendor_code',
                DB::raw('MIN(oi.from_date) as from_date'),
                DB::raw('MAX(oi.to_date) as to_date'),
                DB::raw('(SELECT GROUP_CONCAT(DISTINCT mm2.hoarding_code SEPARATOR ", ") FROM order_items oi2 JOIN media_management mm2 ON mm2.id = oi2.media_id WHERE oi2.order_id = o.id) as hoarding_codes')
            )
            ->groupBy(
                'o.id',
                'o.order_no',
                'o.total_amount',
                'o.gst_amount',
                'o.grand_total',
                'o.payment_status',
                'o.payment_id',
                'o.created_at',
                'camp.campaign_name',
                'u.name',
                'u.email',
                'u.mobile_number',
                'v.vendor_name',
                'v.vendor_code'
            )
            ->orderBy('o.id', 'desc')
            ->get();
    }

    public function getOrderDetails($orderId)
    {
        $gstPercent = 18; // GST %
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
            ->leftJoin('orders as od', 'od.id', '=', 'oi.order_id')
            ->leftJoin('highway as hw', 'hw.id', '=', 'mm.highway_id')
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
                'mm.hoarding_code',
                'hw.highway_name',
                DB::raw('(SELECT GROUP_CONCAT(l.landmark_name SEPARATOR ", ") FROM media_landmark ml JOIN landmark l ON l.id = ml.landmark_id WHERE ml.media_id = mm.id AND l.is_deleted = 0) as landmark_names'),
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

<?php

namespace App\Http\Repository\Website;

use Illuminate\Support\Facades\DB;

class PaymentRepository
{

    public function getPaidCampaignInvoices($userId)
    {
        return DB::table('orders as o')
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->leftJoin('campaign as c', 'c.id', '=', 'o.campaign_id')
            ->join('media_management as m', 'm.id', '=', 'oi.media_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')

            ->select(
                'o.id as order_id',
                'o.order_no',
                'o.payment_status',
                'o.created_at',
                'o.grand_total',


                //  combine multiple media locations into one string
                DB::raw('GROUP_CONCAT(DISTINCT CONCAT(a.area_name, ", ", m.facing) SEPARATOR " | ") as location'),

                //  campaign optional
                DB::raw('GROUP_CONCAT(DISTINCT c.campaign_name) as campaign_name')
            )
            ->where('o.user_id', $userId)
            ->whereIn('o.payment_status', ['PAID', 'ADMIN_BOOKED'])

            //  GROUP BY ONLY ORDER FIELDS
            ->groupBy(
                'o.id',
                'o.order_no',
                'o.payment_status',
                'o.created_at',
                'o.grand_total'
            )
            ->orderBy('o.id', 'DESC')
            ->get();
    }

    public function getInvoiceDetails($orderId)
    {
        return DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')

            ->leftJoin('cart_items as ci', function ($join) {
                $join->on('ci.media_id', '=', 'oi.media_id')
                    ->where('ci.cart_type', 'CAMPAIGN');
            })

            ->leftJoin('campaign as c', 'c.id', '=', 'ci.campaign_id')
            ->leftJoin('media_management as m', 'm.id', '=', 'oi.media_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')

            ->leftJoin('media_images as mi', function ($join) {
                $join->on('mi.media_id', '=', 'm.id')
                    ->where('mi.is_deleted', 0);
            })

            ->select(
                'o.id as order_id',
                'o.order_no',
                'o.total_amount',
                'o.gst_amount',
                'o.grand_total',
                'a.area_name',
                // Campaign
                DB::raw('GROUP_CONCAT(DISTINCT c.campaign_name) as campaign_name'),

                // Media
                'm.media_title',
                'm.width',
                'm.height',
                'm.facing',
                'oi.price',
                'oi.qty',
                'oi.from_date',
                'oi.to_date',
                'oi.total_days',

                // Location
                'a.area_name',

                DB::raw('GROUP_CONCAT(mi.images) as images')
            )
            ->where('oi.order_id', $orderId)
            ->groupBy(
                'o.id',
                'o.order_no',
                'o.total_amount',
                'o.gst_amount',
                'o.grand_total',
                'm.media_title',
                'm.width',
                'm.height',
                'm.facing',
                'oi.price',
                'oi.qty',
                'oi.from_date',
                'oi.to_date',
                'a.area_name',
                'oi.total_days',
            )
            ->get();
    }
}

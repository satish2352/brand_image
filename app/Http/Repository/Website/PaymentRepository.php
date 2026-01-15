<?php

namespace App\Http\Repository\Website;

use Illuminate\Support\Facades\DB;

class PaymentRepository
{
    public function getPaidCampaignInvoices($userId)
    {
        return DB::table('orders as o')
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->join('media_management as m', 'm.id', '=', 'oi.media_id')
            ->leftJoin('cart_items as ci', function ($join) {
                $join->on('ci.media_id', '=', 'm.id')
                    ->where('ci.cart_type', 'CAMPAIGN');
            })
            ->leftJoin('campaign as c', 'c.id', '=', 'ci.campaign_id')
            ->leftJoin('category as cat', 'cat.id', '=', 'm.category_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->select(
                'o.id as order_id',
                'o.order_no',
                'o.total_amount',
                'o.payment_status',
                'o.payment_id',
                'o.created_at',

                // Campaign
                DB::raw('GROUP_CONCAT(DISTINCT c.campaign_name) as campaign_name'),

                // Media info
                DB::raw('COUNT(oi.id) as total_items'),
                DB::raw('GROUP_CONCAT(DISTINCT m.media_title) as media_titles'),

                // Category & Area
                DB::raw('GROUP_CONCAT(DISTINCT cat.category_name) as category_name'),
                DB::raw('GROUP_CONCAT(DISTINCT a.common_stdiciar_name) as common_stdiciar_name')
            )
            ->where('o.user_id', $userId)
            // ->where('o.payment_status', 'PAID')
            ->whereIn('o.payment_status', ['PAID', 'ADMIN_BOOKED'])
            ->groupBy(
                'o.id',
                'o.order_no',
                'o.total_amount',
                'o.payment_status',
                'o.payment_id',
                'o.created_at'
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

                // Campaign
                DB::raw('GROUP_CONCAT(DISTINCT c.campaign_name) as campaign_name'),

                // Media
                'm.media_title',
                'm.width',
                'm.height',
                'oi.price',
                'oi.qty',
                'oi.from_date',
                'oi.to_date',

                // Location
                'a.common_stdiciar_name',

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
                'oi.price',
                'oi.qty',
                'oi.from_date',
                'oi.to_date',
                'a.common_stdiciar_name'
            )
            ->get();
    }
}

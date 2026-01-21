<?php

namespace App\Http\Repository\Superadm;

use Illuminate\Support\Facades\DB;

class UserPaymentRepository
{
    public function list()
    {
        return DB::table('orders as o')
            ->join('website_users as u', 'u.id', '=', 'o.user_id')
            ->leftJoin('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->select(
                'o.id',
                'o.order_no',
                'o.total_amount',
                'o.gst_amount',
                'o.grand_total',
                'o.payment_status',
                'o.payment_id',
                'o.created_at',
                'u.name',
                'u.email',
                'u.mobile_number',

                DB::raw('MIN(oi.from_date) as from_date'),
                DB::raw('MAX(oi.to_date) as to_date')
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
                'u.name',
                'u.email',
                'u.mobile_number'
            )
            ->orderBy('o.id', 'desc')
            ->get();
    }

    public function getOrderDetails($orderId)
    {
        $order = DB::table('orders as o')
            ->join('website_users as u', 'u.id', '=', 'o.user_id')
            ->where('o.id', $orderId)
            ->select(
                'o.*',
                'u.name',
                'u.email',
                'u.mobile_number',
                'o.total_amount',
                'o.gst_amount',
                'o.grand_total',
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

                DB::raw('(oi.price * oi.qty) as item_total'),
                DB::raw('(oi.price * oi.qty * 0.18) as gst_amount'),
                DB::raw('((oi.price * oi.qty) + (oi.price * oi.qty * 0.18)) as final_total')
            )
            ->get();


        $order->items = $items;

        return $order;
    }
}

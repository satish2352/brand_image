<?php

namespace App\Http\Repository\Superadm;

use Illuminate\Support\Facades\DB;

class UserPaymentRepository
{
    public function list()
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

    public function getOrderDetails($orderId)
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

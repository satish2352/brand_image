<?php
// app/Http/Repository/Website/OrderRepository.php

namespace App\Http\Repository\Website;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;

use Illuminate\Support\Facades\Auth;

class OrderRepository
{

    public function createOrder($subTotal, $campaignId = null)
    {
        $gstAmount   = round(($subTotal * 18) / 100, 2);
        $grandTotal  = $subTotal + $gstAmount;

        return Order::create([
            'user_id'       => Auth::guard('website')->id(),
            'order_no'      => 'ORD-' . time(),
            'total_amount'  => $subTotal,
            'gst_amount'    => $gstAmount,
            'grand_total'   => $grandTotal,
            'payment_status' => 'PENDING',
            'campaign_id'   => $campaignId 
        ]);
    }
public function createOrderItems($orderId, $items)
{
    foreach ($items as $item) {
        OrderItem::create([
            'order_id'       => $orderId,
            'media_id'       => $item->media_id,

            'from_date'      => $item->from_date,
            'to_date'        => $item->to_date,

            'price'          => $item->price,          // monthly price
            'per_day_price'  => $item->per_day_price,  // from cart_items
            'total_days'     => $item->total_days,     // from cart_items
            'total_price'    => $item->total_price,    // from cart_items

            'qty'            => $item->qty,
        ]);
    }
}

    // public function createOrderItems($orderId, $items)
    // {
    //     foreach ($items as $item) {
    //         OrderItem::create([
    //             'order_id' => $orderId,
    //             'media_id' => $item->media_id,
    //             'from_date' => $item->from_date,
    //             'to_date' => $item->to_date,
    //             // 'price'    => $item->price,
    //             'price' => $item->total_price,
    //             'qty'      => $item->qty,
    //         ]);
    //     }
    // }

    public function findById($id)
    {
        if (!$id) {
            return null;
        }

        return Order::find($id);
    }

    public function markAsPaid($orderId, $paymentId)
    {
        Order::where('id', $orderId)->update([
            'payment_status' => 'PAID',
            'payment_id'     => $paymentId,
        ]);
    }
}

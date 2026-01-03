<?php
// app/Http/Repository/Website/OrderRepository.php

namespace App\Http\Repository\Website;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;

use Illuminate\Support\Facades\Auth;

class OrderRepository
{

    public function createOrder($total)
    {
        return Order::create([
            // 'user_id'        => Auth::id(), // ✅ FIX
            'user_id' => Auth::guard('website')->id(),
            'order_no'       => 'ORD-' . time(),
            'total_amount'   => $total,
            'payment_status' => 'PENDING',
        ]);
    }

    public function createOrderItems($orderId, $items)
    {

        // dd($items);
        // die();
        foreach ($items as $item) {
            OrderItem::create([
                'order_id' => $orderId,
                'media_id' => $item->media_id,
                'from_date' => $item->from_date,
                'to_date' => $item->to_date,
                // 'price'    => $item->price,
                'price' => $item->total_price,
                'qty'      => $item->qty,
            ]);
        }
    }

    public function findById($id)
    {
        return Order::findOrFail($id);
    }

    public function markAsPaid($orderId, $paymentId)
    {
        Order::where('id', $orderId)->update([
            'payment_status' => 'PAID',
            'payment_id'     => $paymentId,
        ]);
    }
}

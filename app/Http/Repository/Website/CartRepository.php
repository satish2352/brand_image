<?php

namespace App\Http\Repository\Website;

use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;

class CartRepository
{
    private function ownerCondition($query)
    {
        if (Auth::guard('website')->check()) {
            $query->where('user_id', Auth::guard('website')->id());
        } else {
            $query->whereNull('user_id')
                ->where('session_id', session()->getId());
        }
    }

    // public function getCartItems()
    // {
    //     $query = CartItem::query();
    //     $this->ownerCondition($query);

    //     return $query
    //         ->join('media_management as m', 'm.id', '=', 'cart_items.media_id')
    //         ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
    //         ->select(
    //             'cart_items.id',
    //             'cart_items.media_id',
    //             'cart_items.price',
    //             'cart_items.qty',
    //             'm.media_title',
    //             'c.category_name'
    //         )
    //         ->where('cart_items.status', 'ACTIVE')
    //         ->whereIn('cart_items.cart_type', ['NORMAL', 'CAMPAIGN'])
    //         ->get();
    // }
    public function getCartItems()
    {
        $query = CartItem::query();
        $this->ownerCondition($query);

        return $query
            ->join('media_management as m', 'm.id', '=', 'cart_items.media_id')
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
            ->select(
                'cart_items.id',
                'cart_items.media_id',
                'cart_items.price',
                'cart_items.qty',
                'm.media_title',
                'c.category_name'
            )
            ->where('cart_items.status', 'ACTIVE')
            ->where('cart_items.cart_type', 'NORMAL') // ✅ ONLY NORMAL
            ->orderBy('cart_items.id', 'DESC')
            ->get();
    }

    // public function addItem($mediaId, $price)
    // {
    //     $query = CartItem::where('media_id', $mediaId);
    //     $this->ownerCondition($query);

    //     $item = $query->first();

    //     if ($item) {
    //         $item->increment('qty');
    //         return;
    //     }

    //     CartItem::create([
    //         'user_id' => Auth::guard('website')->check()
    //             ? Auth::guard('website')->id()
    //             : null,
    //         'session_id' => session()->getId(),
    //         'media_id' => $mediaId,
    //         'price' => $price,
    //         'qty' => 1,
    //     ]);
    // }
    public function addItem($mediaId, $price)
    {
        CartItem::create([
            'user_id' => Auth::guard('website')->check()
                ? Auth::guard('website')->id()
                : null,
            'session_id' => session()->getId(),
            'media_id' => $mediaId,
            'price' => $price,
            'qty' => 1,
            'cart_type' => 'NORMAL',   // IMPORTANT
            'status' => 'ACTIVE',
            'is_active' => 1,
            'is_deleted' => 0,
        ]);
    }

    // public function addItem($mediaId, $price)
    // {
    //     CartItem::create([
    //         'user_id' => Auth::guard('website')->check()
    //             ? Auth::guard('website')->id()
    //             : null,
    //         'session_id' => session()->getId(),
    //         'media_id' => $mediaId,
    //         'price' => $price,
    //         'qty' => 1,
    //         'cart_type' => 'NORMAL',   // always NORMAL for cart
    //         'status' => 'ACTIVE',
    //         'is_active' => 1,
    //         'is_deleted' => 0,
    //     ]);
    // }
    public function updateQty($itemId, $qty)
    {
        CartItem::where('id', $itemId)->update([
            'qty' => max(1, $qty)
        ]);
    }


    public function removeItem($itemId)
    {
        $query = CartItem::where('id', $itemId);
        $this->ownerCondition($query);
        $query->delete();
    }

    public function clearCart()
    {
        $query = CartItem::query();
        $this->ownerCondition($query);
        $query->delete();
    }

    public function softDeleteCartAfterOrder($userId)
    {
        CartItem::where('user_id', $userId)
            ->where('status', 'ACTIVE')
            ->where('cart_type', 'NORMAL')
            ->update([
                'is_deleted' => 1,
                'is_active'  => 0,
                'status'     => 'ORDERED', // ✅ VALID ENUM
            ]);
    }
}

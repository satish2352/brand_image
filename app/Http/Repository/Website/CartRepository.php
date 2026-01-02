<?php

namespace App\Http\Repository\Website;

use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        $items = $query
            ->join('media_management as m', 'm.id', '=', 'cart_items.media_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
            ->select(
                'cart_items.id',
                'cart_items.media_id',
                'cart_items.price',
                'cart_items.per_day_price',
                'cart_items.total_price',
                'cart_items.total_days',
                'cart_items.qty',
                'cart_items.from_date',
                'cart_items.to_date',
                'm.media_title',
                'a.area_name',
                'c.category_name'
            )
            // ->select(
            //     'cart_items.id',
            //     'cart_items.media_id',
            //     'cart_items.price',
            //     'cart_items.qty',
            //     'cart_items.from_date',
            //     'cart_items.to_date',
            //     'm.media_title',
            //     'a.area_name',
            //     'c.category_name'
            // )
            ->where('cart_items.status', 'ACTIVE')
            ->where('cart_items.cart_type', 'NORMAL')
            ->orderBy('cart_items.id', 'DESC')
            ->get();

        // 🔥 Fetch multiple images
        $mediaIds = $items->pluck('media_id')->unique();

        $images = DB::table('media_images')
            ->whereIn('media_id', $mediaIds)
            // ->where('is_deleted', 0)
            // ->where('is_active', 1)
            ->orderBy('id')
            ->get()
            ->groupBy('media_id');

        // Attach images
        foreach ($items as $item) {
            $item->images = $images[$item->media_id] ?? collect();
        }

        return $items;
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

    // public function addItemWithDate($mediaId, $price, $from, $to)
    // {
    //     CartItem::create([
    //         'user_id'    => Auth::guard('website')->id(),
    //         'session_id' => session()->getId(),
    //         'media_id'   => $mediaId,
    //         'price'      => $price,
    //         'qty'        => 1,
    //         'from_date'  => $from,
    //         'to_date'    => $to,
    //         'cart_type'  => 'NORMAL',
    //         'status'     => 'HOLD', // 🔥 TEMP HOLD
    //         'is_active'  => 1,
    //         'is_deleted' => 0,
    //     ]);
    // }
    public function addItemWithDate(
        $mediaId,
        $price,
        $from,
        $to,
        $perDayPrice,
        $totalPrice,
        $totalDays
    ) {
        CartItem::create([
            'user_id'       => Auth::guard('website')->id(),
            'session_id'    => session()->getId(),
            'media_id'      => $mediaId,
            'price'         => $price,
            'from_date'     => $from,
            'to_date'       => $to,
            'per_day_price' => $perDayPrice,
            'total_price'   => $totalPrice,
            'total_days'    => $totalDays,
            'qty'           => 1,
            'cart_type'     => 'NORMAL',
            'status'        => 'HOLD',
            'is_active'     => 1,
            'is_deleted'    => 0,
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

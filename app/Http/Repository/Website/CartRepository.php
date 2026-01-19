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
                'm.facing',
                'a.area_name',
                'a.common_stdiciar_name',
                'c.category_name'
            )

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


    public function addItem($mediaId, $price)
    {
        CartItem::create([
            'user_id' => Auth::guard('website')->check()
                ? Auth::guard('website')->id()
                : null,
            'session_id' => session()->getId(),
            'media_id' => $mediaId,
            'price' => $price,

            'per_day_price' => 0,
            'total_price'   => 0,
            'total_days'    => 0,

            'qty' => 1,
            'cart_type' => 'NORMAL',   // IMPORTANT
            'status' => 'ACTIVE',
            'is_active' => 1,
            'is_deleted' => 0,
        ]);
    }
    // =================
    public function getBookedDatesByMedia($mediaId)
    {
        return DB::table('order_items')
            ->where('media_id', $mediaId)
            ->select('from_date', 'to_date')
            ->get();
    }

    // 🔹 Get single cart item
    public function getCartItemById($cartItemId)
    {
        return CartItem::where('id', $cartItemId)->first();
    }

    // 🔒 Check already booked dates
    public function isDateAlreadyBooked($mediaId, $from, $to)
    {
        return DB::table('order_items')
            ->where('media_id', $mediaId)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('from_date', [$from, $to])
                    ->orWhereBetween('to_date', [$from, $to])
                    ->orWhere(function ($q2) use ($from, $to) {
                        $q2->where('from_date', '<=', $from)
                            ->where('to_date', '>=', $to);
                    });
            })
            ->exists();
    }

    // 🔄 Update cart dates + price
    public function updateCartDates(
        $cartItemId,
        $from,
        $to,
        $perDayPrice,
        $totalPrice,
        $totalDays
    ) {
        CartItem::where('id', $cartItemId)->update([
            'from_date'     => $from,
            'to_date'       => $to,
            'per_day_price' => $perDayPrice,
            'total_price'   => $totalPrice,
            'total_days'    => $totalDays,
        ]);
    }
    // =============

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
                'status'     => 'ORDERED', //  VALID ENUM
            ]);
    }
}

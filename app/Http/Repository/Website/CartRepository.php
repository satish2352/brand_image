<?php

namespace App\Http\Repository\Website;

use Illuminate\Support\Facades\Auth;
use App\Models\Cart;
use App\Models\CartItem;

class CartRepository
{
    /**
     * Get cart for logged-in user OR session
     */
    // public function getOrCreateCart()
    // {
    //     if (Auth::check()) {
    //         return Cart::firstOrCreate([
    //             'user_id' => Auth::id(),
    //         ]);
    //     }

    //     return Cart::firstOrCreate([
    //         'session_id' => session()->getId(),
    //     ]);
    // }
    public function getOrCreateCart()
    {
        // ✅ WEBSITE AUTH HERE
        if (Auth::guard('website')->check()) {

            return Cart::firstOrCreate(
                [
                    'user_id' => Auth::guard('website')->id(),
                ],
                [
                    'session_id' => session()->getId(),
                ]
            );
        }

        return Cart::firstOrCreate([
            'session_id' => session()->getId(),
        ]);
    }
    /**
     * Get all cart items with media details
     */
    public function getCartItems($cartId)
    {
        return CartItem::where('cart_items.cart_id', $cartId)
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
            ->get();
    }

    /**
     * Add item to cart
     */
    public function addItem($cartId, $mediaId, $price)
    {
        $item = CartItem::where('cart_id', $cartId)
            ->where('media_id', $mediaId)
            ->first();

        if ($item) {
            $item->increment('qty');
            return;
        }

        CartItem::create([
            'cart_id' => $cartId,
            'media_id' => $mediaId,
            'price' => $price,
            'qty' => 1,
        ]);
    }

    /**
     * Update quantity (min 1)
     */
    public function updateQty($itemId, $qty, $cartId)
    {
        CartItem::where('id', $itemId)
            ->where('cart_id', $cartId)
            ->update([
                'qty' => max(1, $qty),
            ]);
    }

    public function removeItem($itemId, $cartId)
    {
        CartItem::where('id', $itemId)
            ->where('cart_id', $cartId)
            ->delete();
    }

    public function clearCart($cartId)
    {
        CartItem::where('cart_id', $cartId)->delete();
        Cart::where('id', $cartId)->delete();
    }
}

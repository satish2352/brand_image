<?php

namespace App\Http\Services\Website;

use App\Http\Repository\Website\CartRepository;
use Illuminate\Support\Facades\DB;

class CartService
{
    protected $repo;

    public function __construct(CartRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getCart()
    {
        $cart = $this->repo->getOrCreateCart();
        $items = $this->repo->getCartItems($cart->id);

        return [$cart, $items];
    }

    public function addToCart($mediaId)
    {
        $cart = $this->repo->getOrCreateCart();

        $media = DB::table('media_management')
            ->select('id', 'price')
            ->where('id', $mediaId)
            ->first();

        $this->repo->addItem($cart->id, $media->id, $media->price);
    }

    /**
     * ✅ FIXED: pass cart_id
     */
    public function updateQty($itemId, $qty)
    {
        $cart = $this->repo->getOrCreateCart();
        $this->repo->updateQty($itemId, $qty, $cart->id);
    }

    /**
     * ✅ FIXED: pass cart_id
     */
    public function removeItem($itemId)
    {
        $cart = $this->repo->getOrCreateCart();
        $this->repo->removeItem($itemId, $cart->id);
    }
}

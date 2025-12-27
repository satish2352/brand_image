<?php

namespace App\Http\Services\Website;

use App\Http\Repository\Website\CartRepository;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(private CartRepository $repo) {}

    public function getCartItems()
    {
        return $this->repo->getCartItems();
    }

    public function addToCart($mediaId)
    {
        $media = DB::table('media_management')
            ->where('id', $mediaId)
            ->select('id', 'price')
            ->first();

        if (!$media) {
            throw new \Exception('Media not found');
        }

        $this->repo->addItem($media->id, $media->price);
    }

    public function updateQty($itemId, $qty)
    {
        $this->repo->updateQty($itemId, $qty);
    }

    public function removeItem($itemId)
    {
        $this->repo->removeItem($itemId);
    }

    public function clearCart()
    {
        $this->repo->clearCart();
    }
}

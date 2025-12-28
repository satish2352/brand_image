<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Services\Website\CartService;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected $service;

    public function __construct(CartService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $items = $this->service->getCartItems();
        return view('website.cart', compact('items'));
    }

    // public function add($mediaId)
    // {
    //     if (!Auth::guard('website')->check()) {
    //         return redirect()->route('website.home')
    //             ->with('error', 'Please login to add items to cart');
    //     }

    //     $this->service->addToCart(decrypt($mediaId));

    //     return redirect()
    //         ->route('cart.index')
    //         ->with('success', 'Item added to cart');
    // }
    public function add($mediaId)
    {
        $mediaId = base64_decode($mediaId);
        $this->service->addToCart($mediaId);

        return redirect()
            ->route('cart.index')
            ->with('success', 'Item added to cart');
    }

    public function update(Request $request)
    {
        $this->service->updateQty($request->item_id, $request->qty);
        return back();
    }

    // public function remove($itemId)
    // {
    //     $this->service->removeItem(decrypt($itemId));
    //     return back()->with('success', 'Item removed');
    // }
    public function remove($itemId)
    {
        $itemId = base64_decode($itemId);
        $this->service->removeItem($itemId);

        return back()->with('success', 'Item removed');
    }
}

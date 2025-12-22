<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MediaManagement;
use Illuminate\Support\Facades\DB;
use App\Http\Services\Website\CartService;

class CartController extends Controller
{
    protected $service;

    public function __construct(CartService $service)
    {
        $this->service = $service;
    }
    public function index()
    {
        [$cart, $items] = $this->service->getCart();
        return view('website.cart', compact('cart', 'items'));
    }

    public function add($mediaId)
    {
        $this->service->addToCart(decrypt($mediaId));
        return redirect()->route('cart.index')->with('success', 'Item added to cart');
    }

    public function update(Request $request)
    {
        $this->service->updateQty($request->item_id, $request->qty);
        return back();
    }

    public function remove($itemId)
    {
        $this->service->removeItem(decrypt($itemId));
        return back()->with('success', 'Item removed');
    }
}

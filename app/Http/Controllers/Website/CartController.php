<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Services\Website\CartService;
use Illuminate\Support\Facades\DB;

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

    public function add($mediaId)
    {
        $mediaId = base64_decode($mediaId);

        try {
            $this->service->addToCart($mediaId);

            return redirect()
                ->route('cart.index')
                ->with('success', 'Media added to cart successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->route('cart.index')
                ->with('info', $e->getMessage());
        }
    }

    public function addWithDate(Request $request)
    {
        $request->validate([
            'media_id'  => 'required',
            'from_date' => 'required|date',
            'to_date'   => 'required|date|after_or_equal:from_date',
        ]);

        $mediaId = base64_decode($request->media_id);

        try {
            $this->service->addToCartWithDate(
                $mediaId,
                $request->from_date,
                $request->to_date,
                'NORMAL'   // ✅ ADD THIS
            );

            return redirect()
                ->route('cart.index')
                ->with('success', 'Media added to cart successfully');
        } catch (\Exception $e) {
            return redirect()
                ->route('cart.index')
                ->with('error', $e->getMessage());
        }
    }
    // public function getBookedDates($mediaId)
    // {
    //     return response()->json(
    //         $this->service->getBookedDatesByMedia($mediaId)
    //     );
    // }
    public function getBookedDates($mediaId)
    {
        try {
            $bookings = $this->service->getBookedDatesByMedia($mediaId);
            return response()->json($bookings ?? []);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }


    public function updateDates(Request $request)
    {
        try {
            $request->validate([
                'cart_item_id' => 'required|exists:cart_items,id',
                'from_date'    => 'required|date',
                'to_date'      => 'required|date|after_or_equal:from_date',
            ]);


            $this->service->updateCartDates(
                $request->cart_item_id,
                $request->from_date,
                $request->to_date
            );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }


    public function update(Request $request)
    {
        $this->service->updateQty($request->item_id, $request->qty);
        return back();
    }

    public function remove($itemId)
    {
        $itemId = base64_decode($itemId);
        $this->service->removeItem($itemId);

        return back()->with('success', 'Item removed');
    }
}

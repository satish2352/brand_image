<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Services\Website\CartService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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
        $this->service->addToCart($mediaId);

        return redirect()
            ->route('cart.index')
            ->with('success', 'Item added to cart');
    }

    // public function add()
    // {
    //     return redirect('/')
    //         ->with('error', 'Please select dates before adding to cart');
    // }
    // public function addWithDate(Request $request)
    // {
    //     try {

    //         // ✅ Basic validation (NO today comparison here)
    //         $request->validate([
    //             'media_id'  => 'required',
    //             'from_date' => 'required|date',
    //             'to_date'   => 'required|date|after_or_equal:from_date',
    //         ]);

    //         // ✅ Decode media ID
    //         $mediaId = base64_decode($request->media_id);

    //         if (!$mediaId) {
    //             return redirect()
    //                 ->back()
    //                 ->with('error', 'Invalid media selected');
    //         }

    //         // ✅ Timezone-safe date validation
    //         $fromDate = Carbon::parse($request->from_date);
    //         $today    = Carbon::today();

    //         if ($fromDate->lt($today)) {
    //             return redirect()
    //                 ->back()
    //                 ->with('error', 'From date must be today or a future date');
    //         }

    //         // ✅ Add to cart
    //         $this->service->addToCartWithDate(
    //             $mediaId,
    //             $request->from_date,
    //             $request->to_date
    //         );

    //         // ✅ IMPORTANT: Redirect instead of JSON
    //         return redirect()
    //             ->route('cart.index')
    //             ->with('success', 'Media added to cart successfully');
    //     } catch (\Throwable $e) {

    //         return redirect()
    //             ->back()
    //             ->with('error', $e->getMessage());
    //     }
    // }
    public function addWithDate(Request $request)
    {
        $request->validate([
            'media_id'  => 'required',
            'from_date' => 'required|date|after_or_equal:today',
            'to_date'   => 'required|date|after_or_equal:from_date',
        ]);

        $mediaId = base64_decode($request->media_id);

        $this->service->addToCartWithDate(
            $mediaId,
            $request->from_date,
            $request->to_date
        );

        return redirect()
            ->route('cart.index')
            ->with('success', 'Media added to cart successfully');
    }

    // public function addWithDate(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'media_id'  => 'required',
    //             'from_date' => 'required|date|after_or_equal:today',
    //             'to_date'   => 'required|date|after_or_equal:from_date',
    //         ]);

    //         $mediaId = base64_decode($request->media_id);

    //         $this->service->addToCartWithDate(
    //             $mediaId,
    //             $request->from_date,
    //             $request->to_date
    //         );

    //         return response()->json(['success' => true]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage()
    //         ], 422);
    //     }
    // }

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

<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Repository\Website\CartRepository;
use App\Http\Repository\Website\OrderRepository;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use App\Models\CartItem;

class CheckoutController extends Controller
{
    public function __construct(
        private CartRepository $cartRepo,
        private OrderRepository $orderRepo
    ) {}

    // public function index()
    // {
    //     $items = $this->cartRepo->getCartItems();

    //     if ($items->isEmpty()) {
    //         return redirect('/')->with('error', 'Cart is empty');
    //     }

    //     $total = $items->sum(fn($i) => $i->price * $i->qty);

    //     return view('website.checkout', compact('items', 'total'));
    // }
    public function index()
    {
        $orderId = session('order_id');

        if (!$orderId) {
            return redirect('/')->with('error', 'Order not found');
        }

        $order = $this->orderRepo->findById($orderId);

        $items = \App\Models\OrderItem::where('order_id', $orderId)
            ->join('media_management as m', 'm.id', '=', 'order_items.media_id')
            ->select(
                'order_items.price',
                'order_items.qty',
                'm.media_title'
            )
            ->get();

        return view('website.checkout', [
            'items' => $items,
            'total' => $order->total_amount
        ]);
    }

    // public function placeOrder()
    // {
    //     $items = $this->cartRepo->getCartItems();

    //     if ($items->isEmpty()) {
    //         return response()->json(['error' => 'Cart is empty'], 400);
    //     }

    //     $total = $items->sum(fn($i) => $i->price * $i->qty);

    //     $order = $this->orderRepo->createOrder($total);
    //     $this->orderRepo->createOrderItems($order->id, $items);

    //     session(['order_id' => $order->id]);

    //     return response()->json(['success' => true]);
    // }
    public function placeOrder()
    {
        $items = $this->cartRepo->getCartItems();

        if ($items->isEmpty()) {
            return redirect()->back()->with('error', 'Cart is empty');
        }

        $total = $items->sum(fn($i) => $i->price * $i->qty);

        $order = $this->orderRepo->createOrder($total);
        $this->orderRepo->createOrderItems($order->id, $items);

        session(['order_id' => $order->id]);

        return redirect()->route('checkout.index');
    }

    public function pay()
    {
        $orderId = session('order_id');
        $order = $this->orderRepo->findById($orderId);

        $api = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );

        $razorpayOrder = $api->order->create([
            'receipt' => $order->order_no,
            'amount' => $order->total_amount * 100,
            'currency' => 'INR',
        ]);

        session(['razorpay_order_id' => $razorpayOrder['id']]);

        return response()->json([
            'order_id' => $razorpayOrder['id'],
            'amount' => $order->total_amount * 100,
            'key' => config('services.razorpay.key'),
        ]);
    }

    // public function success(Request $request)
    // {
    //     $api = new Api(
    //         config('services.razorpay.key'),
    //         config('services.razorpay.secret')
    //     );

    //     $api->utility->verifyPaymentSignature([
    //         'razorpay_order_id' => session('razorpay_order_id'),
    //         'razorpay_payment_id' => $request->razorpay_payment_id,
    //         'razorpay_signature' => $request->razorpay_signature,
    //     ]);

    //     $orderId = session('order_id');

    //     \App\Models\Order::where('id', $orderId)->update([
    //         'payment_status' => 'PAID',
    //         'payment_id' => $request->razorpay_payment_id,
    //     ]);

    //     // $this->cartRepo->clearCart();

    //     $this->cartRepo->softDeleteCartAfterOrder(
    //         auth()->guard('website')->id()
    //     );

    //     session()->forget(['order_id', 'razorpay_order_id']);

    //     return view('website.payment-success');
    // }
    public function success(Request $request)
    {
        $api = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );

        $api->utility->verifyPaymentSignature([
            'razorpay_order_id' => session('razorpay_order_id'),
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'razorpay_signature' => $request->razorpay_signature,
        ]);

        $orderId    = session('order_id');
        $campaignId = session('campaign_id');
        $userId     = auth()->guard('website')->id();

        // ✅ Mark order paid
        \App\Models\Order::where('id', $orderId)->update([
            'payment_status' => 'PAID',
            'payment_id'     => $request->razorpay_payment_id,
        ]);

        // ✅ 1️⃣ Clear NORMAL cart items (VERY IMPORTANT)
        \App\Models\CartItem::where('user_id', $userId)
            ->where('cart_type', 'NORMAL')
            ->where('status', 'ACTIVE')
            ->update([
                'status'     => 'ORDERED',
                'is_active'  => 0,
                'is_deleted' => 1,
            ]);

        // ✅ 2️⃣ Clear CAMPAIGN items if campaign order
        if ($campaignId) {
            \App\Models\CartItem::where('campaign_id', $campaignId)
                ->where('cart_type', 'CAMPAIGN')
                ->update([
                    'status'     => 'ORDERED',
                    'is_active'  => 0,
                    'is_deleted' => 1,
                ]);
        }

        session()->forget([
            'order_id',
            'campaign_id',
            'razorpay_order_id'
        ]);

        return view('website.payment-success');
    }


    public function placeCampaignOrder($campaignId)
    {
        $campaignId = base64_decode($campaignId);

        $items = CartItem::where('campaign_id', $campaignId)
            ->where('cart_type', 'CAMPAIGN')
            ->where('status', 'ACTIVE')
            ->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'Campaign is empty');
        }

        $total = $items->sum(fn($i) => $i->price * $i->qty);

        $order = $this->orderRepo->createOrder($total);
        $this->orderRepo->createOrderItems($order->id, $items);

        // ONLY STORE ORDER ID
        session([
            'order_id'    => $order->id,
            'campaign_id' => $campaignId,
        ]);

        return redirect()->route('checkout.index');
    }
}

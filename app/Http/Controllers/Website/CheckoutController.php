<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Repository\Website\CartRepository;
use App\Http\Repository\Website\OrderRepository;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Cart;
use App\Models\CartItem;

class CheckoutController extends Controller
{
    protected $cartRepo;
    protected $orderRepo;

    public function __construct(
        CartRepository $cartRepo,
        OrderRepository $orderRepo
    ) {
        $this->cartRepo  = $cartRepo;
        $this->orderRepo = $orderRepo;
    }

    // STEP 1: Checkout + Create Order
    // public function index()
    // {
    //     $cart  = $this->cartRepo->getOrCreateCart();
    //     $items = $this->cartRepo->getCartItems($cart->id);

    //     if ($items->count() === 0) {
    //         return redirect('/')->with('error', 'Cart is empty');
    //     }

    //     $total = $items->sum(fn($i) => $i->price * $i->qty);

    //     $order = $this->orderRepo->createOrder(Auth::id(), $total);
    //     $this->orderRepo->createOrderItems($order->id, $items);

    //     session(['order_id' => $order->id]);

    //     return view('website.checkout', compact('order', 'items', 'total'));
    // }
    // public function index()
    // {
    //     $cart  = $this->cartRepo->getOrCreateCart();
    //     $items = $this->cartRepo->getCartItems($cart->id);

    //     if ($items->count() === 0) {
    //         return redirect('/')->with('error', 'Cart is empty');
    //     }

    //     // ✅ SAFE TOTAL
    //     $total = 0;
    //     foreach ($items as $item) {
    //         $total += ($item->price * $item->qty);
    //     }

    //     // ✅ DO NOT PASS Auth::id()
    //     $order = $this->orderRepo->createOrder($total);
    //     $this->orderRepo->createOrderItems($order->id, $items);

    //     session(['order_id' => $order->id]);

    //     return view('website.checkout', compact('order', 'items', 'total'));
    // }
    public function index()
    {
        $cart  = $this->cartRepo->getOrCreateCart();
        $items = $this->cartRepo->getCartItems($cart->id);

        if ($items->count() === 0) {
            return redirect('/')->with('error', 'Cart is empty');
        }

        $total = 0;
        foreach ($items as $item) {
            $total += ($item->price * $item->qty);
        }

        return view('website.checkout', compact('items', 'total'));
    }
    public function placeOrder()
    {
        $cart  = $this->cartRepo->getOrCreateCart();
        $items = $this->cartRepo->getCartItems($cart->id);

        if ($items->count() === 0) {
            return response()->json(['error' => 'Cart is empty'], 400);
        }

        $total = 0;
        foreach ($items as $item) {
            $total += $item->price * $item->qty;
        }

        $order = $this->orderRepo->createOrder($total);
        $this->orderRepo->createOrderItems($order->id, $items);

        session(['order_id' => $order->id]);

        return response()->json(['success' => true]);
    }

    public function createOrder()
    {
        $cart  = $this->cartRepo->getOrCreateCart();
        $items = $this->cartRepo->getCartItems($cart->id);

        if ($items->count() === 0) {
            return redirect('/cart')->with('error', 'Cart is empty');
        }

        $total = 0;
        foreach ($items as $item) {
            $total += ($item->price * $item->qty);
        }

        // ✅ CREATE ORDER HERE
        $order = $this->orderRepo->createOrder($total);
        $this->orderRepo->createOrderItems($order->id, $items);

        // store order in session
        session(['order_id' => $order->id]);

        // redirect to checkout page
        return redirect()->route('checkout.index');
    }


    // STEP 2: Razorpay Order
    public function pay()
    {
        $orderId = session('order_id');

        if (!$orderId) {
            return response()->json(['error' => 'Order not found'], 400);
        }

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

        session()->put('razorpay_order_id', $razorpayOrder['id']);

        return response()->json([
            'order_id' => $razorpayOrder['id'],
            'amount' => $order->total_amount * 100,
            'key' => config('services.razorpay.key'),
        ]);
    }

    // STEP 3: Payment Success
    // public function success(Request $request)
    // {
    //     $api = new Api(
    //         config('services.razorpay.key'),
    //         config('services.razorpay.secret')
    //     );

    //     try {
    //         $api->utility->verifyPaymentSignature([
    //             'razorpay_order_id' => session('razorpay_order_id'),
    //             'razorpay_payment_id' => $request->razorpay_payment_id,
    //             'razorpay_signature' => $request->razorpay_signature,
    //         ]);
    //     } catch (\Exception $e) {
    //         return redirect('/checkout')->with('error', 'Payment verification failed');
    //     }

    //     $orderId = session('order_id');

    //     $this->orderRepo->markAsPaid(
    //         $orderId,
    //         $request->razorpay_payment_id
    //     );

    //     $cart = $this->cartRepo->getOrCreateCart();
    //     $this->cartRepo->clearCart($cart->id);

    //     session()->forget(['order_id', 'razorpay_order_id']);

    //     return view('website.payment-success');
    // }
    public function success(Request $request)
    {
        $api = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );

        try {
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => session('razorpay_order_id'),
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ]);
        } catch (\Exception $e) {
            return redirect('/checkout')->with('error', 'Payment verification failed');
        }

        // ✅ GET EXISTING ORDER
        $orderId = session('order_id');

        if (!$orderId) {
            return redirect('/cart')->with('error', 'Order not found');
        }

        // ✅ UPDATE PAYMENT STATUS
        \App\Models\Order::where('id', $orderId)->update([
            'payment_status' => 'PAID',   // or SUCCESS
            'payment_id'     => $request->razorpay_payment_id,
        ]);

        // ✅ CLEAR CART
        $cart = $this->cartRepo->getOrCreateCart();
        $this->cartRepo->clearCart($cart->id);

        // ✅ CLEAR SESSION
        session()->forget(['order_id', 'razorpay_order_id']);

        return view('website.payment-success');
    }

    public function clearCart($cartId)
    {
        CartItem::where('cart_id', $cartId)->delete();
        Cart::where('id', $cartId)->delete();
    }


    public function razorpayWebhook(Request $request)
    {
        $webhookSecret = config('services.razorpay.webhook_secret');

        $signature = $request->header('X-Razorpay-Signature');
        $payload   = $request->getContent();

        // 🔐 Verify webhook signature
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Razorpay webhook signature mismatch');
            return response()->json(['status' => 'invalid signature'], 400);
        }

        $data = json_decode($payload, true);

        // ✅ Only handle payment.captured
        if (
            isset($data['event']) &&
            $data['event'] === 'payment.captured'
        ) {
            $payment = $data['payload']['payment']['entity'];

            $paymentId = $payment['id'];
            $orderNo   = $payment['notes']['receipt'] ?? null;

            if ($orderNo) {
                // Update order ONLY from webhook
                \App\Models\Order::where('order_no', $orderNo)
                    ->where('payment_status', 'PENDING') // prevent duplicate
                    ->update([
                        'payment_status' => 'PAID',
                        'payment_id'     => $paymentId,
                    ]);
            }
        }

        return response()->json(['status' => 'ok'], 200);
    }
}

<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Repository\Website\CartRepository;
use App\Http\Repository\Website\OrderRepository;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\MediaBookedDate;
use App\Models\User;

class CheckoutController extends Controller
{
    public function __construct(
        private CartRepository $cartRepo,
        private OrderRepository $orderRepo
    ) {}
    public function index()
    {
        $orderId = session('order_id');

        if (!$orderId) {
            return redirect('/')->with('error', 'Order session expired');
        }

        $order = $this->orderRepo->findById($orderId);

        if (!$order) {
            session()->forget('order_id');
            return redirect('/')->with('error', 'Order not found');
        }

        $items = \App\Models\OrderItem::where('order_items.order_id', $orderId)
            ->join('media_management as m', 'm.id', '=', 'order_items.media_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->select(
                'order_items.price',
                'order_items.total_days',
                'order_items.qty',
                'm.media_title',
                'm.facing',
                'a.area_name'
            )
            ->get();



        //  GST CALCULATION
        $subTotal = $order->total_amount;
        $gstRate  = 18;
        $gstAmount = round(($subTotal * $gstRate) / 100, 2);
        $grandTotal = round($subTotal + $gstAmount, 2);

        return view('website.checkout', compact(
            'items',
            'subTotal',
            'gstRate',
            'gstAmount',
            'grandTotal'
        ));
    }

    public function placeOrder()
    {
        if (!Auth::guard('website')->check()) {
            return redirect('/')->with('error', 'Please login');
        }

        $userId = Auth::guard('website')->id();
        $items  = $this->cartRepo->getCartItems();

        foreach ($items as $item) {

            // 1) Check campaign bookings
            $campaignBooked = DB::table('media_booked_date')
                ->where('media_id', $item->media_id)
                ->where('is_deleted', 0)
                ->where('is_active', 1)
                ->where(function ($q) use ($item) {
                    $q->whereBetween('from_date', [$item->from_date, $item->to_date])
                        ->orWhereBetween('to_date', [$item->from_date, $item->to_date])
                        ->orWhere(function ($q2) use ($item) {
                            $q2->where('from_date', '<=', $item->from_date)
                                ->where('to_date', '>=', $item->to_date);
                        });
                })
                ->exists();

            if ($campaignBooked) {
                return back()->with('error', 'Some media is already booked. Please change dates.');
            }

            // 2) Check PAID orders
            $paidBooked = DB::table('order_items as oi')
                ->join('orders as o', 'o.id', '=', 'oi.order_id')
                ->where('oi.media_id', $item->media_id)
                ->where('o.payment_status', 'PAID')
                ->where(function ($q) use ($item) {
                    $q->whereBetween('oi.from_date', [$item->from_date, $item->to_date])
                        ->orWhereBetween('oi.to_date', [$item->from_date, $item->to_date])
                        ->orWhere(function ($q2) use ($item) {
                            $q2->where('oi.from_date', '<=', $item->from_date)
                                ->where('oi.to_date', '>=', $item->to_date);
                        });
                })
                ->exists();

            if ($paidBooked) {
                return back()->with('error', 'media already booked by another user.');
            }
        }


        if ($items->isEmpty()) {
            return back()->with('error', 'Cart is empty');
        }

        foreach ($items as $item) {
            if (!$item->from_date || !$item->to_date) {
                return back()->with('error', 'Please select booking dates');
            }
        }

        $total = $items->sum(fn($i) => $i->total_price);

        $order = DB::transaction(function () use ($userId, $items, $total) {

            // 🔎 CHECK existing PENDING order
            $order = Order::where('user_id', $userId)
                ->where('payment_status', 'PENDING')
                ->latest()
                ->first();

            if ($order) {
                //  UPDATE order amount only
                $gst = round(($total * 18) / 100, 2);

                $order->update([
                    'total_amount' => $total,
                    'gst_amount'   => $gst,
                    'grand_total'  => $total + $gst,
                ]);

                return $order;
            }

            // 🆕 CREATE order only if none exists
            $order = $this->orderRepo->createOrder($total);

            // 🧠 INSERT order_items ONLY ONCE
            $this->orderRepo->createOrderItems($order->id, $items);

            return $order;
        });

        session(['order_id' => $order->id]);

        return redirect()->route('checkout.index');
    }


    // public function razorpayWebhook(Request $request)
    // {
    //     $payload   = $request->getContent();
    //     $signature = $request->header('X-Razorpay-Signature');
    //     $secret    = config('services.razorpay.webhook_secret');

    //     try {
    //         //  VERIFY WEBHOOK SIGNATURE
    //         $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
    //         $api->utility->verifyWebhookSignature($payload, $signature, $secret);

    //         $data = json_decode($payload, true);

    //         $razorpayOrderId = $data['payload']['payment']['entity']['order_id'];
    //         $paymentId       = $data['payload']['payment']['entity']['id'];

    //         $order = \App\Models\Order::where('payment_gateway_order_id', $razorpayOrderId)->first();

    //         if (!$order) {
    //             return response()->json(['status' => 'order_not_found'], 404);
    //         }

    //         $order->update([
    //             'payment_status' => 'PAID',
    //             'payment_id' => $paymentId
    //         ]);

    //         return response()->json(['status' => 'success'], 200);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }
    public function razorpayWebhook(Request $request)
    {
        $payload   = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');
        $secret    = config('services.razorpay.webhook_secret');

        // ✅ LOG 1 — Check if webhook is hitting controller
        \Log::info('Razorpay Webhook HIT', [
            'signature' => $signature,
            'secret_present' => !empty($secret),
            'payload' => $payload
        ]);

        if (empty($signature) || empty($secret)) {
            \Log::warning('Signature missing', [
                'signature' => $signature,
                'secret' => $secret
            ]);

            return response()->json(['status' => 'signature_missing'], 200);
        }

        try {
            $api = new Api(
                config('services.razorpay.key'),
                config('services.razorpay.secret')
            );

            // Verify webhook signature
            $api->utility->verifyWebhookSignature($payload, $signature, $secret);

            $data  = json_decode($payload, true);
            $event = $data['event'] ?? null;

            // ✅ LOG 2 — Which event came
            \Log::info('Razorpay Event Received', [
                'event' => $event
            ]);

            if (!in_array($event, ['payment.captured', 'order.paid'])) {
                return response()->json(['status' => 'ignored'], 200);
            }

            $payment = $data['payload']['payment']['entity'] ?? null;

            if (!$payment) {
                \Log::warning('No payment entity found');
                return response()->json(['status' => 'no_payment'], 200);
            }

            $razorpayOrderId = $payment['order_id'] ?? null;
            $paymentId       = $payment['id'] ?? null;

            // ✅ LOG 3 — Payment details
            \Log::info('Payment Data', [
                'razorpay_order_id' => $razorpayOrderId,
                'payment_id' => $paymentId
            ]);

            if (!$razorpayOrderId || !$paymentId) {
                return response()->json(['status' => 'invalid_payload'], 200);
            }

            $order = Order::where('payment_gateway_order_id', $razorpayOrderId)->first();

            if ($order && $order->payment_status !== 'PAID') {
                $order->update([
                    'payment_status' => 'PAID',
                    'payment_id'     => $paymentId,
                ]);

                \Log::info('Order marked PAID', [
                    'order_id' => $order->id
                ]);
            }

            return response()->json(['status' => 'success'], 200);
        } catch (\Throwable $e) {
            \Log::error('Razorpay Webhook Error', [
                'message' => $e->getMessage(),
                'payload' => $payload
            ]);

            return response()->json(['status' => 'error_logged'], 200);
        }
    }




    public function pay()
    {
        $orderId = session('order_id');
        $order = $this->orderRepo->findById($orderId);

        $subTotal = $order->total_amount;
        $gstAmount = round(($subTotal * 18) / 100, 2);
        $grandTotal = round($subTotal + $gstAmount, 2);

        //  Razorpay requires INTEGER amount in paise
        $amountInPaise = (int) round($grandTotal * 100);


        $api = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );

        $razorpayOrder = $api->order->create([
            'receipt'  => $order->order_no,
            'amount'   => $amountInPaise, //  GST INCLUDED
            'currency' => 'INR',
        ]);
        //  IMPORTANT: SAVE RAZORPAY ORDER ID IN DB
        $order->update([
            'payment_gateway_order_id' => $razorpayOrder['id'],
        ]);
        session([
            'razorpay_order_id' => $razorpayOrder['id'],
            'gst_amount'        => $gstAmount,
            'grand_total'       => $grandTotal
        ]);

        return response()->json([
            'order_id' => $razorpayOrder['id'],
            'amount'   => $amountInPaise,
            'key'      => config('services.razorpay.key'),
        ]);
    }


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

        //  Mark order paid
        \App\Models\Order::where('id', $orderId)->update([
            'payment_status' => 'PAID',
            'payment_id'     => $request->razorpay_payment_id,
        ]);

        $order = Order::find($orderId);

        //  Notify admins payment done
        $admins = User::where('id', 1)->get();
        foreach ($admins as $admin) {
            // $admin->notify(new PaymentReceivedNotification($order));
            \App\Models\Notification::create([
                'user_id'  => $admin->id,
                'order_id' => $order->id,
                'media_id' => null,   // payment -> no media
                'is_read'  => 0,      // important
            ]);
        }

        //  Clear NORMAL cart items (VERY IMPORTANT)
        \App\Models\CartItem::where('user_id', $userId)
            ->where('cart_type', 'NORMAL')
            ->where('status', 'ACTIVE')
            ->update([
                'status'     => 'ORDERED',
                'is_active'  => 0,
                'is_deleted' => 1,
            ]);

        //  Load order with items
        $order = Order::with('items')->findOrFail($orderId);

        /*
            |--------------------------------------------------------------------------
            | INSERT / UPDATE MEDIA BOOKED DATES
            |--------------------------------------------------------------------------
            */
        foreach ($order->items as $item) {

            $existing = MediaBookedDate::where('media_id', $item->media_id)->first();

            if ($existing) {
                //  ONLY update to_date
                $existing->update([
                    'to_date' => $item->to_date,
                ]);
            } else {
                //  Insert new record
                MediaBookedDate::create([
                    'media_id'  => $item->media_id,
                    'from_date' => $item->from_date,
                    'to_date'   => $item->to_date,
                ]);
            }
        }


        //  2 Clear CAMPAIGN items if campaign order
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

        // return view('website.dashboard');
        return redirect()->route('dashboard.home')->with('success', 'Payment successful!');
    }


    // public function placeCampaignOrder($campaignId)
    // {
    //     $campaignId = base64_decode($campaignId);

    //     $items = CartItem::where('campaign_id', $campaignId)
    //         ->where('cart_type', 'CAMPAIGN')
    //         ->where('status', 'ACTIVE')
    //         ->get();

    //     if ($items->isEmpty()) {
    //         return back()->with('error', 'Campaign is empty');
    //     }

    //     // $total = $items->sum(fn($i) => $i->price * $i->qty);
    //     $total = $items->sum(fn($i) => $i->total_price);

    //     // $order = $this->orderRepo->createOrder($total);
    //      $order = $this->orderRepo->createOrder($total, $campaignId);
    //     $this->orderRepo->createOrderItems($order->id, $items);

    //     // ONLY STORE ORDER ID
    //     session([
    //         'order_id'    => $order->id,
    //         'campaign_id' => $campaignId,
    //     ]);

    //     return redirect()->route('checkout.index');
    // }
    public function placeCampaignOrder($campaignId)
    {
        $campaignId = base64_decode($campaignId);

        $userId = Auth::guard('website')->id();

        $items = CartItem::where('campaign_id', $campaignId)
            ->where('cart_type', 'CAMPAIGN')
            ->where('status', 'ACTIVE')
            ->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'Campaign is empty');
        }

        $total = $items->sum(fn($i) => $i->total_price);

        DB::beginTransaction();

        try {

            // 🔴 STEP 1: CHECK existing pending order for SAME campaign
            $order = Order::where('user_id', $userId)
                ->where('campaign_id', $campaignId)
                ->where('payment_status', 'PENDING')
                ->latest()
                ->first();

            if ($order) {
                // 🟡 Order already exists → DO NOT create new
                session([
                    'order_id'    => $order->id,
                    'campaign_id' => $campaignId,
                ]);

                DB::commit();

                return redirect()->route('checkout.index');
            }

            // 🟢 STEP 2: CREATE new order only if none exists
            $order = $this->orderRepo->createOrder($total, $campaignId);

            // 🧠 Insert items only once
            $this->orderRepo->createOrderItems($order->id, $items);

            session([
                'order_id'    => $order->id,
                'campaign_id' => $campaignId,
            ]);

            DB::commit();

            return redirect()->route('checkout.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}

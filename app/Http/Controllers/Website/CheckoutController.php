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

        $items = \App\Models\OrderItem::where('order_id', $orderId)
            ->join('media_management as m', 'm.id', '=', 'order_items.media_id')
            ->select(
                'order_items.price',
                'order_items.qty',
                'm.media_title'
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

        $items = $this->cartRepo->getCartItems();

        if ($items->isEmpty()) {
            return back()->with('error', 'Cart is empty');
        }

        //  Prevent empty date items
        foreach ($items as $item) {
            if (!$item->from_date || !$item->to_date) {
                return redirect()
                    ->back()
                    ->with('error', 'Please select date and click "Add Dates" before checkout.');
            }
        }

        //  Calculate total AFTER validation
        $total = $items->sum(fn($i) => $i->total_price);

        $order = DB::transaction(function () use ($items, $total) {
            $order = $this->orderRepo->createOrder($total);
            $this->orderRepo->createOrderItems($order->id, $items);
            return $order;
        });

        // Notify all admins
        $admins = User::where('id', 1)->get();
        foreach ($admins as $admin) {
            // $admin->notify(new OrderPlacedNotification($order));
            // \App\Models\Notification::create([
            //     'user_id'  => $admin->id,
            //     'order_id' => $order->id,
            //     'media_id' => null, // use when needed
            // ]);
            foreach ($items as $item) {

                \App\Models\Notification::create([
                    'user_id'  => $admin->id, // Admin who reads
                    'order_id' => $order->id,
                    'media_id' => $item->media_id,
                    'is_read'  => 0,
                ]);
            }
        }

        session(['order_id' => $order->id]);

        return redirect()->route('checkout.index');
    }

    // public function placeOrder()
    // {
    //     if (!Auth::guard('website')->check()) {
    //         return redirect('/')->with('error', 'Please login');
    //     }

    //     $items = $this->cartRepo->getCartItems();

    //     if ($items->isEmpty()) {
    //         return back()->with('error', 'Cart is empty');
    //     }

    //     $total = $items->sum(fn($i) => $i->total_price);

    //     $order = DB::transaction(function () use ($items, $total) {
    //         $order = $this->orderRepo->createOrder($total);
    //         $this->orderRepo->createOrderItems($order->id, $items);
    //         return $order;
    //     });

    //     session(['order_id' => $order->id]);

    //     return redirect()->route('checkout.index'); //  IMPORTANT
    // }
    public function razorpayWebhook(Request $request)
    {
        $payload   = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');
        $secret    = config('services.razorpay.webhook_secret');

        try {
            //  VERIFY WEBHOOK SIGNATURE
            $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
            $api->utility->verifyWebhookSignature($payload, $signature, $secret);

            $data = json_decode($payload, true);

            $razorpayOrderId = $data['payload']['payment']['entity']['order_id'];
            $paymentId       = $data['payload']['payment']['entity']['id'];

            $order = \App\Models\Order::where('payment_gateway_order_id', $razorpayOrderId)->first();

            if (!$order) {
                return response()->json(['status' => 'order_not_found'], 404);
            }

            $order->update([
                'payment_status' => 'PAID',
                'payment_id' => $paymentId
            ]);

            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function pay()
    {
        $orderId = session('order_id');
        $order = $this->orderRepo->findById($orderId);

        $subTotal = $order->total_amount;
        $gstAmount = round(($subTotal * 18) / 100, 2);
        $grandTotal = round($subTotal + $gstAmount, 2);

        $api = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );

        $razorpayOrder = $api->order->create([
            'receipt'  => $order->order_no,
            'amount'   => $grandTotal * 100, //  GST INCLUDED
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
            'amount'   => $grandTotal * 100,
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

        return view('website.dashboard');
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

        // $total = $items->sum(fn($i) => $i->price * $i->qty);
        $total = $items->sum(fn($i) => $i->total_price);

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

<?php

namespace App\Http\Services\Website;

use App\Http\Repository\Website\OrderRepository;
use App\Http\Repository\Website\CartRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CheckoutService
{
    protected $orderRepo;
    protected $cartRepo;

    public function __construct(OrderRepository $orderRepo, CartRepository $cartRepo)
    {
        $this->orderRepo = $orderRepo;
        $this->cartRepo  = $cartRepo;
    }

    public function placeOrder()
    {
        return DB::transaction(function () {

            // 🔐 Ensure website user is logged in
            if (!Auth::guard('website')->check()) {
                throw new \Exception('User not logged in');
            }

            $cart  = $this->cartRepo->getOrCreateCart();
            $items = $this->cartRepo->getCartItems($cart->id);

            if ($items->count() === 0) {
                throw new \Exception('Cart is empty');
            }

            // ✅ Calculate total correctly
            $total = $items->sum(fn($i) => $i->price * $i->qty);

            // ✅ DO NOT pass user_id
            $order = $this->orderRepo->createOrder($total);

            $this->orderRepo->createOrderItems($order->id, $items);

            return $order;
        });
    }
}

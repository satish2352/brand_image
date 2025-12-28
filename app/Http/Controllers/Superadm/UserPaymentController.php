<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\UserPaymentService;

class UserPaymentController extends Controller
{
    protected $service;

    public function __construct(UserPaymentService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $payments = $this->service->list();
        return view('superadm.user-payment.user-payment-list', compact('payments'));
    }

    public function details($orderId)
    {
        $orderId = base64_decode($orderId);

        $order = $this->service->getOrderDetails($orderId);
        return view('superadm.user-payment.user-payment-details', compact('order'));
    }
}

<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Services\Website\PaymentService;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class PaymentHistoryController extends Controller
{
    protected $campaignService;

    public function __construct(PaymentService $campaignService)
    {
        $this->campaignService = $campaignService;
    }
    public function paymentHistory()
    {
        $userId = Auth::guard('website')->id();

        $payments = $this->campaignService->getInvoicePayments($userId);

        return view('website.payment-history', compact('payments'));
    }
    public function viewInvoice($orderId)
    {
        try {
            $orderId = base64_decode($orderId);

            $items = $this->campaignService->getInvoiceDetails($orderId);

            return view('website.payment-receipt', compact('items'));
        } catch (\Throwable $e) {
            Log::error('Invoice View Error', [
                'message' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Unable to load invoice');
        }
    }
}

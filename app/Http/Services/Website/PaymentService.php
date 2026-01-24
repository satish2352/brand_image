<?php

namespace App\Http\Services\Website;

use App\Http\Repository\Website\PaymentRepository;


class PaymentService
{
    public function __construct(
        protected PaymentRepository $repo
    ) {}

    public function getInvoicePayments($userId)
    {
        $data_output = $this->repo->getPaidCampaignInvoices($userId);
        // dd($data_output);
        // die();
        return $data_output;
    }
    public function getInvoiceDetails($orderId)
    {
        $data_output = $this->repo->getInvoiceDetails($orderId);

        return $data_output;
    }
}

<?php

namespace App\Http\Services\Website;

use App\Http\Repository\Website\CampaignRepository;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    public function __construct(
        protected CampaignRepository $repo
    ) {}

    // public function saveCampaign($userId, $campaignName)
    // {
    //     return DB::transaction(function () use ($userId, $campaignName) {
    //         return $this->repo->createCampaignByCopyingCart(
    //             $userId,
    //             $campaignName
    //         );
    //     });
    // }
    public function saveCampaign($userId, $campaignName)
    {
        return $this->repo->createCampaignAndMoveCart(
            $userId,
            $campaignName
        );
    }

    public function getCampaignList($userId, $request)
    {
        $data_output = $this->repo->getCampaignList($userId, $request);

        return $data_output;
    }

    public function getCampaignDetailsByCartItem($userId, $cartItemId)
    {
        $data_output = $this->repo->getCampaignDetailsByCartItem($userId, $cartItemId);
        // dd($data_output);
        return $data_output;
    }

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

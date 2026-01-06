<?php

namespace App\Http\Services\Website;

use App\Http\Repository\Website\CampaignRepository;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    public function __construct(
        protected CampaignRepository $repo
    ) {}


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

        return $data_output;
    }
}

<?php

namespace App\Http\Services\Website;

use App\Http\Repository\Website\CampaignRepository;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    protected $campaignRepo;

    public function __construct(CampaignRepository $campaignRepo)
    {
        $this->campaignRepo = $campaignRepo;
    }

    public function saveCampaign($userId, $campaignName)
    {
        return DB::transaction(function () use ($userId, $campaignName) {
            return $this->campaignRepo->updateCartCampaign(
                $userId,
                $campaignName
            );
        });
    }

    public function getCampaignList($userId)
    {
        $data_output = $this->campaignRepo->getCampaignList($userId);
        // dd($data_output);
        // die();
        return $data_output;
    }
}

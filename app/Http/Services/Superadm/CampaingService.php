<?php

namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\CampaingRepository;
use Illuminate\Support\Facades\DB;

class CampaingService
{
    protected $repo;

    public function __construct(CampaingRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list()
    {
        $data_output =  $this->repo->list();

        return $data_output;
    }

    public function delete($id)
    {
        return $this->repo->delete($id);
    }
    public function toggleStatus($id)
    {
        return $this->repo->toggleStatus($id);
    }

    public function adminCampaignList()
    {
        return $this->repo->adminCampaignList();
    }


    // public function getCampaignByUserForAdmin($userId)
    // {
    //     return DB::table('campaign as c')
    //         ->join('cart_items as ci', 'ci.campaign_id', '=', 'c.id')
    //         ->leftJoin('media_management as m', 'm.id', '=', 'ci.media_id')
    //         ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
    //         ->where('c.user_id', $userId)
    //         ->where('ci.cart_type', 'CAMPAIGN')
    //         ->select(
    //             'c.id as campaign_id',
    //             'c.campaign_name',
    //             'ci.from_date',
    //             'ci.to_date',
    //             'ci.qty',
    //             'ci.price',
    //             'ci.total_days',
    //             'ci.total_price',
    //             'm.media_title',
    //             'm.width',
    //             'm.height',
    //             'a.common_stdiciar_name'
    //         )
    //         ->get()
    //         ->groupBy('campaign_name');
    // }

    public function getCampaignByUserForAdmin($userId)
    {
        return DB::table('campaign as c')
            ->join('cart_items as ci', 'ci.campaign_id', '=', 'c.id')
            ->leftJoin('media_management as m', 'm.id', '=', 'ci.media_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->where('c.user_id', $userId)
            ->where('ci.cart_type', 'CAMPAIGN')
            ->select(
                'c.id as campaign_id',
                'c.campaign_name',
                'c.user_id',
                'ci.media_id',
                'ci.from_date',
                'ci.to_date',
                'ci.qty',
                'ci.price',
                'ci.total_days',
                'ci.total_price',
                'm.media_title',
                'm.width',
                'm.height',
                'a.common_stdiciar_name'
            )
            ->orderBy('c.id', 'DESC')
            ->get();   // ← groupBy काढले
    }
}

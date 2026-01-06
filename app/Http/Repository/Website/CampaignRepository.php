<?php

namespace App\Http\Repository\Website;

use App\Models\Campaign;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;

class CampaignRepository
{
    public function createCampaignAndMoveCart($userId, $campaignName)
    {
        return DB::transaction(function () use ($userId, $campaignName) {

            // 1️⃣ Create campaign
            $campaign = Campaign::create([
                'user_id'       => $userId,
                'campaign_name' => $campaignName,
                'is_active'     => 1,
                'is_deleted'    => 0,
            ]);

            // 2️⃣ Update existing NORMAL cart items
            $query = CartItem::where('status', 'ACTIVE')
                ->where('cart_type', 'NORMAL')
                ->where('user_id', $userId);

            if (!$query->exists()) {
                throw new \Exception('Cart is empty');
            }

            $query->update([
                'cart_type'   => 'CAMPAIGN',
                'campaign_id' => $campaign->id,
            ]);

            return true;
        });
    }


    /**
     * Campaign list
     */
    // public function getCampaignList($userId, $request)
    // {
    //     return DB::table('campaign as c')
    //         ->join('cart_items as ci', 'ci.campaign_id', '=', 'c.id')
    //         ->join('media_management as m', 'm.id', '=', 'ci.media_id')
    //         ->select(
    //             'c.id as campaign_id',
    //             'c.campaign_name',
    //             'ci.price',
    //             'ci.qty',
    //             'ci.created_at as campaign_date',
    //             'm.media_title',
    //             'm.width',
    //             'm.height'
    //         )
    //         ->where('c.user_id', $userId)
    //         ->where('ci.cart_type', 'CAMPAIGN')
    //         ->where('ci.status', 'ACTIVE')
    //         ->where('c.is_active', 1)
    //         ->where('c.is_deleted', 0)
    //         ->orderBy('c.id', 'DESC')
    //         ->paginate(10);
    // }
    public function getCampaignList($userId, $request)
    {
        $query = DB::table('campaign as c')
            ->leftJoin('cart_items as ci', 'ci.campaign_id', '=', 'c.id')
            ->leftJoin('media_management as m', 'm.id', '=', 'ci.media_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->select(
                'ci.id as cart_item_id',
                'c.id as campaign_id',
                'c.campaign_name',
                'ci.media_id',
                'ci.price',
                'ci.qty',
                'ci.per_day_price',
                'ci.total_price',
                'ci.total_days',
                'ci.from_date',
                'ci.to_date',
                'ci.created_at as campaign_date',
                'm.media_title',
                'm.width',
                'm.height',
                'a.common_stdiciar_name'
            )
            ->where('c.user_id', $userId)
            ->where('ci.cart_type', 'CAMPAIGN')
            ->where('ci.status', 'ACTIVE');

        if ($request->filled('campaign_name')) {
            $query->where('c.campaign_name', 'like', '%' . $request->campaign_name . '%');
        }

        return $query
            ->orderBy('c.id', 'DESC')
            ->get()
            ->groupBy('campaign_id'); // ⭐ grouped campaign-wise
    }

    public function getCampaignDetailsByCartItem($userId, $cartItemId)
    {
        $data = DB::table('cart_items as ci')
            ->join('campaign as c', 'c.id', '=', 'ci.campaign_id')
            ->join('media_management as m', 'm.id', '=', 'ci.media_id')
            ->leftJoin('media_images as mi', function ($join) {
                $join->on('mi.media_id', '=', 'm.id')
                    ->where('mi.is_deleted', 0);
            })
            ->select(
                'c.campaign_name',
                'ci.price',
                'ci.qty',
                'ci.created_at',
                'ci.total_price',
                'ci.total_days',
                'ci.from_date',
                'ci.to_date',
                'm.media_title',
                'm.width',
                'm.height',
                DB::raw('GROUP_CONCAT(mi.images) as images')
            )
            ->where('ci.id', $cartItemId)
            ->where('c.user_id', $userId)
            ->where('ci.cart_type', 'CAMPAIGN')
            ->where('ci.status', 'ACTIVE')
            ->groupBy(
                'c.campaign_name',
                'ci.price',
                'ci.qty',
                'ci.total_price',
                'ci.total_days',
                'ci.from_date',
                'ci.to_date',
                'ci.created_at',
                'm.media_title',
                'm.width',
                'm.height'
            )
            ->get();


        if ($data->isEmpty()) {
            throw new \Exception('Invalid campaign/cart item');
        }

        return $data;
    }
}

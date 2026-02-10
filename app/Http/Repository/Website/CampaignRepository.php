<?php

namespace App\Http\Repository\Website;

use App\Models\Campaign;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CampaignRepository
{
    // public function createCampaignAndMoveCart($userId, $campaignName)
    // {
    //     return DB::transaction(function () use ($userId, $campaignName) {

    //         // 1️⃣ Create campaign
    //         $campaign = Campaign::create([
    //             'user_id'       => $userId,
    //             'campaign_name' => $campaignName,
    //             'is_active'     => 1,
    //             'is_deleted'    => 0,
    //         ]);

    //         // 2️⃣ Update existing NORMAL cart items
    //         $query = CartItem::where('status', 'ACTIVE')
    //             ->where('cart_type', 'NORMAL')
    //             ->where('user_id', $userId);

    //         if (!$query->exists()) {
    //             throw new \Exception('Cart is empty');
    //         }

    //         $query->update([
    //             'cart_type'   => 'CAMPAIGN',
    //             'campaign_id' => $campaign->id,
    //         ]);

    //         return true;
    //     });
    // }
    public function createCampaignAndMoveCart($userId, $campaignName)
    {
        return DB::transaction(function () use ($userId, $campaignName) {

            $campaign = Campaign::create([
                'user_id'       => $userId,
                'campaign_name' => $campaignName,
                'is_active'     => 1,
                'is_deleted'    => 0,
            ]);

            $normalItems = CartItem::where('status', 'ACTIVE')
                ->where('cart_type', 'NORMAL')
                ->where('user_id', $userId)
                ->get();

            if ($normalItems->isEmpty()) {
                throw new \Exception('Cart is empty');
            }

            $duplicateFound = false;

            foreach ($normalItems as $item) {

                $exists = CartItem::where('user_id', $userId)
                    ->where('media_id', $item->media_id)
                    ->where('from_date', $item->from_date)
                    ->where('to_date', $item->to_date)
                    ->whereNotNull('campaign_id')          // only real campaigns
                    ->where('id', '!=', $item->id)         // exclude same cart row
                    ->exists();


                if ($exists) {
                    $duplicateFound = true;
                    continue;
                }

                $item->update([
                    'cart_type'   => 'CAMPAIGN',
                    'campaign_id' => $campaign->id,
                ]);
            }

            if ($duplicateFound) {
                throw new \Exception('Some media are already added in another campaign for the same dates.');
            }

            return true;
        });
    }


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
                'm.facing',
                'm.width',
                'm.height',
                'a.area_name'
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
    private function baseQuery($userId, $request)
    {
        $query = DB::table('campaign as c')
            ->join('cart_items as ci', 'ci.campaign_id', '=', 'c.id')
            ->join('media_management as m', 'm.id', '=', 'ci.media_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->where('c.user_id', $userId)
            ->where('ci.cart_type', 'CAMPAIGN')
            ->where('ci.status', 'ACTIVE');

        if ($request->filled('campaign_name')) {
            $query->where('c.campaign_name', 'like', '%' . $request->campaign_name . '%');
        }

        return $query->select(
            'ci.id as cart_item_id',
            'ci.media_id',
            'c.id as campaign_id',
            'c.campaign_name',
            'ci.total_price',
            'ci.total_days',
            'ci.from_date',
            'ci.to_date',
            'ci.created_at as campaign_date',
            'm.media_title',
            'm.facing',
            'm.width',
            'm.height',
            'a.area_name'
        );
    }

    // public function fetchOpenCampaigns($userId, $request)
    // {
    //     return $this->baseQuery($userId, $request)
    //         ->where('ci.status', 'ACTIVE') // only active cart media
    //         ->whereNotExists(function ($q) {
    //             $q->select(DB::raw(1))
    //                 ->from('order_items as oi')
    //                 ->join('orders as o', 'o.id', '=', 'oi.order_id')
    //                 ->whereColumn('oi.media_id', 'ci.media_id') // media-wise
    //                 ->where('ci.cart_type', 'CAMPAIGN') // booked ones
    //                 ->where('o.is_deleted', 0);
    //         })
    //         ->whereDate('ci.to_date', '>=', now()->toDateString())
    //         ->orderBy('c.id', 'DESC')
    //         ->get()
    //         ->groupBy('campaign_id');
    // }

    public function getOpenCampaigns($userId, $request)
    {
        return $this->baseQuery($userId, $request)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('orders as o')
                    ->whereColumn('o.campaign_id', 'c.id')
                    ->where('o.is_deleted', 0);
            })
            ->whereDate('ci.to_date', '>=', now()->toDateString())
            ->orderBy('c.id', 'DESC')
            ->get()
            ->groupBy('campaign_id');
    }
    public function fetchBookedCampaigns($userId, $request)
    {
        return DB::table('campaign as c')
            ->join('orders as o', 'o.campaign_id', '=', 'c.id')
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->join('cart_items as ci', function ($join) {
                $join->on('ci.media_id', '=', 'oi.media_id')
                    ->on('ci.campaign_id', '=', 'c.id');
            })
            ->join('media_management as m', 'm.id', '=', 'ci.media_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->where('c.user_id', $userId)
            ->where('ci.cart_type', 'CAMPAIGN')
            ->whereIn('o.payment_status', ['PAID', 'ADMIN_BOOKED'])
            ->where('o.is_deleted', 0)
            ->select(
                'ci.media_id',
                'ci.id as cart_item_id',
                'c.id as campaign_id',
                'c.campaign_name',

                'ci.total_days',
                'ci.price',
                'ci.per_day_price',
                'ci.total_price',

                'o.gst_amount',
                'o.grand_total',

                'ci.from_date',
                'ci.to_date',
                'ci.created_at as campaign_date',

                'm.media_title',
                'm.width',
                'm.height',
                'a.area_name',
                'm.facing'
            )
            ->orderBy('c.id', 'DESC')
            ->get()
            ->groupBy('campaign_id');
    }

    // public function fetchBookedCampaigns($userId, $request)
    // {
    //     return $this->baseQuery($userId, $request)
    //         ->whereExists(function ($q) {
    //             $q->select(DB::raw(1))
    //                 ->from('order_items as oi')
    //                 ->join('orders as o', 'o.id', '=', 'oi.order_id')
    //                 ->whereColumn('oi.media_id', 'ci.media_id') // 🔑 media-level check
    //                 ->whereIn('o.payment_status', ['PAID', 'ADMIN_BOOKED']) //  FIX
    //                 ->where('o.is_deleted', 0);
    //         })
    //         ->orderBy('c.id', 'DESC')
    //         ->get()
    //         ->groupBy('campaign_id');
    // }

    // public function fetchBookedCampaigns($userId, $request)
    // {
    //     return $this->baseQuery($userId, $request)
    //         ->whereExists(function ($q) {
    //             $q->select(DB::raw(1))
    //                 ->from('orders as o')
    //                 ->whereColumn('o.campaign_id', 'c.id')
    //                 ->where('o.is_deleted', 0);
    //         })
    //         ->orderBy('c.id', 'DESC')
    //         ->get()
    //         ->groupBy('campaign_id');
    // }

    public function fetchPastCampaigns($userId, $request)
    {
        return $this->baseQuery($userId, $request)
            ->whereDate('ci.to_date', '<', now()->toDateString())
            ->orderBy('c.id', 'DESC')
            ->get()
            ->groupBy('campaign_id');
    }
    public function getCampaignDetailsByCartItem($userId, $cartItemId)
    {
        $data = DB::table('cart_items as ci')
            ->join('campaign as c', 'c.id', '=', 'ci.campaign_id')
            ->join('media_management as m', 'm.id', '=', 'ci.media_id')
            ->join('areas as a', 'm.area_id', '=', 'a.id')
            ->join('cities as cit', 'm.city_id', '=', 'cit.id')
            ->join('illuminations as li', 'm.illumination_id', '=', 'li.id')
            ->leftJoin('media_images as mi', function ($join) {
                $join->on('mi.media_id', '=', 'm.id')
                    ->where('mi.is_deleted', 0);
            })
            ->select(
                'c.campaign_name',
                'ci.price',
                'ci.qty',
                'a.area_name',
                'cit.city_name',
                'li.illumination_name',
                'ci.created_at',
                'ci.total_price',
                'ci.total_days',
                'ci.from_date',
                'ci.to_date',
                'm.media_title',
                'm.facing',
                'm.width',
                'm.height',
                DB::raw('GROUP_CONCAT(mi.images) as images')
            )
            ->where('ci.id', $cartItemId)
            ->where('c.user_id', $userId)
            ->where('ci.cart_type', 'CAMPAIGN')
            // ->where('ci.status', 'ACTIVE')
            ->groupBy(
                'c.campaign_name',
                'ci.price',
                'ci.qty',
                'a.area_name',
                'cit.city_name',
                'li.illumination_name',
                'ci.total_price',
                'ci.total_days',
                'ci.from_date',
                'ci.to_date',
                'ci.created_at',
                'm.media_title',
                'm.facing',
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

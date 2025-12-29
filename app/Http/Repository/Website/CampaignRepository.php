<?php

namespace App\Http\Repository\Website;

use App\Models\Campaign;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;

class CampaignRepository
{
    /**
     * Insert campaign + update cart_items
     */
    // public function createCampaignAndUpdateCart($userId, $campaignName)
    // {
    //     // 1️⃣ Create campaign
    //     $campaign = Campaign::create([
    //         'user_id'       => $userId,
    //         'campaign_name' => $campaignName,
    //         'is_active'     => 1,
    //         'is_deleted'    => 0,
    //     ]);

    //     // 2️⃣ Update cart_items with SAME campaign_id
    //     $query = CartItem::where('status', 'ACTIVE')
    //         ->where('cart_type', 'NORMAL');

    //     if ($userId) {
    //         $query->where('user_id', $userId);
    //     } else {
    //         $query->whereNull('user_id')
    //             ->where('session_id', session()->getId());
    //     }

    //     $query->update([
    //         'cart_type'   => 'CAMPAIGN',
    //         'campaign_id' => $campaign->id, // ✅ MUST MATCH
    //     ]);

    //     return true;
    // }
    // public function createCampaignByCopyingCart($userId, $campaignName)
    // {
    //     return DB::transaction(function () use ($userId, $campaignName) {

    //         $campaign = Campaign::create([
    //             'user_id' => $userId,
    //             'campaign_name' => $campaignName,
    //             'is_active' => 1,
    //             'is_deleted' => 0,
    //         ]);

    //         $normalItems = CartItem::where('user_id', $userId)
    //             ->where('cart_type', 'NORMAL')
    //             ->where('status', 'ACTIVE')
    //             ->get();

    //         if ($normalItems->isEmpty()) {
    //             throw new \Exception('Cart is empty');
    //         }

    //         foreach ($normalItems as $item) {
    //             CartItem::create([
    //                 'user_id' => $item->user_id,
    //                 'session_id' => $item->session_id,
    //                 'media_id' => $item->media_id,
    //                 'price' => $item->price,
    //                 'qty' => $item->qty,
    //                 'cart_type' => 'CAMPAIGN',
    //                 'campaign_id' => $campaign->id,
    //                 'status' => 'ACTIVE',
    //                 'is_active' => 1,
    //                 'is_deleted' => 0,
    //             ]);
    //         }

    //         return true;
    //     });
    // }

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
    // public function getPaidCampaignInvoices($userId)
    // {
    //     return DB::table('orders as o')
    //         ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
    //         ->join('media_management as m', 'm.id', '=', 'oi.media_id')
    //         ->leftJoin('campaign as c', 'c.user_id', '=', 'o.user_id')
    //         ->select(
    //             'o.order_no as invoice_no',
    //             'o.id as order_id',
    //             'o.order_no',
    //             'o.total_amount as amount',
    //             'o.payment_status as status',
    //             'o.payment_id',
    //             'o.created_at ad created_at',
    //             'c.campaign_name'
    //         )
    //         ->where('o.user_id', $userId)
    //         ->where('o.payment_status', 'PAID')
    //         ->groupBy(
    //             'o.id',
    //             'o.order_no',
    //             'o.total_amount',
    //             'o.payment_status',
    //             'o.payment_id',
    //             'o.created_at',
    //             'c.campaign_name'
    //         )
    //         ->orderBy('o.id', 'DESC')
    //         ->get();
    // }

    // public function getPaidCampaignInvoices($userId)
    // {
    //     return DB::table('orders as o')
    //         ->select(
    //             'o.id as order_id',
    //             'o.order_no',
    //             'o.total_amount',
    //             'o.payment_status',
    //             'o.payment_id',
    //             'o.created_at'
    //         )
    //         ->where('o.user_id', $userId)
    //         ->where('o.payment_status', 'PAID')
    //         ->orderBy('o.id', 'DESC')
    //         ->get();
    // }
    public function getPaidCampaignInvoices($userId)
    {
        return DB::table('orders as o')
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->join('media_management as m', 'm.id', '=', 'oi.media_id')
            ->leftJoin('cart_items as ci', function ($join) {
                $join->on('ci.media_id', '=', 'm.id')
                    ->where('ci.cart_type', 'CAMPAIGN');
            })
            ->leftJoin('campaign as c', 'c.id', '=', 'ci.campaign_id')
            ->leftJoin('category as cat', 'cat.id', '=', 'm.category_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->select(
                'o.id as order_id',
                'o.order_no',
                'o.total_amount',
                'o.payment_status',
                'o.payment_id',
                'o.created_at',

                // Campaign
                DB::raw('GROUP_CONCAT(DISTINCT c.campaign_name) as campaign_name'),

                // Media info
                DB::raw('COUNT(oi.id) as total_items'),
                DB::raw('GROUP_CONCAT(DISTINCT m.media_title) as media_titles'),

                // Category & Area
                DB::raw('GROUP_CONCAT(DISTINCT cat.category_name) as category_name'),
                DB::raw('GROUP_CONCAT(DISTINCT a.common_stdiciar_name) as common_stdiciar_name')
            )
            ->where('o.user_id', $userId)
            ->where('o.payment_status', 'PAID')
            ->groupBy(
                'o.id',
                'o.order_no',
                'o.total_amount',
                'o.payment_status',
                'o.payment_id',
                'o.created_at'
            )
            ->orderBy('o.id', 'DESC')
            ->get();
    }
    public function getInvoiceDetails($orderId)
    {
        return DB::table('order_items as oi')
            ->leftJoin('media_management as m', 'm.id', '=', 'oi.media_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('media_images as mi', function ($join) {
                $join->on('mi.media_id', '=', 'm.id')
                    ->where('mi.is_deleted', 0);
            })
            ->select(
                'm.media_title',
                'm.width',
                'm.height',
                'oi.price',
                'oi.qty',
                'a.common_stdiciar_name',
                DB::raw('GROUP_CONCAT(mi.images) as images')
            )
            ->where('oi.order_id', $orderId)
            ->groupBy(
                'm.media_title',
                'm.width',
                'm.height',
                'oi.price',
                'oi.qty',
                'a.common_stdiciar_name',
            )
            ->get();
    }

    // public function getPaidCampaignInvoices($userId)
    // {
    //     return DB::table('orders as o')
    //         ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
    //         ->join('media_management as m', 'm.id', '=', 'oi.media_id')
    //         ->leftJoin('category as cat', 'cat.id', '=', 'm.category_id')
    //         ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
    //         ->leftJoin('campaign as c', 'c.id', '=', 'oi.campaign_id')
    //         ->where('o.user_id', $userId)
    //         ->where('o.payment_status', 'PAID')
    //         ->select(
    //             'o.id as order_id',
    //             'o.order_no',
    //             'o.total_amount',
    //             'o.payment_status',
    //             'o.payment_id',
    //             'o.created_at',

    //             // 🔹 campaign
    //             'c.campaign_name',

    //             // 🔹 media
    //             'm.media_title',
    //             'm.media_code',

    //             // 🔹 category
    //             'cat.category_name',

    //             // 🔹 area
    //             'a.area_name'
    //         )
    //         ->orderBy('o.id', 'DESC')
    //         ->get();
    // }
}

<?php

namespace App\Http\Repository\Website;

use Illuminate\Support\Facades\DB;
use App\Models\Cart;

class CampaignRepository
{
    public function updateCartCampaign($userId, $campaignName)
    {
        $cart = Cart::where('is_active', '1')
            ->where(function ($q) use ($userId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                } else {
                    $q->where('session_id', session()->getId());
                }
            })
            ->first();

        if (!$cart) {
            throw new \Exception('Active cart not found');
        }

        $cart->campaign_name = $campaignName;

        // 🔥 attach user if logged in
        if ($userId) {
            $cart->user_id = $userId;
        }

        $cart->save(); // ✅ FORCE SAVE

        return $cart;
    }


    public function getCampaignList($userId)
    {
        return DB::table('carts as c')
            ->join('cart_items as ci', 'ci.cart_id', '=', 'c.id')
            ->join('media_management as m', 'm.id', '=', 'ci.media_id')
            ->select(
                'c.id as cart_id',
                'c.campaign_name',
                'c.created_at as campaign_date',

                'ci.price',
                'ci.qty',

                'm.media_title',
                'm.width',
                'm.height'
            )
            ->where('c.is_active', 1)
            ->where(function ($q) use ($userId) {
                if ($userId) {
                    $q->where('c.user_id', $userId);
                } else {
                    $q->where('c.session_id', session()->getId());
                }
            })
            ->orderBy('c.id', 'DESC')
            ->get();
    }
}

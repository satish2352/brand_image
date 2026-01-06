<?php

namespace App\Http\Repository\Superadm;

use Illuminate\Support\Facades\DB;

class CampaingRepository
{
    public function list()
    {
        return DB::table('campaign as c')
            ->leftJoin('cart_items as ci', 'ci.campaign_id', '=', 'c.id')
            ->leftJoin('media_management as m', 'm.id', '=', 'ci.media_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->where('ci.cart_type', 'CAMPAIGN')
            ->where('ci.status', 'ACTIVE')
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
            ->get();
    }


    public function delete($id)
    {
        return DB::table('website_users')
            ->where('id', $id)
            ->update(['is_deleted' => 1]);
    }
    public function toggleStatus($id)
    {
        $user = DB::table('website_users')->where('id', $id)->first();

        return DB::table('website_users')
            ->where('id', $id)
            ->update([
                'is_active' => $user->is_active ? 0 : 1
            ]);
    }
}

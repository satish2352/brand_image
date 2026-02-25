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
    public function getCampaignByUserForAdmin($userId)
    {
        $items = DB::table('campaign as c')
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
                'ci.per_day_price',
                'ci.qty',
                'ci.price',
                'ci.total_days',
                'ci.total_price',
                'm.media_title',
                'm.width',
                'm.height',
                'm.facing',
                'a.area_name',
                'a.common_stdiciar_name'
            )
            ->orderBy('c.id', 'DESC')
            ->get();
        foreach ($items as $item) {

            // booking by SAME USER
            $bookedByMe = DB::table('orders as o')
                ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
                ->where('oi.media_id', $item->media_id)
                ->where('o.user_id', $item->user_id)
                ->whereIn('o.payment_status', ['PAID', 'ADMIN_BOOKED'])
                ->where('o.is_deleted', 0)
                ->where(function ($q) use ($item) {
                    $q->whereBetween('oi.from_date', [$item->from_date, $item->to_date])
                        ->orWhereBetween('oi.to_date', [$item->from_date, $item->to_date])
                        ->orWhereRaw("'{$item->from_date}' BETWEEN oi.from_date AND oi.to_date")
                        ->orWhereRaw("'{$item->to_date}' BETWEEN oi.from_date AND oi.to_date");
                })
                ->exists();


            // booking by OTHER USER
            $bookedByOther = DB::table('orders as o')
                ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
                ->where('oi.media_id', $item->media_id)
                ->where('o.user_id', '!=', $item->user_id)
                ->whereIn('o.payment_status', ['PAID', 'ADMIN_BOOKED'])
                ->where('o.is_deleted', 0)
                ->where(function ($q) use ($item) {
                    $q->whereBetween('oi.from_date', [$item->from_date, $item->to_date])
                        ->orWhereBetween('oi.to_date', [$item->from_date, $item->to_date])
                        ->orWhereRaw("'{$item->from_date}' BETWEEN oi.from_date AND oi.to_date")
                        ->orWhereRaw("'{$item->to_date}' BETWEEN oi.from_date AND oi.to_date");
                })
                ->exists();

            $item->booked_by_me = $bookedByMe;
            $item->booked_by_other = $bookedByOther;
        }
        // foreach ($items as $item) {
        //     $item->is_booked = DB::table('media_booked_date')
        //         ->where('media_id', $item->media_id)
        //         ->where('is_deleted', 0)
        //         ->where(function ($q) use ($item) {
        //             $q->whereBetween('from_date', [$item->from_date, $item->to_date])
        //                 ->orWhereBetween('to_date', [$item->from_date, $item->to_date])
        //                 ->orWhereRaw("'{$item->from_date}' BETWEEN from_date AND to_date")
        //                 ->orWhereRaw("'{$item->to_date}' BETWEEN from_date AND to_date");
        //         })
        //         ->exists();
        // }

        return $items;
    }
}

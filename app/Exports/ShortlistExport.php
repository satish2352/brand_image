<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;

/**
 * The campaign quotation sheet for a shortlist ticked on /search or the Map.
 *
 * Same columns, totals, colours and terms as CampaignExport. A shortlist has
 * no booking dates, so each site is quoted for one month at its listed price.
 */
class ShortlistExport extends CampaignExport
{
    private const QUOTE_DAYS = 30;

    /** @var int[] */
    protected array $mediaIds;

    public function __construct(array $mediaIds)
    {
        parent::__construct(0, 0);

        $this->mediaIds = array_values(array_map('intval', $mediaIds));
    }

    public function collection()
    {
        $rows = DB::table('media_management as m')
            ->leftJoin('areas as ar', 'ar.id', '=', 'm.area_id')
            ->leftJoin('districts as d', 'd.id', '=', 'ar.district_id')
            ->leftJoin('cities as ct', 'ct.id', '=', 'ar.city_id')
            ->leftJoin('highway as hw', 'hw.id', '=', 'm.highway_id')
            ->leftJoin('vendors as v', 'v.id', '=', 'm.vendor_id')
            ->whereIn('m.id', $this->mediaIds)
            ->select(
                'm.id as media_id',
                'd.district_name',
                'ct.city_name',
                'ar.area_name',
                'm.media_code',
                'm.hoarding_code',
                'hw.highway_name',
                'm.width',
                'm.height',
                'm.area_auto',
                'm.price as monthly_price',
                'm.is_available',
                'v.vendor_name',
                DB::raw('NULL as from_date'),
                DB::raw('NULL as to_date'),
                DB::raw('ROUND(m.price / ' . self::QUOTE_DAYS . ', 2) as per_day_price'),
                DB::raw(self::QUOTE_DAYS . ' as total_days'),
                'm.price as total_price',
                DB::raw('(SELECT GROUP_CONCAT(l.landmark_name SEPARATOR ", ") FROM media_landmark ml JOIN landmark l ON l.id = ml.landmark_id WHERE ml.media_id = m.id AND l.is_deleted = 0) as landmark_names'),
                DB::raw('(SELECT MAX(mbd.to_date) FROM media_booked_date mbd WHERE mbd.media_id = m.id AND mbd.is_deleted = 0 AND mbd.is_active = 1 AND mbd.to_date >= CURDATE()) as booked_until')
            )
            ->get();

        // In the order the team ticked them.
        $order = array_flip($this->mediaIds);

        return $rows->sortBy(fn ($row) => $order[$row->media_id] ?? PHP_INT_MAX)->values();
    }
}

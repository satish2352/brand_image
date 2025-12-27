<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CampaignExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize
{
    protected $userId;
    protected $campaignId;
    protected $srNo = 0;

    public function __construct($userId, $campaignId)
    {
        $this->userId = $userId;
        $this->campaignId = $campaignId;
    }

    public function collection()
    {
        return DB::table('cart_items as ci')
            ->join('campaign as c', 'c.id', '=', 'ci.campaign_id')
            ->join('media_management as m', 'm.id', '=', 'ci.media_id')
            ->leftJoin('areas as ar', 'ar.id', '=', 'm.area_id')
            ->leftJoin('tbl_location as state', 'state.location_id', '=', 'ar.state_id')
            ->leftJoin('tbl_location as district', 'district.location_id', '=', 'ar.district_id')
            ->leftJoin('tbl_location as city', 'city.location_id', '=', 'ar.city_id')

            // 🔥 CRITICAL FILTERS
            ->where('c.user_id', $this->userId)
            ->where('ci.campaign_id', $this->campaignId)
            ->where('ci.is_active', 1)
            ->where('ci.is_deleted', 0)

            ->select(
                'c.campaign_name',
                'state.name as state_name',
                'district.name as district_name',
                'city.name as city_name',
                'ar.area_name',
                'm.media_code',
                'm.address',
                'm.width',
                'm.height',
                'ci.price',
                'ci.qty'
            )
            ->get();
    }

    public function map($row): array
    {
        $this->srNo++;

        $sqft = $row->width * $row->height;
        $perMonth = $sqft * 30;
        $printing = $perMonth / 3;
        $amount = $printing * 0.8;
        $total = $amount * $row->qty;

        return [
            $this->srNo,
            $row->campaign_name,
            $row->state_name,
            $row->district_name,
            $row->city_name,
            $row->area_name,
            $row->media_code,
            $row->address,
            $row->width,
            $row->height,
            $sqft,
            round($perMonth, 2),
            round($printing, 2),
            round($amount, 2),
            round($total, 2),
        ];
    }

    public function headings(): array
    {
        return [
            'Sr No',
            'Campaign Name',
            'State',
            'District',
            'City',
            'Area',
            'Media Code',
            'Location',
            'Width',
            'Height',
            'Total Sqft',
            'Per Month',
            'Printing Amount',
            'Amount',
            'Total Amount',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'FFD966'],
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                ],
            ],
        ];
    }
}

<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AdminCampaignExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize
{
    /**
     * Campaign id
     */
    protected int $campaignId;

    /**
     * Serial number
     */
    protected int $srNo = 0;

    /**
     * Constructor
     */
    public function __construct(int $campaignId)
    {
        $this->campaignId = $campaignId;
    }

    /**
     * Fetch campaign data (ADMIN – no user filter)
     */
    public function collection()
    {
        return DB::table('cart_items as ci')
            ->join('campaign as c', 'c.id', '=', 'ci.campaign_id')
            ->join('website_users as u', 'u.id', '=', 'c.user_id')
            ->join('media_management as m', 'm.id', '=', 'ci.media_id')
            ->leftJoin('areas as ar', 'ar.id', '=', 'm.area_id')
            ->leftJoin('tbl_location as district', 'district.location_id', '=', 'ar.district_id')
            ->leftJoin('tbl_location as city', 'city.location_id', '=', 'ar.city_id')
            ->where('ci.campaign_id', $this->campaignId)
            ->where('ci.is_active', 1)
            ->where('ci.is_deleted', 0)
            ->select(
                'u.name as user_name',
                'district.name as district_name',
                'city.name as city_name',
                'm.media_code',
                'ar.area_name',
                'm.width',
                'm.height',
                'm.price as monthly_price',
                'ci.per_day_price',
                'ci.total_days',
                'ci.total_price'
            )
            ->orderBy('ci.id')
            ->get();
    }

    /**
     * Map row
     */
    public function map($row): array
    {
        $this->srNo++;

        $totalSqft = ($row->width ?? 0) * ($row->height ?? 0);

        return [
            $this->srNo,
            $row->user_name ?? '-',                     // User Name
            $row->district_name ?? '-',
            $row->city_name ?? '-',
            $row->media_code ?? '-',
            $row->area_name ?? '-',
            $row->width ?? 0,
            $row->height ?? 0,
            $totalSqft,
            number_format($row->monthly_price, 2),
            number_format($row->per_day_price, 2),
            $row->total_days ?? 0,
            number_format($row->total_price, 2),
            number_format($row->total_price, 2),
        ];
    }

    /**
     * Headings
     */
    public function headings(): array
    {
        return [
            'Sr No',
            'User Name',
            'District',
            'Town',
            'Site Code',
            'Location',
            'Width',
            'Height',
            'Total Sqft',
            'Monthly Price (₹)',
            'Per Day Price (₹)',
            'Total Days',
            'Amount (₹)',
            'Total Amount (₹)',
        ];
    }

    /**
     * Styles
     */
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
                    'vertical'   => 'center',
                ],
            ],
        ];
    }
}

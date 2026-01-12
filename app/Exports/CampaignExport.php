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
    /**
     * Logged in user id
     */
    protected int $userId;

    /**
     * Campaign id
     */
    protected int $campaignId;

    /**
     * Serial number counter
     */
    protected int $srNo = 0;

    /**
     * Constructor
     */
    public function __construct(int $userId, int $campaignId)
    {
        $this->userId     = $userId;
        $this->campaignId = $campaignId;
    }

    /**
     * Fetch campaign cart items from database
     */
    public function collection()
    {
        return DB::table('cart_items as ci')
            ->join('campaign as c', 'c.id', '=', 'ci.campaign_id')
            ->join('media_management as m', 'm.id', '=', 'ci.media_id')
            ->leftJoin('areas as ar', 'ar.id', '=', 'm.area_id')
            ->leftJoin('districts as d', 'd.id', '=', 'ar.district_id')
            ->leftJoin('cities as ct', 'ct.id', '=', 'ar.city_id')
            ->leftJoin('states as s', 's.id', '=', 'ar.state_id')
            ->where('c.user_id', $this->userId)
            ->where('ci.campaign_id', $this->campaignId)
            ->where('ci.is_active', 1)
            ->where('ci.is_deleted', 0)
            ->select(
                // 'district.name as district_name',
                // 'city.name as city_name',
                'd.district_name as district_name',
                'ct.city_name as city_name',
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
     * Map each row to Excel columns
     */
    public function map($row): array
    {
        $this->srNo++;

        $totalSqft = ($row->width ?? 0) * ($row->height ?? 0);

        return [
            $this->srNo,                                  // Sr No
            $row->district_name ?? '-',                   // District
            $row->city_name ?? '-',                       // Town
            $row->media_code ?? '-',                      // Site Code
            $row->area_name ?? '-',                       // Location
            $row->width ?? 0,                             // Width
            $row->height ?? 0,                            // Height
            $totalSqft,                                   // Total Sqft
            number_format($row->monthly_price, 2),        // Monthly Price
            number_format($row->per_day_price, 2),        // Per Day Price
            $row->total_days ?? 0,                        // Total Days
            number_format($row->total_price, 2),          // Amount
            number_format($row->total_price, 2),          // Total Amount
        ];
    }

    /**
     * Excel column headings
     */
    public function headings(): array
    {
        return [
            'Sr No',
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
     * Apply Excel styles
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [ // Header row
                'font' => [
                    'bold' => true,
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => [
                        'rgb' => 'FFD966',
                    ],
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical'   => 'center',
                ],
            ],
        ];
    }
}

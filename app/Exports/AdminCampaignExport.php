<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AdminCampaignExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize,
    WithEvents
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
     * Grand total accumulator
     */
    protected float $grandTotal = 0;

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
            ->leftJoin('states as s', 's.id', '=', 'ar.state_id')
            ->leftJoin('districts as d', 'd.id', '=', 'ar.district_id')
            ->leftJoin('cities as ct', 'ct.id', '=', 'ar.city_id')
            ->leftJoin('highway as hw', 'hw.id', '=', 'm.highway_id')
            ->where('ci.campaign_id', $this->campaignId)
            ->where('ci.is_active', 1)
            ->where('ci.is_deleted', 0)
            ->select(
                'u.name as user_name',
                // 'district.name as district_name',
                // 'city.name as city_name',
                'd.district_name as district_name',
                'ct.city_name as city_name',
                'm.media_code',
                'm.hoarding_code',
                'hw.highway_name',
                DB::raw('(SELECT GROUP_CONCAT(l.landmark_name SEPARATOR ", ") FROM media_landmark ml JOIN landmark l ON l.id = ml.landmark_id WHERE ml.media_id = m.id AND l.is_deleted = 0) as landmark_names'),
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

        $this->grandTotal += $row->total_price ?? 0;

        return [
            $this->srNo,
            $row->user_name ?? '-',                     // User Name
            $row->district_name ?? '-',
            $row->city_name ?? '-',
            $row->media_code ?? '-',
            $row->hoarding_code ?? '-',
            $row->area_name ?? '-',
            $row->highway_name ?? '-',
            $row->landmark_names ?? '-',
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
            'Hoarding Code',
            'Location',
            'Highway',
            'Landmarks',
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

    /**
     * Append grand total row after all data rows
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $totalRow = $this->srNo + 2; // row 1 = heading, rows 2..N = data

                // Label spanning columns A–O; totals in P (Amount) and Q (Total Amount)
                $sheet->mergeCells('A' . $totalRow . ':O' . $totalRow);
                $sheet->setCellValue('A' . $totalRow, 'Grand Total');
                $sheet->setCellValue('P' . $totalRow, number_format($this->grandTotal, 2));
                $sheet->setCellValue('Q' . $totalRow, number_format($this->grandTotal, 2));

                $sheet->getStyle('A' . $totalRow . ':Q' . $totalRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType'   => 'solid',
                        'startColor' => ['rgb' => 'FFD966'],
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical'   => 'center',
                    ],
                ]);
            },
        ];
    }
}

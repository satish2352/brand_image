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

class CampaignExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize,
    WithEvents
{
    protected int $userId;
    protected int $campaignId;
    protected int $srNo = 0;

    // Grand total accumulator
    protected float $grandTotal = 0;


    protected float $grandGst   = 0;   // ADD THIS
    protected float $grandFinal = 0;   // ADD THIS


    public function __construct(int $userId, int $campaignId)
    {
        $this->userId     = $userId;
        $this->campaignId = $campaignId;
    }

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
    public function map($row): array
    {
        $this->srNo++;

        $totalSqft = ($row->width ?? 0) * ($row->height ?? 0);

        $amount = $row->total_price ?? 0;
        $gst    = round($amount * 0.18, 2);
        $final  = $amount + $gst;

        // accumulate totals
        $this->grandTotal += $amount;
        $this->grandGst   += $gst;
        $this->grandFinal += $final;

        return [
            $this->srNo,
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
            number_format($amount, 2),
            number_format($gst, 2),     // NEW
            number_format($final, 2),   // NEW
        ];
    }
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
            // 'Amount (₹)',
            'Total Amount (₹)',
            'GST 18% (₹)',        // NEW
            'Final Amount (₹)',   // NEW
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
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
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $lastDataRow = $this->srNo + 1;
                $totalRow    = $lastDataRow + 1;

                $event->sheet->setCellValue('A' . $totalRow, 'GRAND TOTAL');

                // merge till Total Days column
                $event->sheet->mergeCells("A{$totalRow}:K{$totalRow}");

                // totals
                $event->sheet->setCellValue('L' . $totalRow, number_format($this->grandTotal, 2));
                $event->sheet->setCellValue('M' . $totalRow, number_format($this->grandGst, 2));
                $event->sheet->setCellValue('N' . $totalRow, number_format($this->grandFinal, 2));

                $event->sheet->getStyle("A{$totalRow}:N{$totalRow}")
                    ->applyFromArray([
                        'font' => ['bold' => true],
                    ]);
            },
        ];
    }
}

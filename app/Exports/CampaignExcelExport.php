<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class CampaignExcelExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function collection()
    {
        $sr = 1;

        return DB::table('cart_items as ci')
            ->join('campaign as c', 'c.id', '=', 'ci.campaign_id')
            ->join('media_management as m', 'm.id', '=', 'ci.media_id')
            ->join('areas as a', 'a.id', '=', 'm.area_id')
            ->join('cities as ci2', 'ci2.id', '=', 'm.city_id')
            ->join('districts as d', 'd.id', '=', 'm.district_id')
            ->join('states as s', 's.id', '=', 'm.state_id')
            ->where('c.user_id', $this->userId)
            ->select(
                's.state_name',
                'd.district_name',
                'ci2.city_name',
                'm.media_code',
                'a.area_name',
                'm.width',
                'm.height',
                'ci.price'
            )
            ->get()
            ->map(function ($row) use (&$sr) {

                $sqft = $row->width * $row->height;
                $monthly = $sqft * 30;
                $printing = $monthly / 3;
                $amount = $printing * 0.8;
                $total = $monthly + $printing + $amount;

                return [
                    $row->state_name,
                    $row->district_name,
                    $row->city_name,
                    $row->media_code,
                    $row->area_name,
                    $row->width,
                    $row->height,
                    $sqft,
                    $monthly,
                    $printing,
                    $amount,
                    $total,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'State',
            'District',
            'City',
            'Media Code',
            'Location',
            'Width',
            'Height',
            'Total Sqft',
            '/ Month',
            'Printing Amount',
            'Amount',
            'Total Amount'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
            ]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastColumn = $sheet->getHighestColumn();

                // Header background (Yellow)
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFD966']
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                        ]
                    ]
                ]);

                // Table Borders
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // Auto size
                foreach (range('A', $lastColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Total row
                $totalRow = $lastRow + 2;
                $sheet->setCellValue("K{$totalRow}", 'Total Amount');
                $sheet->setCellValue("L{$totalRow}", "=SUM(L2:L{$lastRow})");

                $sheet->getStyle("K{$totalRow}:L{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                ]);
            }
        ];
    }
}

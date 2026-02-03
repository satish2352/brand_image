<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MediaUtilisationExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data->map(function ($row) {
            return [
                $row->user_name,
                $row->media_code,
                $row->media_title,
                $row->category_name,
                "{$row->width} x {$row->height}",
                $row->from_date,
                $row->to_date,
                $row->booked_days,
                $row->total_amount,
                $row->gst_amount,
                $row->grand_total,
                
            ];
        });
    }

    public function headings(): array
    {
        return [
            'User Name',
            'Media Code',
            'Media Title',
            'Category',
            'Size (WxH)',
            'From Date',
            'To Date',
            'Booked Days',
            'Amount (₹)',
            'GST (18%) (₹)',
             'Final Total (₹)'
            
        ];
    }

    /**
     * 🎨 EXCEL STYLING
     */
    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // HEADER STYLE
        $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD'], // Blue header
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical'   => 'center',
            ],
        ]);

        // BORDER FOR ENTIRE TABLE
        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        return [];
    }
}

<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    WithStyles,
    ShouldAutoSize
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RevenueExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected Collection $data;
    protected string $type;

    public function __construct(Collection $data, string $type)
    {
        $this->data = $data;
        $this->type = $type;
    }

    /**
     * DATA ROWS
     */
    public function collection()
    {
        return $this->data->values()->map(function ($row, $index) {

            /* ========= MEDIA-WISE ========= */
            if ($this->type === 'media') {
                return [
                    $index + 1,
                    $row->media_code,
                    $row->category_name,
                    $row->media_title,
                    $row->state_name,
                    $row->district_name,
                    $row->city_name,
                    $row->area_name,
                    $row->width,
                    $row->height,
                    $row->booking_type,
                    // $row->total_bookings,
                    $row->booked_days,
                    round($row->total_amount, 2),
                    round($row->gst_amount, 2),
                    round($row->grand_total, 2),
                ];
            }

            /* ========= DATE-WISE ========= */
            if ($this->type === 'date') {
                if ($this->type === 'date') {
                    return [
                        $index + 1,
                        $row->period,
                        $row->booking_type,
                        round($row->total_amount, 2),
                        round($row->gst_amount, 2),
                        round($row->grand_total, 2),
                    ];
                }
            }

            /* ========= USER-WISE ========= */
            return [
                $index + 1,
                $row->user_name,
                $row->booking_type,
                $row->booked_days,
                round($row->total_amount, 2),
                round($row->gst_amount, 2),
                round($row->grand_total, 2),
            ];
        });
    }

    /**
     * HEADINGS
     */
    public function headings(): array
    {
        if ($this->data->isEmpty()) {
            return [];
        }

        if ($this->type === 'date') {
            return [
                'Sr. No',
                'Period',
                'Booking Type',
                // 'Total Bookings',
                'Amount (₹)',
                'GST (₹)',
                'Final Total (₹)',
            ];
        }

        if ($this->type === 'media') {
            return [
                'Sr. No',
                'Media Code',
                'Category',
                'Media Title',
                'State',
                'District',
                'City',
                'Area',
                'Width',
                'Height',
                'Booking Type',
                // 'Total Bookings',
                'Booked Days',
                'Amount (₹)',
                'GST (₹)',
                'Final Total (₹)',
            ];
        }

        // USER-WISE
        return [
            'Sr. No',
            'User Name',
            'Booking Type',
            // 'Total Bookings',
            'Booked Days',
            'Amount (₹)',
            'GST (₹)',
            'Final Total (₹)',
        ];
    }

    /**
     * STYLES
     */
    public function styles(Worksheet $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow    = $sheet->getHighestRow();

        // Header style
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => '2F75B5'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => 'thin'],
            ],
        ]);

        // Body borders
        if ($lastRow > 1) {
            $sheet->getStyle("A2:{$lastColumn}{$lastRow}")
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle('thin');
        }
    }
}

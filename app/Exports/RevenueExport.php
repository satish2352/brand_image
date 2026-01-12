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

    // public function collection()
    // {
    //     return $this->data->map(function ($row) {
    //         return (array) $row;
    //     });
    // }

    public function collection()
    {
        return $this->data->values()->map(function ($row, $index) {
            return array_merge(
                ['sr_no' => $index + 1],
                (array) $row
            );
        });
    }

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
                'Total Bookings',
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
                'Total Bookings',
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
            'Total Bookings',
            'Booked Days',
            'Amount (₹)',
            'GST (₹)',
            'Final Total (₹)',
        ];
    }

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

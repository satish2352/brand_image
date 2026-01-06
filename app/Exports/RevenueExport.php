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

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data->map(function ($row) {
            return (array) $row;
        });
    }

    public function headings(): array
    {
        if ($this->data->isEmpty()) {
            return [];
        }

        // Custom readable headings
        $map = [
            'period'         => 'Period',
            'media_code'     => 'Media Code',
            'category_name'  => 'Category',
            'media_title'    => 'Media Title',
            'state_name'     => 'State',
            'district_name'  => 'District',
            'city_name'      => 'City',
            'area_name'      => 'Area',
            'width'          => 'Width',
            'height'         => 'Height',
            'total_bookings' => 'Total Bookings',
            'booked_days'    => 'Booked Days',
            'total_revenue'  => 'Total Revenue (₹)',
        ];

        $firstRow = (array) $this->data->first();

        return array_map(
            fn ($key) => $map[$key] ?? ucfirst(str_replace('_', ' ', $key)),
            array_keys($firstRow)
        );
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

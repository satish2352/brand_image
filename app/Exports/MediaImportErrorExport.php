<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Downloadable error log for a rejected bulk upload: the team fixes the rows
 * listed here and re-uploads only those.
 */
class MediaImportErrorExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    protected array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
    }

    public function array(): array
    {
        $serial = 0;

        return array_map(function ($error) use (&$serial) {
            return [
                ++$serial,
                $error['row'],
                ($error['hoarding_code'] ?? '') ?: '-',
                // Filled when the row was rejected as a duplicate: the code of
                // the record already in the inventory that it clashes with.
                ($error['existing_code'] ?? '') ?: '-',
                ($error['media_title'] ?? '') ?: '-',
                $error['issues'],
            ];
        }, $this->errors);
    }

    public function headings(): array
    {
        return [
            'Sr No.',
            'Sheet Row No.',
            'Hoarding Code',
            'Already In Inventory As',
            'Media Title',
            'Problem(s) Found',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'C0392B'],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
        ]);

        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // Sr No. and Sheet Row No. read better centred; the problem text is the
        // only column long enough to need wrapping.
        $sheet->getStyle("A2:B{$highestRow}")
            ->getAlignment()
            ->setHorizontal('center');

        $sheet->getStyle("F2:F{$highestRow}")
            ->getAlignment()
            ->setWrapText(true);

        $sheet->getColumnDimension('F')->setAutoSize(false);
        $sheet->getColumnDimension('F')->setWidth(70);

        $sheet->freezePane('A2');

        return [];
    }
}

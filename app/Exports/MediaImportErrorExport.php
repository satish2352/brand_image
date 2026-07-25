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
        return array_map(function ($error) {
            return [
                $error['row'],
                $error['hoarding_code'] ?: '-',
                $error['media_title'] ?: '-',
                $error['issues'],
            ];
        }, $this->errors);
    }

    public function headings(): array
    {
        return [
            'Sheet Row No.',
            'Hoarding Code',
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

        $sheet->getStyle("D2:D{$highestRow}")
            ->getAlignment()
            ->setWrapText(true);

        return [];
    }
}

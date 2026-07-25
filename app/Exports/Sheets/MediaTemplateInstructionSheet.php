<?php

namespace App\Exports\Sheets;

use App\Support\MediaImportSchema;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet 2 of the sample template: what each column means and whether it is
 * mandatory. Generated from MediaImportSchema so it can never go stale.
 */
class MediaTemplateInstructionSheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function array(): array
    {
        return array_map(function ($column) {
            return [
                $column['label'],
                $column['required'] ? 'Mandatory' : 'Optional',
                ucfirst($column['type']),
                $column['help'],
            ];
        }, MediaImportSchema::COLUMNS);
    }

    public function headings(): array
    {
        return ['Column', 'Mandatory?', 'Value Type', 'How To Fill'];
    }

    public function title(): string
    {
        return 'Instructions';
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
                'startColor' => ['rgb' => '4F81BD'],
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

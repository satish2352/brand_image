<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet 3 of the sample template: the exact master values the importer will
 * accept, so the team can copy names instead of guessing spellings.
 */
class MediaTemplateMasterSheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $reference;

    public function __construct(array $reference)
    {
        $this->reference = $reference;
    }

    public function array(): array
    {
        return $this->reference;
    }

    public function headings(): array
    {
        return ['Master', 'Value', 'Belongs To'];
    }

    public function title(): string
    {
        return 'Master Reference';
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

        $sheet->freezePane('A2');

        return [];
    }
}

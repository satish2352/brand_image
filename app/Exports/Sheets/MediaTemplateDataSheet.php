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
 * Sheet 1 of the sample template: the headers the importer reads, plus two
 * filled example rows the team can overwrite.
 */
class MediaTemplateDataSheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $rows;

    protected array $columns;

    /**
     * @param array $rows    example rows built from the live masters; the schema's
     *                       static illustrations are used when none are supplied.
     * @param array $columns column subset for a category template; empty means
     *                       every column.
     */
    public function __construct(array $rows = [], array $columns = [])
    {
        $this->rows = $rows;
        $this->columns = $columns;
    }

    /**
     * The columns this sheet renders — a category subset, or all of them.
     *
     * @return array<int,array>
     */
    protected function columns(): array
    {
        return $this->columns ?: MediaImportSchema::COLUMNS;
    }

    public function array(): array
    {
        return $this->rows ?: MediaImportSchema::sampleRows($this->columns());
    }

    public function headings(): array
    {
        return MediaImportSchema::labels($this->columns());
    }

    public function title(): string
    {
        return 'Media Data';
    }

    public function styles(Worksheet $sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD'],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
        ]);

        // Mandatory headers get a distinct colour so they are obvious at a
        // glance; every header carries its how-to-fill note as a cell comment,
        // so the format is on hand even when the example cell is empty.
        $columnIndex = 1;
        foreach ($this->columns() as $column) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);

            if ($column['required']) {
                $sheet->getStyle("{$letter}1")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'C0392B'],
                    ],
                ]);
            }

            $comment = $sheet->getComment("{$letter}1");
            $comment->setWidth('320px');
            $comment->getText()->createTextRun(
                ($column['required'] ? 'Mandatory. ' : 'Optional. ') . $column['help']
            );

            $columnIndex++;
        }

        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->freezePane('A2');

        // Keep codes and coordinates as text so Excel does not reformat them.
        foreach (['Hoarding Code', 'Media Code', 'Latitude', 'Longitude'] as $label) {
            $index = array_search($label, MediaImportSchema::labels($this->columns()), true);
            if ($index !== false) {
                $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                $sheet->getStyle("{$letter}2:{$letter}500")
                    ->getNumberFormat()
                    ->setFormatCode('@');
            }
        }

        return [];
    }
}

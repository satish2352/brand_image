<?php

namespace App\Exports;

use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * A table exported from the vendor panel, headed with the vendor's company
 * name and "Powered by Brand Adda" (PRD 2.9 branding).
 */
class VendorBrandedExport implements FromArray, WithEvents, ShouldAutoSize, WithTitle
{
    public function __construct(
        private Vendor $vendor,
        private string $title,
        private array $headings,
        private array $rows
    ) {
    }

    public function array(): array
    {
        return array_merge([
            [$this->vendor->vendor_name],
            [$this->title . ' · Generated ' . now()->format('d M Y, h:i A') . ' · Powered by Brand Adda'],
            [],
            $this->headings,
        ], $this->rows);
    }

    public function title(): string
    {
        return substr(preg_replace('/[^A-Za-z0-9 ]/', '', $this->title), 0, 31) ?: 'Sheet1';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = Coordinate::stringFromColumnIndex(max(1, count($this->headings)));

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setARGB('FF475569');

                // The empty spacer row is not written, so the headings do not sit at a fixed
                // row: find them. (A fixed "row 4" coloured the first data row instead.)
                $headRow = 4;
                for ($r = 1; $r <= 6; $r++) {
                    if ($sheet->getCell("A{$r}")->getValue() === ($this->headings[0] ?? null)) {
                        $headRow = $r;
                        break;
                    }
                }

                // Heading row only: bold white text on the dark brand background.
                $head = $sheet->getStyle("A{$headRow}:{$lastCol}{$headRow}");
                $head->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $head->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F172A');

                // Data rows: plain, no background.
                $lastRow = $sheet->getHighestRow();
                if ($lastRow > $headRow) {
                    $data = $sheet->getStyle('A' . ($headRow + 1) . ":{$lastCol}{$lastRow}");
                    $data->getFont()->setBold(false)->getColor()->setARGB('FF000000');
                    $data->getFill()->setFillType(Fill::FILL_NONE);
                }
                $sheet->freezePane('A' . ($headRow + 1)); // headings stay visible while scrolling
            },
        ];
    }
}

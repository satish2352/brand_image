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
                $sheet->getStyle("A4:{$lastCol}4")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle("A4:{$lastCol}4")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF0F172A');
            },
        ];
    }
}

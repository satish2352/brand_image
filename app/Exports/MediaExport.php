<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Full outdoor media inventory export — location, commercial, GPS and media
 * specification details in one sheet.
 *
 * Uses FromQuery so the package chunks the result set instead of loading the
 * whole inventory into memory when the team exports the complete database.
 */
class MediaExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize, WithEvents
{
    /** Headings whose cells become clickable links to the picture they name. */
    private const LINK_COLUMNS = ['Image URLs', 'Panorama Image URL'];

    protected Builder $query;

    /** Running Sr.No across every chunk the package streams. */
    protected int $serial = 0;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function title(): string
    {
        return 'Media Inventory';
    }

    public function headings(): array
    {
        return [
            'Sr.No',
            'Hoarding Code',
            'Media Code',
            'Media Title',
            'Category',
            'Media Type',
            'State',
            'District',
            'City',
            'Area',
            'Address',
            'Vendor Name',
            'Vendor Code',
            'Width (ft)',
            'Height (ft)',
            'Total Area (Sq Ft)',
            'Illumination',
            'Facing',
            'Area Type',
            'Highway',
            'Landmarks',
            'Latitude',
            'Longitude',
            'Price (Monthly)',
            'Media Format',
            'Mall Name',
            'Airport Name',
            'Zone Type',
            'Transit Type',
            'Branding Type',
            'Vehicle Count',
            'Building Name',
            'Wall Length',
            'Total Images',
            'Image URLs',
            'Panorama Image URL',
            'Status',
            'Created On',
        ];
    }

    public function map($row): array
    {
        $this->serial++;

        $width = (float) $row->width;
        $height = (float) $row->height;
        $totalArea = $row->area_auto !== null && $row->area_auto !== ''
            ? $row->area_auto
            : round($width * $height, 2);

        return [
            $this->serial,
            $row->hoarding_code ?: '-',
            $row->media_code ?: '-',
            $row->media_title ?: '-',
            $row->category_name ?: '-',
            $row->media_type ?: '-',
            $row->state_name ?: '-',
            $row->district_name ?: '-',
            $row->city_name ?: '-',
            $row->area_name ?: '-',
            $row->address ?: '-',
            $row->vendor_name ?: '-',
            $row->vendor_code ?: '-',
            $width,
            $height,
            $totalArea,
            $row->illumination_name ?: '-',
            $row->facing ?: '-',
            $row->areatype_name ?: '-',
            $row->highway_name ?: '-',
            $row->landmark_names ?: '-',
            (string) $row->latitude,
            (string) $row->longitude,
            (float) $row->price,
            $row->media_format ?: '-',
            $row->mall_name ?: '-',
            $row->airport_name ?: '-',
            $row->zone_type ?: '-',
            $row->transit_type ?: '-',
            $row->branding_type ?: '-',
            $row->vehicle_count ?: '-',
            $row->building_name ?: '-',
            $row->wall_length ?: '-',
            (int) ($row->total_images ?? 0),
            $this->imageUrls($row->image_files ?? null),
            $this->imageUrls($row->panorama_image ?? null),
            $row->is_active ? 'Active' : 'Inactive',
            $row->created_at ? date('d-m-Y', strtotime($row->created_at)) : '-',
        ];
    }

    /**
     * Turn stored file names into public links, so the exported sheet feeds
     * straight back into the importer's Image URLs / Panorama Image URL columns.
     */
    private function imageUrls(?string $fileNames): string
    {
        $names = array_filter(array_map('trim', explode(',', (string) $fileNames)));

        if (empty($names)) {
            return '-';
        }

        $base = rtrim((string) config('fileConstants.IMAGE_VIEW'), '/') . '/';

        return implode(', ', array_map(fn ($name) => self::tidyUrl($base . $name), $names));
    }

    /**
     * Collapse the doubled slashes a configured base path ending in "/" leaves
     * behind, without touching the "https://" scheme.
     */
    private static function tidyUrl(string $url): string
    {
        return preg_replace('#(?<!:)//+#', '/', $url);
    }

    /**
     * The first link in a cell holding a comma separated list.
     */
    private static function firstUrl(?string $value): ?string
    {
        foreach (explode(',', (string) $value) as $part) {
            $part = trim($part);

            if ($part !== '' && preg_match('#^https?://#i', $part)) {
                return $part;
            }
        }

        return null;
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
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
        ]);

        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->freezePane('A2');

        return [];
    }

    /**
     * Make the picture columns clickable.
     *
     * The cell text is left exactly as it was written — the comma separated list
     * is what the importer reads back when an exported sheet is edited and
     * uploaded again — and a hyperlink is attached alongside it. Excel allows one
     * hyperlink per cell, so a cell naming several pictures opens the first; the
     * rest stay readable in the cell.
     *
     * Cells are read back from the sheet rather than remembered while mapping, so
     * exporting the whole inventory costs no extra memory.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                if ($lastRow < 2) {
                    return;
                }

                foreach ($this->linkColumnLetters($sheet) as $letter) {
                    for ($row = 2; $row <= $lastRow; $row++) {
                        $cell = $sheet->getCell($letter . $row);
                        $url = self::firstUrl((string) $cell->getValue());

                        if ($url === null) {
                            continue;
                        }

                        $cell->getHyperlink()->setUrl($url);
                        $cell->getHyperlink()->setTooltip('Open this image');
                    }

                    // Look like links, so it is obvious they can be clicked.
                    $sheet->getStyle("{$letter}2:{$letter}{$lastRow}")->applyFromArray([
                        'font' => [
                            'color' => ['rgb' => '0563C1'],
                            'underline' => true,
                        ],
                    ]);
                }
            },
        ];
    }

    /**
     * Column letters of LINK_COLUMNS, found by reading the header row so the
     * links follow the headings if the column order ever changes.
     *
     * @return array<int,string>
     */
    private function linkColumnLetters(Worksheet $sheet): array
    {
        $letters = [];
        $lastColumn = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($index = 1; $index <= $lastColumn; $index++) {
            $letter = Coordinate::stringFromColumnIndex($index);
            $heading = trim((string) $sheet->getCell($letter . '1')->getValue());

            if (in_array($heading, self::LINK_COLUMNS, true)) {
                $letters[] = $letter;
            }
        }

        return $letters;
    }
}

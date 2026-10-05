<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The campaign quotation sheet: one row per booked site, a grand total, then
 * the standing terms.
 */
class CampaignExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithEvents
{
    private const GST_RATE = 0.18;

    private const NAVY   = '1F3864';
    private const CYAN   = '00B0F0';   // vendor cell: site is free now
    private const YELLOW = 'FFFF00';   // vendor cell: site is not free now

    /** Last column of the table. */
    private const LAST_COL = 'S';

    /** Columns holding money, and the ones holding plain counts. */
    private const MONEY_COLS = ['L', 'M', 'O', 'P', 'Q'];
    private const COUNT_COLS = ['I', 'J', 'K'];

    /** Columns that read better centred — the rest stay left aligned. */
    private const CENTRE_COLS = ['A', 'B', 'C', 'D', 'E', 'F', 'I', 'J', 'K', 'N', 'R', 'S'];

    private const BUSINESS_TERMS = [
        '50% Payment to be made in Advance and balance with in 30 days from the date of Invoice.',
        'Any special photography/monitoring etc. will attract additional cost at actuals.',
        '18% GST shall be charged extra on Display & Mounting Charges.',
        '18% GST shall be charged extra on Printing Charges.',
        'Any Innovations being undertaken, charges will be applicable at actuals.',
    ];

    private const GENERAL_TERMS = [
        'All the sites are subject to availability at the time of final written confirmation from your side.',
        'Sites once booked cannot be cancelled/postponed.',
        'We will require the entire detail of site, rates, duration, mounting charges & taxes in the confirmation mail.',
        'Billing for all the sites will be from date of booking/availability & won\'t be postponed due to delay in supply of creatives or Purchase order (PO) from your side.',
        'We will not be responsible for the theft/damages of flexes if any.',
        'We will not hold the responsibility to keep the flexes after 5 days from the expiry of campaign.',
        'If the extension is not confirmed before 7 days from the expiry date we will treat it as no extension in the ongoing campaign.',
    ];

    protected int $userId;
    protected int $campaignId;
    protected int $srNo = 0;

    /** Column totals for the GRAND TOTAL row. */
    protected float $totalSqft    = 0;
    protected float $totalMonthly = 0;
    protected float $totalAmount  = 0;
    protected float $totalGst     = 0;
    protected float $totalFinal   = 0;

    /** Sheet row => availability status, so the vendor cell can be coloured. */
    protected array $statusByRow = [];

    public function __construct(int $userId, int $campaignId)
    {
        $this->userId     = $userId;
        $this->campaignId = $campaignId;
    }

    public function collection()
    {
        return DB::table('cart_items as ci')
            ->join('campaign as c', 'c.id', '=', 'ci.campaign_id')
            ->join('media_management as m', 'm.id', '=', 'ci.media_id')
            ->leftJoin('areas as ar', 'ar.id', '=', 'm.area_id')
            ->leftJoin('districts as d', 'd.id', '=', 'ar.district_id')
            ->leftJoin('cities as ct', 'ct.id', '=', 'ar.city_id')
            ->leftJoin('highway as hw', 'hw.id', '=', 'm.highway_id')
            ->leftJoin('vendors as v', 'v.id', '=', 'm.vendor_id')
            ->where('c.user_id', $this->userId)
            ->where('ci.campaign_id', $this->campaignId)
            ->where('ci.cart_type', 'CAMPAIGN')
            ->select(
                'd.district_name',
                'ct.city_name',
                'ar.area_name',
                'm.media_code',
                'm.hoarding_code',
                'hw.highway_name',
                'm.width',
                'm.height',
                'm.area_auto',
                'm.price as monthly_price',
                'm.is_available',
                'v.vendor_name',
                'ci.from_date',
                'ci.to_date',
                'ci.per_day_price',
                'ci.total_days',
                'ci.total_price',
                DB::raw('(SELECT GROUP_CONCAT(l.landmark_name SEPARATOR ", ") FROM media_landmark ml JOIN landmark l ON l.id = ml.landmark_id WHERE ml.media_id = m.id AND l.is_deleted = 0) as landmark_names'),
                // The furthest date this site is already committed to. Drives
                // the "From <date>" availability status.
                DB::raw('(SELECT MAX(mbd.to_date) FROM media_booked_date mbd WHERE mbd.media_id = m.id AND mbd.is_deleted = 0 AND mbd.is_active = 1 AND mbd.to_date >= CURDATE()) as booked_until')
            )
            ->orderBy('ci.id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Sr No',
            'District',
            'Town',
            'Site Code',
            'Hoarding Code',
            'Location',
            'Highway',
            'Landmarks',
            'Width',
            'Height',
            'Total Sqft',
            'Monthly Price (₹)',
            'Per Day Price (₹)',
            'Total Days',
            'Total Amount (₹)',
            'GST 18% (₹)',
            'Final Amount (₹)',
            'Availability Status',
            'Vendor',
        ];
    }

    public function map($row): array
    {
        $this->srNo++;

        $width  = (float) ($row->width ?? 0);
        $height = (float) ($row->height ?? 0);

        // Panelled media (a Bus Shelter) has no single face, so its area is the
        // total its panels add up to.
        $sqft = $width > 0 && $height > 0
            ? $width * $height
            : (float) ($row->area_auto ?? 0);

        $monthly = (float) ($row->monthly_price ?? 0);
        $amount  = (float) ($row->total_price ?? 0);
        $gst     = round($amount * self::GST_RATE, 2);
        $final   = $amount + $gst;

        $this->totalSqft    += $sqft;
        $this->totalMonthly += $monthly;
        $this->totalAmount  += $amount;
        $this->totalGst     += $gst;
        $this->totalFinal   += $final;

        $status = $this->availability($row);

        // +1 for the heading row.
        $this->statusByRow[$this->srNo + 1] = $status;

        return [
            $this->srNo,
            $row->district_name ?: '-',
            $row->city_name ?: '-',
            $row->media_code ?: '-',
            $row->hoarding_code ?: '-',
            $row->area_name ?: '-',
            $row->highway_name ?: '-',
            $row->landmark_names ?: '-',
            $width ?: '-',
            $height ?: '-',
            $sqft,
            $monthly,
            (float) ($row->per_day_price ?? 0),
            $this->durationInDays($row),
            $amount,
            $gst,
            $final,
            $status,
            $row->vendor_name ?: '-',
        ];
    }

    /**
     * When this site can go up: never while it is switched off, otherwise the
     * day after whatever it is already committed to, otherwise right now.
     */
    private function availability(object $row): string
    {
        if ((int) ($row->is_available ?? 1) === 0) {
            return 'Not Available';
        }

        if (!empty($row->booked_until)) {
            return 'From ' . Carbon::parse($row->booked_until)->addDay()->format('d M y');
        }

        return 'Immediate';
    }

    private function durationInDays(object $row): int
    {
        if (!empty($row->total_days)) {
            return (int) $row->total_days;
        }

        if (!empty($row->from_date) && !empty($row->to_date)) {
            return Carbon::parse($row->from_date)->diffInDays(Carbon::parse($row->to_date)) + 1;
        }

        return 0;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last  = self::LAST_COL;

                $firstDataRow = 2;
                $lastDataRow  = $this->srNo + 1;
                $totalRow     = $lastDataRow + 1;

                /* ---------- heading ---------- */
                $sheet->getStyle("A1:{$last}1")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => self::NAVY],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(42);

                /* ---------- grand total ---------- */
                $sheet->setCellValue("A{$totalRow}", 'GRAND TOTAL');
                $sheet->mergeCells("A{$totalRow}:J{$totalRow}");

                $sheet->setCellValue("K{$totalRow}", $this->totalSqft);
                $sheet->setCellValue("L{$totalRow}", $this->totalMonthly);
                $sheet->setCellValue("O{$totalRow}", $this->totalAmount);
                $sheet->setCellValue("P{$totalRow}", $this->totalGst);
                $sheet->setCellValue("Q{$totalRow}", $this->totalFinal);

                $sheet->getStyle("A{$totalRow}:{$last}{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => self::NAVY],
                    ],
                ]);

                /* ---------- numbers, borders, alignment ---------- */
                if ($this->srNo > 0) {
                    foreach (self::MONEY_COLS as $col) {
                        $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$totalRow}")
                            ->getNumberFormat()->setFormatCode('#,##0.00');
                    }

                    foreach (self::COUNT_COLS as $col) {
                        $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$totalRow}")
                            ->getNumberFormat()->setFormatCode('#,##0.##');
                    }

                    $sheet->getStyle("A{$firstDataRow}:{$last}{$lastDataRow}")
                        ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                    foreach (self::CENTRE_COLS as $col) {
                        $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$lastDataRow}")
                            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    $sheet->getStyle("A1:{$last}{$totalRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['rgb' => 'BFBFBF'],
                            ],
                        ],
                    ]);

                    /* ---------- vendor cell, flagged by availability ---------- */
                    foreach ($this->statusByRow as $rowNumber => $status) {
                        $sheet->getStyle("S{$rowNumber}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => [
                                'fillType'   => Fill::FILL_SOLID,
                                'startColor' => [
                                    'rgb' => $status === 'Immediate' ? self::CYAN : self::YELLOW,
                                ],
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'wrapText'   => true,
                            ],
                        ]);
                    }
                }

                /* ---------- terms ---------- */
                $this->writeTerms($sheet, $totalRow + 3);
            },
        ];
    }

    /** The two standing blocks beneath the table. */
    private function writeTerms(Worksheet $sheet, int $row): void
    {
        foreach ([
            'Business Terms:' => self::BUSINESS_TERMS,
            'General Terms:'  => self::GENERAL_TERMS,
        ] as $heading => $terms) {

            $sheet->setCellValue("B{$row}", $heading);
            $sheet->getStyle("B{$row}")->applyFromArray([
                'font' => ['bold' => true, 'underline' => true],
            ]);

            $row++;

            foreach ($terms as $index => $term) {
                $sheet->setCellValue('B' . $row, ($index + 1) . ')');
                $sheet->setCellValue('C' . $row, $term);
                $row++;
            }

            // A blank line between the two blocks.
            $row += 2;
        }
    }
}

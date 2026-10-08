<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The Brand Adda quotation sheet ("Quotation format - hoardings.xlsx"):
 * logo and contact block, QUOTATION banner, campaign name with date / number /
 * type, one row per hoarding, Sub Total / GST / Total, then the terms.
 *
 * Every Excel quotation goes through here - a campaign (CampaignExport) and
 * a shortlist ticked on /search or the Map (ShortlistExport) - so the client
 * always receives the same document. Subclasses only say which hoardings and
 * what to call the quotation.
 *
 * The whole sheet is drawn in AfterSheet: the layout is a form, not a plain
 * table, so the usual headings/map concerns would fight it.
 */
abstract class QuotationExport implements FromArray, WithEvents, WithTitle
{
    private const GST_RATE = 0.18;

    private const NAVY   = '0F243E';
    private const ORANGE = 'C65911';
    private const WHITE  = 'FFFFFF';
    private const LINK   = '0563C1';

    private const LAST_COL = 'O';

    private const HEADINGS = [
        'B' => 'Sr.No',
        'C' => 'City',
        'D' => 'District',
        'E' => 'Town',
        'F' => 'Hoarding_Code',
        'G' => 'Location_Name',
        'H' => 'Landmark',
        'I' => 'Width',
        'J' => 'Height',
        'K' => 'TSQ',
        'L' => 'Display Amount (monthly)',
        'M' => 'Days',
        'N' => 'Amount',
        'O' => 'Availability',
    ];

    /** Column widths from the template; the rest keep Excel's default. */
    private const WIDTHS = [
        'A' => 2.57, 'B' => 8.71, 'C' => 11, 'D' => 12, 'E' => 12, 'F' => 14.43,
        'G' => 19.57, 'H' => 18.43, 'I' => 8.71, 'J' => 8.71, 'K' => 9.5,
        'L' => 12, 'M' => 8.71, 'N' => 12, 'O' => 12, 'P' => 9.14,
    ];

    private const ROW_H = 24.95;

    private const BUSINESS_TERMS = [
        'Payment: 50% advance; balance payable within 30 days of invoice.',
        'Printing & Mounting: Printing ₹10/Sq. Ft. | Mounting ₹5/Sq. Ft.',
        'Campaign Photos: Photos shared 3 times — Start, Mid & End.',
        'Special Services: Photography, monitoring, innovations, or special branding will be charged at actuals.',
        'GST: 18% GST applicable additionally on Display, Mounting & Printing Charges.',
    ];

    private const GENERAL_TERMS = [
        'Availability: Sites are subject to availability at the time of final confirmation.',
        'Booking: Confirmed bookings cannot be cancelled or postponed.',
        'Confirmation: Complete site, rate, duration, mounting & tax details must be provided in the confirmation/PO.',
        'Billing: Billing starts from the date of booking/site availability and is not affected by delays in creatives or PO.',
        'Flexes: We are not responsible for theft or damage to flexes during/after the campaign.',
        'Post-Campaign: Flexes/materials will be retained for a maximum of 5 days after campaign expiry.',
        'Extension: Campaign extensions must be confirmed 7 days before expiry; otherwise, the campaign will close as scheduled.',
    ];

    /** Shown in the big cell beside the date - campaign name or "Media Shortlist". */
    abstract protected function quotationName(): string;

    /** e.g. "26/ 160". */
    abstract protected function quotationNumber(): string;

    /**
     * One object per hoarding with: city_name, district_name, area_name,
     * hoarding_code, media_title, landmark_names, width, height, area_auto,
     * monthly_price, days, amount, is_available, booked_until.
     */
    abstract protected function rows(): Collection;

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Quotation';
    }

    /**
     * The hoarding columns every quotation needs, keyed off media_management
     * as m. Callers add the days / amount for their own case.
     */
    protected static function mediaQuery(): Builder
    {
        return DB::table('media_management as m')
            ->leftJoin('areas as ar', 'ar.id', '=', 'm.area_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('cities as ct', 'ct.id', '=', 'm.city_id')
            ->select(
                'm.id as media_id',
                'ct.city_name',
                'd.district_name',
                'ar.area_name',
                'm.hoarding_code',
                'm.media_title',
                'm.width',
                'm.height',
                'm.area_auto',
                'm.price as monthly_price',
                'm.is_available',
                DB::raw('(SELECT GROUP_CONCAT(l.landmark_name SEPARATOR ", ") FROM media_landmark ml JOIN landmark l ON l.id = ml.landmark_id WHERE ml.media_id = m.id AND l.is_deleted = 0) as landmark_names'),
                // The furthest date this site is already committed to - drives
                // the "From <date>" availability.
                DB::raw('(SELECT MAX(mbd.to_date) FROM media_booked_date mbd WHERE mbd.media_id = m.id AND mbd.is_deleted = 0 AND mbd.is_active = 1 AND mbd.to_date >= CURDATE()) as booked_until')
            );
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->draw($event->sheet->getDelegate());
            },
        ];
    }

    private function draw(Worksheet $sheet): void
    {
        $last = self::LAST_COL;

        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

        foreach (self::WIDTHS as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->getRowDimension(2)->setRowHeight(1.5);

        /* ---------- logo and contact block, rows 3-6 ---------- */
        $sheet->mergeCells('B3:G6');
        $this->logo($sheet);

        $contact = [
            3 => 'BRAND ADDA',
            4 => 'Contact No- 7770018173',
            5 => 'Email ID - sales@brand-adda.co.in',
            6 => 'Website : https://brand-adda.co.in/',
        ];
        foreach ($contact as $row => $text) {
            $sheet->mergeCells("H{$row}:{$last}{$row}");
            $sheet->setCellValue("H{$row}", $text);
            $sheet->getStyle("H{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("H{$row}")->getFont()->setSize(12);
            $sheet->getRowDimension($row)->setRowHeight($row === 3 ? 39 : 30);
        }
        $sheet->getStyle("H3")->getFont()->setBold(true)->setSize(18)->getColor()->setRGB(self::WHITE);
        $this->fill($sheet, "H3:{$last}3");
        $sheet->getCell('H6')->getHyperlink()->setUrl('https://brand-adda.co.in/');
        $sheet->getStyle('H6')->getFont()->setUnderline(true)->getColor()->setRGB(self::LINK);

        $this->box($sheet, "B3:{$last}6");
        $this->edge($sheet, 'B3:G6', 'right', Border::BORDER_MEDIUM);

        /* ---------- QUOTATION banner, row 7 ---------- */
        $sheet->mergeCells("B7:{$last}7");
        $sheet->setCellValue('B7', 'QUOTATION');
        $this->banner($sheet, "B7:{$last}7", 12, Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(7)->setRowHeight(self::ROW_H);

        /* ---------- name, date, number, type - rows 8-10 ---------- */
        $sheet->mergeCells('B8:K10');
        $sheet->setCellValueExplicit('B8', $this->quotationName(), DataType::TYPE_STRING);
        $sheet->getStyle('B8')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B8')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true)->setIndent(1);

        $meta = [
            8  => ['Quotation Date :-', null],
            9  => ['Quotation Number:-', $this->quotationNumber()],
            10 => ['Type', 'Hoardings'],
        ];
        foreach ($meta as $row => [$label, $value]) {
            $sheet->mergeCells("L{$row}:M{$row}");
            $sheet->mergeCells("N{$row}:{$last}{$row}");
            $sheet->setCellValue("L{$row}", $label);
            if ($value !== null) {
                $sheet->setCellValueExplicit("N{$row}", $value, DataType::TYPE_STRING);
            }
            $sheet->getRowDimension($row)->setRowHeight(self::ROW_H);
        }
        // A real date, so it sorts and reformats like the template's.
        $sheet->setCellValue('N8', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(now()->startOfDay()));
        $sheet->getStyle('N8')->getNumberFormat()->setFormatCode('d-mmm-yy');

        $sheet->getStyle("L8:{$last}10")->getFont()->setSize(12);
        $sheet->getStyle("L8:{$last}10")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
        $this->grid($sheet, "L8:{$last}10");
        $this->box($sheet, "B8:{$last}10");

        /* ---------- table heading, row 11 ---------- */
        foreach (self::HEADINGS as $col => $text) {
            $sheet->setCellValue("{$col}11", $text);
        }
        $this->banner($sheet, "B11:{$last}11", 12, Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B11:{$last}11")->getAlignment()->setWrapText(true);
        $sheet->getRowDimension(11)->setRowHeight(51.75);

        /* ---------- one row per hoarding ---------- */
        $first = 12;
        $row   = $first;
        $sr    = 0;

        foreach ($this->rows() as $item) {
            $sr++;

            $width  = (float) ($item->width ?? 0);
            $height = (float) ($item->height ?? 0);

            $sheet->setCellValue("B{$row}", $sr);
            $this->text($sheet, "C{$row}", $item->city_name);
            $this->text($sheet, "D{$row}", $item->district_name);
            $this->text($sheet, "E{$row}", $item->area_name);
            $this->text($sheet, "F{$row}", $item->hoarding_code);
            $this->text($sheet, "G{$row}", $item->media_title);
            $this->text($sheet, "H{$row}", $item->landmark_names);

            if ($width > 0 && $height > 0) {
                $sheet->setCellValue("I{$row}", $width);
                $sheet->setCellValue("J{$row}", $height);
                $sheet->setCellValue("K{$row}", "=I{$row}*J{$row}");
            } else {
                // Panelled media (a bus shelter) has no single face; its area
                // is the total of its panels.
                $this->text($sheet, "I{$row}", '-');
                $this->text($sheet, "J{$row}", '-');
                $sheet->setCellValue("K{$row}", (float) ($item->area_auto ?? 0));
            }

            $sheet->setCellValue("L{$row}", (float) ($item->monthly_price ?? 0));
            $sheet->setCellValue("M{$row}", (int) $item->days);
            $sheet->setCellValue("N{$row}", round((float) $item->amount, 2));
            $this->text($sheet, "O{$row}", $this->availability($item));

            $sheet->getRowDimension($row)->setRowHeight($this->rowHeight($item));
            $row++;
        }

        // The template keeps one empty, bordered row under the last site.
        $lastData = $row;
        $sheet->getRowDimension($lastData)->setRowHeight(self::ROW_H);

        $body = "B{$first}:{$last}{$lastData}";
        $sheet->getStyle($body)->getFont()->setSize(12);
        $sheet->getStyle($body)->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle("C{$first}:H{$lastData}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        foreach (['B', 'I', 'J', 'K', 'L', 'M', 'N', 'O'] as $col) {
            $sheet->getStyle("{$col}{$first}:{$col}{$lastData}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $sheet->getStyle("K{$first}:K{$lastData}")->getNumberFormat()->setFormatCode('#,##0.##');
        $sheet->getStyle("L{$first}:L{$lastData}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("N{$first}:N{$lastData}")->getNumberFormat()->setFormatCode('#,##0');

        $this->grid($sheet, "B11:{$last}{$lastData}");
        $this->box($sheet, "B11:{$last}{$lastData}");

        /* ---------- Sub Total / GST / Total ---------- */
        $sub = $lastData + 1;
        $gst = $sub + 1;
        $tot = $sub + 2;

        $totals = [
            $sub => ['Sub Total', "=SUM(N{$first}:N{$lastData})"],
            $gst => ['GST 18%', "=O{$sub}*" . self::GST_RATE],
            $tot => ['Total Amount', "=O{$sub}+O{$gst}"],
        ];
        foreach ($totals as $r => [$label, $formula]) {
            $sheet->mergeCells("B{$r}:L{$r}");
            $sheet->mergeCells("M{$r}:N{$r}");
            $sheet->setCellValue("M{$r}", $label);
            $sheet->setCellValue("O{$r}", $formula);
            $sheet->getRowDimension($r)->setRowHeight(self::ROW_H);
        }
        $sheet->getStyle("M{$sub}:O{$tot}")->getFont()->setBold(true)->setSize(12)->getColor()->setRGB(self::WHITE);
        $sheet->getStyle("M{$sub}:O{$tot}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("O{$sub}:O{$tot}")->getNumberFormat()->setFormatCode('#,##0.00');
        // Sub Total, GST and Total run navy the full width of the table.
        foreach ([$sub, $gst, $tot] as $r) {
            $this->fill($sheet, "B{$r}:{$last}{$r}");
            $this->box($sheet, "B{$r}:{$last}{$r}");
        }
        $this->grid($sheet, "M{$sub}:O{$tot}");
        $this->box($sheet, "B{$sub}:{$last}{$tot}");

        /* ---------- terms ---------- */
        $r = $tot + 1;
        $sheet->mergeCells("B{$r}:{$last}{$r}");
        $sheet->getRowDimension($r)->setRowHeight(self::ROW_H);
        $termsTop = $r;

        $r++;
        $sheet->mergeCells("B{$r}:{$last}{$r}");
        $sheet->setCellValue("B{$r}", 'Terms & Conditions:-');
        $this->banner($sheet, "B{$r}:{$last}{$r}", 14, Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension($r)->setRowHeight(self::ROW_H);

        $r = $this->termsBlock($sheet, $r + 1, 'Business Terms :', self::BUSINESS_TERMS);
        $r = $this->termsBlock($sheet, $r + 1, 'General Terms:', self::GENERAL_TERMS);

        $sheet->mergeCells("B{$r}:{$last}{$r}");
        $sheet->getRowDimension($r)->setRowHeight(self::ROW_H);
        $this->fill($sheet, "B{$r}:{$last}{$r}");
        $this->box($sheet, "B{$termsTop}:{$last}{$r}");

        /* ---------- printing ---------- */
        $setup = $sheet->getPageSetup();
        $setup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $setup->setPaperSize(PageSetup::PAPERSIZE_A4);
        $setup->setFitToWidth(1);
        $setup->setFitToHeight(0);
        $setup->setPrintArea("A1:P{$r}");
        $sheet->getPageMargins()->setLeft(0.3)->setRight(0.3)->setTop(0.4)->setBottom(0.4);
        $sheet->setSelectedCell('A1');
    }

    /** A heading line, then numbered terms. Returns the next free row. */
    private function termsBlock(Worksheet $sheet, int $r, string $heading, array $terms): int
    {
        $last = self::LAST_COL;

        $sheet->mergeCells("B{$r}:{$last}{$r}");
        $sheet->setCellValue("B{$r}", $heading);
        $sheet->getStyle("B{$r}")->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("B{$r}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($r)->setRowHeight(self::ROW_H);

        foreach ($terms as $i => $term) {
            $r++;
            $sheet->setCellValue("B{$r}", $i + 1);
            $sheet->mergeCells("C{$r}:{$last}{$r}");
            $sheet->getCell("C{$r}")->setValue($this->term($term));
            $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$r}:C{$r}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension($r)->setRowHeight(self::ROW_H);
        }

        return $r + 1;
    }

    /** "Payment: 50% advance..." - the label before the colon in bold orange, the rest navy. */
    private function term(string $term): RichText
    {
        $text = new RichText();
        [$label, $rest] = array_pad(explode(':', $term, 2), 2, null);

        if ($rest === null) {
            $text->createTextRun($term)->getFont()->setColor(new Color('FF' . self::NAVY));
            return $text;
        }

        $head = $text->createTextRun($label . ':');
        $head->getFont()->setBold(true)->setColor(new Color('FF' . self::ORANGE));
        $text->createTextRun($rest)->getFont()->setColor(new Color('FF' . self::NAVY));

        return $text;
    }

    private function logo(Worksheet $sheet): void
    {
        $path = public_path('assets/img/logo/brand_adda_quotation.png');

        if (!is_file($path)) {
            $path = public_path('assets/img/logo/brand_adda.png');
        }
        if (!is_file($path)) {
            return;
        }

        $drawing = new Drawing();
        $drawing->setName('Brand Adda');
        $drawing->setPath($path);
        $drawing->setCoordinates('B3');
        $drawing->setOffsetX(28);
        $drawing->setOffsetY(26);
        $drawing->setHeight(123);
        $drawing->setWorksheet($sheet);
    }

    /**
     * When this site can go up: never while it is switched off, otherwise the
     * day after whatever it is already committed to, otherwise right now.
     */
    private function availability(object $item): string
    {
        if ((int) ($item->is_available ?? 1) === 0) {
            return 'Not Available';
        }

        if (!empty($item->booked_until)) {
            return 'From ' . Carbon::parse($item->booked_until)->addDay()->format('d M y');
        }

        return 'Immediate';
    }

    /** Tall enough for the longest wrapped text cell in the row. */
    private function rowHeight(object $item): float
    {
        $lines = 1;

        foreach ([
            'C' => $item->city_name, 'D' => $item->district_name, 'E' => $item->area_name,
            'G' => $item->media_title, 'H' => $item->landmark_names,
        ] as $col => $value) {
            // Roughly how many 12pt characters fit across the column.
            $perLine = max(1, (int) floor(self::WIDTHS[$col] * 0.95));
            $lines   = max($lines, (int) ceil(mb_strlen((string) $value) / $perLine));
        }

        return max(self::ROW_H, $lines * 16 + 6);
    }

    private function text(Worksheet $sheet, string $cell, $value): void
    {
        $value = trim((string) $value);
        $sheet->setCellValueExplicit($cell, $value === '' ? '-' : $value, DataType::TYPE_STRING);
    }

    /** Navy band with white bold text. */
    private function banner(Worksheet $sheet, string $range, int $size, string $align): void
    {
        $this->fill($sheet, $range);
        $sheet->getStyle($range)->getFont()->setBold(true)->setSize($size)->getColor()->setRGB(self::WHITE);
        $sheet->getStyle($range)->getAlignment()->setHorizontal($align)->setVertical(Alignment::VERTICAL_CENTER);
        if ($align === Alignment::HORIZONTAL_LEFT) {
            $sheet->getStyle($range)->getAlignment()->setIndent(1);
        }
        $this->grid($sheet, $range);
        $this->box($sheet, $range);
    }

    private function fill(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::NAVY);
    }

    private function grid(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    private function box(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
    }

    private function edge(Worksheet $sheet, string $range, string $side, string $style): void
    {
        $borders = $sheet->getStyle($range)->getBorders();
        $method  = 'get' . ucfirst($side);
        $borders->{$method}()->setBorderStyle($style);
    }
}

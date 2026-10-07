<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Services\Website\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Exports\CampaignExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Fill;

class CampaignController extends Controller
{
    protected $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'campaign_name' => 'required|string|max:255',
            ]);

            $this->campaignService->saveCampaign(
                Auth::guard('website')->id(),
                $request->campaign_name
            );

            //  MAIL CALL (after response — does not block user)
            $userId = Auth::guard('website')->id();
            $campaignService = $this->campaignService;

            dispatch(function () use ($userId, $campaignService) {
                try {
                    $campaignService->sendCampaignMailToAdmin($userId);
                } catch (\Exception $mailEx) {
                    Log::error('Campaign mail failed: ' . $mailEx->getMessage());
                }
            })->afterResponse();

            return redirect()
                ->route('campaigns.open')
                ->with('success', 'Campaign created successfully');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }
    public function isCampaignBooked($items)
    {
        foreach ($items as $row) {

            $exists = DB::table('media_booked_date')
                ->where('media_id', $row->media_id)
                ->where('is_deleted', 0)
                ->where('is_active', 1)
                ->where(function ($q) use ($row) {
                    $q->whereBetween('from_date', [$row->from_date, $row->to_date])
                        ->orWhereBetween('to_date', [$row->from_date, $row->to_date])
                        ->orWhere(function ($q2) use ($row) {
                            $q2->where('from_date', '<=', $row->from_date)
                                ->where('to_date', '>=', $row->to_date);
                        });
                })
                ->exists();

            if ($exists) {
                return true;
            }
        }

        return false;
    }
    public function openCampaigns(Request $request)
    {
        $campaigns = $this->campaignService->getOpenCampaigns(
            Auth::guard('website')->id(),
            $request
        );

        $bookedStatus = [];

        foreach ($campaigns as $campaignId => $items) {
            $bookedStatus[$campaignId] =
                $this->campaignService->isCampaignBooked($items);
        }


        return view('website.campaign-list', [
            'campaigns' => $campaigns,
            'type'      => 'open',
            'bookedStatus' => $bookedStatus
        ]);
    }
    // 🔵 BOOKED (Order placed)
    public function bookedCampaigns(Request $request)
    {
        $campaigns = $this->campaignService->getBookedCampaigns(
            Auth::guard('website')->id(),
            $request
        );

        return view('website.campaign-list', [
            'campaigns' => $campaigns,
            'type'      => 'booked',
        ]);
    }

    // ⚫ PAST (Expired)
    public function pastCampaigns(Request $request)
    {
        $campaigns = $this->campaignService->getPastCampaigns(
            Auth::guard('website')->id(),
            $request
        );

        return view('website.campaign-list', [
            'campaigns' => $campaigns,
            'type'      => 'past',
        ]);
    }

    public function getCampaignList(Request $request)
    {
        $userId = Auth::guard('website')->id();
        $type   = $request->get('type', 'active');

        $campaigns = $this->campaignService->getCampaignList(
            $userId,
            $request
        );

        $today = now()->startOfDay();

        $filteredCampaigns = [];

        foreach ($campaigns as $campaignId => $items) {

            // campaign cha last to_date
            $lastToDate = collect($items)->max('to_date');

            if (!$lastToDate) {
                continue;
            }

            $lastToDate = \Carbon\Carbon::parse($lastToDate);

            //  FILTER HERE
            if ($type === 'active' && $lastToDate->gte($today)) {
                $filteredCampaigns[$campaignId] = $items;
            }

            if ($type === 'past' && $lastToDate->lt($today)) {
                $filteredCampaigns[$campaignId] = $items;
            }
        }

        return view('website.campaign-list', [
            'campaigns' => collect($filteredCampaigns),
            'type'      => $type,
        ]);
    }

    public function viewDetails($cartItemId)
    {
        try {

            $cartItemId = base64_decode($cartItemId); //  DECRYPT HERE

            $campaign = $this->campaignService->getCampaignDetailsByCartItem(
                Auth::guard('website')->id(),
                $cartItemId
            );

            return view('website.campaign-details', compact('campaign'));
        } catch (\Exception $e) {
            Log::error('Campaign Details Error', ['message' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Unable to load campaign details.');
        }
    }
    public function exportExcel($campaignId)
    {
        $campaignId = base64_decode($campaignId);

        if (!$campaignId || !is_numeric($campaignId)) {
            abort(404);
        }

        $campaignId = (int) $campaignId;
        $userId = Auth::guard('website')->id();

        $campaign = DB::table('campaign')->where('id', $campaignId)->first();

        if (!$campaign || (int) $campaign->user_id !== $userId) {
            abort(403);
        }

        $fileName = preg_replace('/[^A-Za-z0-9_-]/', '_', $campaign->campaign_name)
            . '_' . now()->format('d-m-Y') . '.xlsx';

        return Excel::download(
            new CampaignExport($userId, $campaignId),
            $fileName
        );
    }



    public function exportPpt($campaignId)
    {
        $campaignId = base64_decode($campaignId);

        if (!$campaignId || !is_numeric($campaignId)) {
            abort(404);
        }

        $campaignId = (int) $campaignId;
        $userId = Auth::guard('website')->id();

        $campaign = DB::table('campaign')->where('id', $campaignId)->first();

        if (!$campaign || (int) $campaign->user_id !== $userId) {
            abort(403);
        }

        $binary = $this->generatePptBinary($campaignId);

        $fileName = preg_replace(
            '/[^A-Za-z0-9_-]/',
            '_',
            $campaign->campaign_name
        ) . '_' . now()->format('d-m-Y') . '.pptx';

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function generatePptBinary(int $campaignId): string
    {
        /* ================= CAMPAIGN ================= */
        $campaign = DB::table('campaign')
            ->where('id', $campaignId)
            ->first();

        if (!$campaign) {
            throw new \Exception('Campaign not found');
        }

        /* ================= ITEMS ================= */
        $items = $this->pptItemsQuery()
            ->join('cart_items as ci', 'ci.media_id', '=', 'm.id')
            ->addSelect('ci.from_date', 'ci.to_date')
            ->where('ci.campaign_id', $campaignId)
            ->where('ci.cart_type', 'CAMPAIGN')
            ->get();

        return $this->buildPpt(['Campaign ', 'Name'], $campaign->campaign_name, $items);
    }

    /**
     * The same deck for a shortlist ticked on /search or the Map — no campaign
     * behind it, so no booking dates, and the hoardings come in the order the
     * team picked them.
     */
    public function generateShortlistPptBinary(array $mediaIds): string
    {
        $items = $this->pptItemsQuery()
            ->addSelect(DB::raw('NULL as from_date'), DB::raw('NULL as to_date'), 'm.id as media_id')
            ->whereIn('m.id', $mediaIds)
            ->get();

        $order = array_flip(array_values($mediaIds));
        $items = $items->sortBy(fn ($item) => $order[$item->media_id] ?? PHP_INT_MAX)->values();

        return $this->buildPpt(
            ['Media ', 'Shortlist'],
            count($mediaIds) . ' Hoarding' . (count($mediaIds) === 1 ? '' : 's') . ' - ' . now()->format('d M Y'),
            $items
        );
    }

    /** Everything a media slide shows, keyed off media_management as m. */
    private function pptItemsQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('media_management as m')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('cities as c', 'c.id', '=', 'm.city_id')
            ->leftJoin('illuminations as i', 'i.id', '=', 'm.illumination_id')
            ->leftJoin('category as cat', 'cat.id', '=', 'm.category_id')
            ->leftJoin('highway as hw', 'hw.id', '=', 'm.highway_id')
            ->leftJoin(DB::raw("
                (
                    SELECT media_id, GROUP_CONCAT(images) AS all_images
                    FROM media_images
                    WHERE is_deleted = 0
                    GROUP BY media_id
                ) mi
            "), 'mi.media_id', '=', 'm.id')
            ->select(
                'm.media_title',
                'm.hoarding_code',
                'm.width',
                'm.height',
                'm.price',
                'a.area_name',
                'a.common_stdiciar_name',
                'c.city_name',
                'i.illumination_name',
                'hw.highway_name',
                DB::raw('(SELECT GROUP_CONCAT(l.landmark_name SEPARATOR ", ") FROM media_landmark ml JOIN landmark l ON l.id = ml.landmark_id WHERE ml.media_id = m.id AND l.is_deleted = 0) as landmark_names'),
                'cat.category_name as media_type',
                'mi.all_images'
            );
    }

    /**
     * @param array{0:string,1:string} $heading  Navy word, orange word.
     */
    private function buildPpt(array $heading, string $subtitleText, \Illuminate\Support\Collection $items): string
    {
        // VERY IMPORTANT: Clean output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        /* ================= INIT PPT ================= */
        $ppt = new PhpPresentation();

        $ppt->getLayout()->setDocumentLayout(
            \PhpOffice\PhpPresentation\DocumentLayout::LAYOUT_SCREEN_16X9
        );

        // Temp files created while converting media images. Declared out here,
        // not inside the item loop, so every one of them is cleaned up at the
        // end rather than only the last item's.
        $tempPaths = [];


        /* =====================================================
        SLIDE 1 : WELCOME — the designed cover, full bleed
        ===================================================== */
        $this->pptFullBleed(
            $ppt->getActiveSlide(),
            public_path('assets/img/brand_adda_welcome.png')
        );

        /* =====================================================
        SLIDE 2 : CAMPAIGN NAME
        ===================================================== */
        $cover = $ppt->createSlide();

        $this->pptChrome($cover);

        $title = $cover->createRichTextShape()
            ->setOffsetX(self::PPT_MARGIN)
            ->setOffsetY(170)
            ->setWidth(self::PPT_W - (2 * self::PPT_MARGIN))
            ->setHeight(70);

        $title->getActiveParagraph()->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Two-tone, as the format sheet has it: navy word, orange word.
        $title->createTextRun($heading[0])
            ->getFont()->setSize(40)->setBold(true)
            ->setColor(new Color(self::PPT_NAVY_HEX));

        $title->createTextRun($heading[1])
            ->getFont()->setSize(40)->setBold(true)
            ->setColor(new Color(self::PPT_ORANGE_HEX));

        $subtitle = $cover->createRichTextShape()
            ->setOffsetX(self::PPT_MARGIN)
            ->setOffsetY(262)
            ->setWidth(self::PPT_W - (2 * self::PPT_MARGIN))
            ->setHeight(40);

        $subtitle->getActiveParagraph()->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $subtitle->createTextRun('# ' . $subtitleText . ' #')
            ->getFont()->setSize(18)->setBold(true)->setItalic(true)
            ->setColor(new Color(self::PPT_NAVY_HEX));

        /* =====================================================
        MEDIA SLIDES — picture left, SITE DETAILS right
        ===================================================== */
        foreach ($items as $item) {

            $slide = $ppt->createSlide();

            $this->pptChrome($slide);

            /* ---------- PICTURES : LEFT ---------- */
            $images = [];

            foreach (explode(',', (string) $item->all_images) as $stored) {
                $prepared = $this->pptImage($stored, $tempPaths);

                if ($prepared !== null) {
                    $images[] = $prepared;
                }

                // Four is all the space can show: one hero plus a strip of three.
                if (count($images) === 4) {
                    break;
                }
            }

            if ($images === []) {
                $placeholder = $slide->createRichTextShape()
                    ->setOffsetX(self::PPT_PIC_X)
                    ->setOffsetY(self::PPT_CONTENT_Y)
                    ->setWidth(self::PPT_PIC_W)
                    ->setHeight(self::PPT_CONTENT_H);

                $placeholder->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->setStartColor(new Color('FFF2F2F2'));

                $placeholder->getActiveParagraph()->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $placeholder->createTextRun('NO IMAGES AVAILABLE')
                    ->getFont()->setSize(16)->setBold(true)
                    ->setColor(new Color('FF8A8A8A'));
            } else {
                // One picture fills the space. More than one and the first keeps
                // the top of it, with the rest as a strip underneath.
                $heroHeight = count($images) > 1
                    ? self::PPT_HERO_H
                    : self::PPT_CONTENT_H;

                $this->pptPlaceImage(
                    $slide,
                    array_shift($images),
                    self::PPT_PIC_X,
                    self::PPT_CONTENT_Y,
                    self::PPT_PIC_W,
                    $heroHeight
                );

                $thumbGap   = 8;
                $thumbWidth = (int) floor((self::PPT_PIC_W - (2 * $thumbGap)) / 3);
                $thumbY     = self::PPT_CONTENT_Y + self::PPT_HERO_H + 12;
                $thumbH     = self::PPT_CONTENT_Y + self::PPT_CONTENT_H - $thumbY;

                foreach (array_values($images) as $index => $thumb) {
                    $this->pptPlaceImage(
                        $slide,
                        $thumb,
                        self::PPT_PIC_X + ($index * ($thumbWidth + $thumbGap)),
                        $thumbY,
                        $thumbWidth,
                        $thumbH
                    );
                }
            }

            /* ---------- SITE DETAILS : RIGHT ---------- */
            $from = $item->from_date
                ? \Carbon\Carbon::parse($item->from_date)->format('d M Y')
                : '-';

            $to = $item->to_date
                ? \Carbon\Carbon::parse($item->to_date)->format('d M Y')
                : '-';

            $rows = [
                'Media Title' => $item->media_title ?: '-',
                'Code'        => $item->hoarding_code ?: '-',
                'Location'    => $item->common_stdiciar_name ?: '-',
                'Area'        => $item->area_name ?: '-',
                'City'        => $item->city_name ?: '-',
                'Size'        => $this->pptSize($item),
                'Media type'  => $item->media_type ?: '-',
                'Price'       => '₹ ' . number_format((float) $item->price),
                'From Date'   => $from,
                'To Date'     => $to,
                'Lighting'    => $item->illumination_name ?: '-',
                'Highway'     => $item->highway_name ?: '-',
                'Landmarks'   => $item->landmark_names ?: '-',
            ];

            $panel = $this->pptDetailsPanel($rows, $tempPaths);

            if ($panel !== null) {
                $slide->createDrawingShape()
                    ->setPath($panel)
                    ->setResizeProportional(false)
                    ->setWidth(self::PPT_DETAIL_W)
                    ->setHeight(self::PPT_CONTENT_H)
                    ->setOffsetX(self::PPT_DETAIL_X)
                    ->setOffsetY(self::PPT_CONTENT_Y);
            }
        }

        /* =====================================================
        LAST SLIDE : THANK YOU — the designed closer, full bleed
        ===================================================== */
        $this->pptFullBleed(
            $ppt->createSlide(),
            public_path('assets/img/brand_adda_thankyou.png')
        );
        /* ================= RETURN BINARY ================= */
        $writer = IOFactory::createWriter($ppt, 'PowerPoint2007');

        ob_start();
        $writer->save('php://output');
        $pptContent = ob_get_clean();

        foreach ($tempPaths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        return $pptContent;
    }

    /* =========================================================
       PPT LAYOUT
       Slide is 960 x 540 px. The media slides give the left 70%
       (0 - 672) to the pictures and the right 30% to the details.
       ========================================================= */


    /* =========================================================
       PPT LAYOUT — follows the Brand Adda PPT format sheet.
       Slide is 960 x 540 px. The welcome and thank-you slides are
       the supplied artwork, full bleed. Everything between them is
       white, with the logo top-left and a footer rule, and splits
       into a picture on the left and SITE DETAILS on the right.
       ========================================================= */

    private const PPT_W      = 960;
    private const PPT_H      = 540;
    private const PPT_MARGIN = 44;

    private const PPT_NAVY_HEX   = 'FF142A4F';
    private const PPT_ORANGE_HEX = 'FFFD5F00';

    /** The band both columns sit in. */
    private const PPT_CONTENT_Y = 76;
    private const PPT_CONTENT_H = 372;

    /** Picture column, left. */
    private const PPT_PIC_X = 44;
    private const PPT_PIC_W = 526;

    /** Height the first picture keeps when a thumbnail strip sits below it. */
    private const PPT_HERO_H = 278;

    /** SITE DETAILS column, right. 610 + 306 = 916, the footer rule's end. */
    private const PPT_DETAIL_X = 610;
    private const PPT_DETAIL_W = 306;

    /** Longest edge a picture keeps once it is in the deck. */
    private const PPT_IMAGE_MAX_EDGE = 1400;

    /** JPEG quality for everything this class re-encodes. */
    private const PPT_JPEG_QUALITY = 82;

    /** Pixel size of cached full-slide artwork — 16:9, sharp on a projector. */
    private const PPT_BACKDROP_W = 1440;
    private const PPT_BACKDROP_H = 810;

    /**
     * A whole slide of supplied artwork — the welcome and thank-you pages.
     *
     * Shrunk and cached first: the originals run to several MB and
     * PhpPresentation stores a separate copy of every drawing it is given.
     */
    private function pptFullBleed(\PhpOffice\PhpPresentation\Slide $slide, string $artwork): void
    {
        $file = $this->pptSlideArtwork($artwork);

        if ($file === null) {
            return;
        }

        $slide->createDrawingShape()
            ->setPath($file)
            ->setResizeProportional(false)
            ->setWidth(self::PPT_W)
            ->setHeight(self::PPT_H)
            ->setOffsetX(0)
            ->setOffsetY(0);
    }

    /**
     * The furniture every middle slide carries: white ground, logo top-left,
     * footer rule and the two footer lines.
     *
     * Painted once into a cached file and placed as a single picture. As one
     * drawing rather than five shapes it keeps the file small, and the footer
     * cannot drift out of line from slide to slide.
     */
    private function pptChrome(\PhpOffice\PhpPresentation\Slide $slide): void
    {
        $file = $this->pptChromeFile();

        if ($file === null) {
            return;
        }

        $slide->createDrawingShape()
            ->setPath($file)
            ->setResizeProportional(false)
            ->setWidth(self::PPT_W)
            ->setHeight(self::PPT_H)
            ->setOffsetX(0)
            ->setOffsetY(0);
    }

    /** Build (or reuse) the white slide furniture. */
    private function pptChromeFile(): ?string
    {
        // The .png rather than the .webp twin: PowerPoint before 365 cannot
        // draw WebP, and a logo that fails to render is worse than no logo.
        $logo = public_path('assets/img/logo/brand_adda.png');

        $cached = $this->pptCachePath('chrome', [
            is_file($logo) ? (string) @filemtime($logo) : 'no-logo',
        ]);

        if ($cached === null) {
            return null;
        }

        if (is_file($cached)) {
            return $cached;
        }

        $w = self::PPT_BACKDROP_W;
        $h = self::PPT_BACKDROP_H;
        $r = $h / self::PPT_H;          // slide px -> artwork px

        $canvas = imagecreatetruecolor($w, $h);

        imagefilledrectangle($canvas, 0, 0, $w, $h, imagecolorallocate($canvas, 255, 255, 255));
        imagealphablending($canvas, true);

        $orange = imagecolorallocate($canvas, ...self::PPT_ORANGE);
        $navy   = imagecolorallocate($canvas, ...self::PPT_NAVY);

        if (is_file($logo)) {
            $mark = @imagecreatefromstring((string) @file_get_contents($logo));

            if ($mark !== false) {
                $logoH = (int) round(34 * $r);
                $logoW = (int) round($logoH * (imagesx($mark) / imagesy($mark)));

                imagecopyresampled(
                    $canvas,
                    $mark,
                    (int) round(self::PPT_MARGIN * $r),
                    (int) round(16 * $r),
                    0,
                    0,
                    $logoW,
                    $logoH,
                    imagesx($mark),
                    imagesy($mark)
                );

                imagedestroy($mark);
            }
        }

        /* ---------- footer ---------- */
        $ruleY = (int) round(497 * $r);

        imagefilledrectangle(
            $canvas,
            (int) round(self::PPT_MARGIN * $r),
            $ruleY,
            (int) round((self::PPT_W - self::PPT_MARGIN) * $r),
            $ruleY + max(1, (int) round(1.2 * $r)),
            $orange
        );

        $textFont = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');

        if (is_file($textFont)) {
            $size     = 8 * $r;
            $baseline = (int) round(518 * $r);

            imagettftext($canvas, $size, 0, (int) round(self::PPT_MARGIN * $r), $baseline, $navy, $textFont, 'brand-adda.co.in');

            $tag = "Maharashtra's Outdoor Media Platform";
            $box = imagettfbbox($size, 0, $textFont, $tag);

            imagettftext(
                $canvas,
                $size,
                0,
                (int) round((self::PPT_W - self::PPT_MARGIN) * $r) - ($box[2] - $box[0]),
                $baseline,
                $navy,
                $textFont,
                $tag
            );
        }

        $written = @imagejpeg($canvas, $cached, self::PPT_JPEG_QUALITY);
        imagedestroy($canvas);

        return $written ? $cached : null;
    }

    /** Shrink a full-slide artwork file once and keep the result. */
    private function pptSlideArtwork(string $artwork): ?string
    {
        if (!is_file($artwork)) {
            Log::warning('Campaign PPT: slide artwork missing', ['path' => $artwork]);
            return null;
        }

        $cached = $this->pptCachePath(
            'slide_' . pathinfo($artwork, PATHINFO_FILENAME),
            [(string) @filemtime($artwork)]
        );

        if ($cached === null) {
            return null;
        }

        if (is_file($cached)) {
            return $cached;
        }

        $source = @imagecreatefromstring((string) @file_get_contents($artwork));

        if ($source === false) {
            return null;
        }

        $canvas = imagecreatetruecolor(self::PPT_BACKDROP_W, self::PPT_BACKDROP_H);

        imagefilledrectangle(
            $canvas,
            0,
            0,
            self::PPT_BACKDROP_W,
            self::PPT_BACKDROP_H,
            imagecolorallocate($canvas, 255, 255, 255)
        );

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            self::PPT_BACKDROP_W,
            self::PPT_BACKDROP_H,
            imagesx($source),
            imagesy($source)
        );

        imagedestroy($source);

        $written = @imagejpeg($canvas, $cached, self::PPT_JPEG_QUALITY);
        imagedestroy($canvas);

        return $written ? $cached : null;
    }

    /**
     * Where a cached slide asset lives. Keyed on what it was built from, so
     * replacing the artwork rebuilds it by itself.
     *
     * @param list<string> $parts
     */
    private function pptCachePath(string $name, array $parts): ?string
    {
        $dir = storage_path('app/ppt');

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return null;
        }

        $key = md5(implode('|', array_merge($parts, [
            self::PPT_BACKDROP_W . 'x' . self::PPT_BACKDROP_H,
        ])));

        return $dir . DIRECTORY_SEPARATOR . $name . '_' . $key . '.jpg';
    }

    /**
     * Size as the format sheet writes it — "20 x 20 Feet" — falling back to the
     * total area for panelled media, which has no single face.
     */
    private function pptSize(object $item): string
    {
        $width  = (float) ($item->width ?? 0);
        $height = (float) ($item->height ?? 0);

        $trim = static fn(float $n): string => rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');

        if ($width > 0 && $height > 0) {
            return $trim($width) . ' x ' . $trim($height) . ' Feet';
        }

        $area = (float) ($item->area_auto ?? 0);

        return $area > 0 ? $trim($area) . ' sq.ft' : '-';
    }
    private function pptImage(string $stored, array &$tempPaths): ?array
    {
        $stored = trim($stored);

        if ($stored === '' || str_contains($stored, '..')) {
            return null;
        }

        $source = storage_path('app/public/upload/images/media/' . basename($stored));

        if (!is_file($source) || !is_readable($source)) {
            return null;
        }

        $info = @getimagesize($source);

        if ($info === false || $info[0] < 1 || $info[1] < 1) {
            return null;
        }

        [$width, $height] = $info;

        $scale = min(1.0, self::PPT_IMAGE_MAX_EDGE / max($width, $height));

        // Already a format PowerPoint reads, and no bigger than the deck needs.
        if ($scale >= 1.0 && in_array($info['mime'] ?? '', ['image/jpeg', 'image/png'], true)) {
            return ['path' => $source, 'width' => $width, 'height' => $height];
        }

        $image = @imagecreatefromstring((string) @file_get_contents($source));

        if ($image === false) {
            return null;
        }

        $targetWidth  = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        // Flatten onto white: a transparent PNG would otherwise come out with
        // black behind it once it is a JPEG.
        imagefilledrectangle(
            $canvas,
            0,
            0,
            $targetWidth,
            $targetHeight,
            imagecolorallocate($canvas, 255, 255, 255)
        );

        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagedestroy($image);

        $tempPath = storage_path('app/temp_ppt_' . md5($stored) . '.jpg');

        $written = @imagejpeg($canvas, $tempPath, self::PPT_JPEG_QUALITY);
        imagedestroy($canvas);

        if (!$written) {
            return null;
        }

        $tempPaths[] = $tempPath;

        return ['path' => $tempPath, 'width' => $targetWidth, 'height' => $targetHeight];
    }

    /* =========================================================
       SITE DETAILS PANEL
       Drawn with GD and placed as one picture. PhpPresentation can
       only write plain rectangles, so the rounded pills and the
       round icon badges the design asks for are not expressible as
       PowerPoint shapes — they are painted here instead.
       ========================================================= */

    /** Rendered at this multiple of the slide size, so it stays sharp. */
    private const PPT_PANEL_SCALE = 3;

    private const PPT_ORANGE = [253, 95, 0];      // sampled from the logo
    private const PPT_NAVY   = [20, 42, 79];      // headings, values, footer
    private const PPT_LABEL  = [98, 105, 115];    // grey, for the labels
    private const PPT_PILL   = [242, 242, 242];   // the row's light ground
    private const PPT_RULE   = [222, 224, 228];   // divider between icon and label

    /** Font Awesome Solid glyph per row, keyed on the label. */
    private const PPT_ROW_ICONS = [
        'Media Title' => 0xf108, // desktop — the site's own name
        'Code'       => 0xf292, // hashtag
        'Location'   => 0xf3c5, // location-dot
        'Area'       => 0xf279, // map
        'City'       => 0xf1ad, // building
        'Size'       => 0xf31e, // expand
        'Media type' => 0xf03e, // image
        'Price'      => 0xf156, // rupee-sign
        'From Date'  => 0xf073, // calendar
        'To Date'    => 0xf073, // calendar
        'Lighting'   => 0xf0eb, // lightbulb
        'Highway'    => 0xf018, // road
        'Landmarks'  => 0xf207, // bus
    ];

    /**
     * Paint the SITE DETAILS panel and return the file to place on the slide.
     *
     * @param  array<string,string> $rows       label => value, in display order
     * @param  list<string>         $tempPaths  collects temp files to delete
     */
    private function pptDetailsPanel(array $rows, array &$tempPaths): ?string
    {
        $iconFont  = public_path('asset/css/icons/font-awesome/webfonts/fa-solid-900.ttf');
        $textFont  = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');
        $boldFont  = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf');

        foreach ([$iconFont, $textFont, $boldFont] as $font) {
            if (!is_file($font)) {
                Log::warning('Campaign PPT: details panel font missing', ['font' => $font]);
                return null;
            }
        }

        $s = self::PPT_PANEL_SCALE;
        $w = self::PPT_DETAIL_W * $s;
        $h = self::PPT_CONTENT_H * $s;

        $canvas = imagecreatetruecolor($w, $h);

        // The slide is white, so the panel is painted on white rather than left
        // transparent — it keeps the rounded pills free of fringing.
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefilledrectangle($canvas, 0, 0, $w, $h, imagecolorallocate($canvas, 255, 255, 255));
        imagealphablending($canvas, true);

        $orange = imagecolorallocate($canvas, ...self::PPT_ORANGE);
        $navy   = imagecolorallocate($canvas, ...self::PPT_NAVY);
        $grey   = imagecolorallocate($canvas, ...self::PPT_LABEL);
        $pill   = imagecolorallocate($canvas, ...self::PPT_PILL);
        $rule   = imagecolorallocate($canvas, ...self::PPT_RULE);
        $white  = imagecolorallocate($canvas, 255, 255, 255);

        /* ---------- heading ---------- */
        $headFont = 13 * $s;
        $badge    = 9 * $s;

        imagefilledellipse($canvas, $badge, $badge, $badge * 2, $badge * 2, $orange);
        $this->pptGlyph($canvas, $iconFont, 0xf3c5, 8 * $s, $badge, $badge, $white);

        $headX = 26 * $s;
        $headY = 14 * $s;

        imagettftext($canvas, $headFont, 0, $headX, $headY, $navy, $boldFont, 'SITE');

        $siteBox   = imagettfbbox($headFont, 0, $boldFont, 'SITE ');
        $siteWidth = $siteBox[2] - $siteBox[0];

        imagettftext($canvas, $headFont, 0, $headX + $siteWidth, $headY, $orange, $boldFont, 'DETAILS');

        // Orange rule under SITE only, as the format sheet has it.
        imagefilledrectangle(
            $canvas,
            $headX,
            $headY + (3 * $s),
            $headX + $siteWidth - (4 * $s),
            $headY + (4 * $s),
            $orange
        );

        /* ---------- rows ---------- */
        $top   = 26 * $s;
        $pitch = (int) floor(($h - $top) / max(1, count($rows)));
        $pillH = (int) round($pitch * 0.84);
        $font  = 7.5 * $s;

        // Columns, measured off the format sheet: icon, a hairline divider,
        // then the label, the colon and the value.
        $iconX    = 10 * $s;
        $ruleX    = 20 * $s;
        $labelX   = 27 * $s;

        $widest = 0;

        foreach (array_keys($rows) as $label) {
            $box    = imagettfbbox($font, 0, $textFont, $label);
            $widest = max($widest, $box[2] - $box[0]);
        }

        $colonX = $labelX + $widest + (5 * $s);
        $valueX = $colonX + (6 * $s);
        $valueW = $w - $valueX - (5 * $s);

        $index = 0;

        foreach ($rows as $label => $value) {
            $rowTop = $top + ($index * $pitch);
            $midY   = $rowTop + (int) round($pitch / 2);
            $halfH  = (int) round($pillH / 2);

            $this->pptRoundedRect(
                $canvas,
                0,
                $midY - $halfH,
                $w - 1,
                $midY + $halfH,
                (int) round($halfH * 0.55),
                $pill
            );

            // Plain orange glyph, no badge — the format sheet keeps the circles
            // for the heading only.
            $this->pptGlyph(
                $canvas,
                $iconFont,
                self::PPT_ROW_ICONS[$label] ?? 0xf111,
                8 * $s,
                $iconX,
                $midY,
                $orange
            );

            imagefilledrectangle(
                $canvas,
                $ruleX,
                $midY - (int) round($halfH * 0.5),
                $ruleX + max(1, (int) round(0.4 * $s)),
                $midY + (int) round($halfH * 0.5),
                $rule
            );

            $baseline = $midY + (int) round($font * 0.36);

            imagettftext($canvas, $font, 0, $labelX, $baseline, $grey, $textFont, $label);
            imagettftext($canvas, $font, 0, $colonX, $baseline, $grey, $textFont, ':');

            // Values vary wildly in length. Step the size down before letting
            // anything spill out of the panel, and only clip as a last resort.
            [$valueText, $valueSize] = $this->pptFitText((string) $value, $boldFont, $font, $valueW);

            imagettftext(
                $canvas,
                $valueSize,
                0,
                $valueX,
                $midY + (int) round($valueSize * 0.36),
                $navy,
                $boldFont,
                $valueText
            );

            $index++;
        }

        $path    = storage_path('app/temp_ppt_panel_' . md5(serialize($rows)) . '.png');
        $written = @imagepng($canvas, $path, 9);

        imagedestroy($canvas);

        if (!$written) {
            return null;
        }

        $tempPaths[] = $path;

        return $path;
    }

    /**
     * Shrink text until it fits the given width, clipping with an ellipsis only
     * once the smallest size still will not do.
     *
     * @return array{0:string,1:float} the text to draw and the size to draw it at
     */
    private function pptFitText(string $text, string $font, float $size, int $maxWidth): array
    {
        $width = static fn(string $t, float $s): int => (int) (
            ($box = imagettfbbox($s, 0, $font, $t)) ? $box[2] - $box[0] : 0
        );

        for ($try = $size; $try >= $size * 0.72; $try -= 1) {
            if ($width($text, $try) <= $maxWidth) {
                return [$text, $try];
            }
        }

        $small = $size * 0.72;

        while ($text !== '' && $width($text . '…', $small) > $maxWidth) {
            $text = mb_substr($text, 0, -1);
        }

        return [$text . '…', $small];
    }

    /** One Font Awesome glyph, centred on the given point. */
    private function pptGlyph(
        \GdImage $canvas,
        string $font,
        int $codepoint,
        float $size,
        int $centreX,
        int $centreY,
        int $colour
    ): void {
        $char = mb_chr($codepoint, 'UTF-8');

        if ($char === false) {
            return;
        }

        $box = imagettfbbox($size, 0, $font, $char);

        if ($box === false) {
            return;
        }

        imagettftext(
            $canvas,
            $size,
            0,
            $centreX - (int) round(($box[2] + $box[0]) / 2),
            $centreY - (int) round(($box[5] + $box[1]) / 2),
            $colour,
            $font,
            $char
        );
    }

    /** A filled rounded rectangle — GD has no primitive for one. */
    private function pptRoundedRect(
        \GdImage $canvas,
        int $left,
        int $top,
        int $right,
        int $bottom,
        int $radius,
        int $colour
    ): void {
        $radius = max(0, min($radius, (int) floor(($right - $left) / 2), (int) floor(($bottom - $top) / 2)));

        if ($radius === 0) {
            imagefilledrectangle($canvas, $left, $top, $right, $bottom, $colour);
            return;
        }

        imagefilledrectangle($canvas, $left + $radius, $top, $right - $radius, $bottom, $colour);
        imagefilledrectangle($canvas, $left, $top + $radius, $right, $bottom - $radius, $colour);

        $diameter = $radius * 2;

        imagefilledellipse($canvas, $left + $radius, $top + $radius, $diameter, $diameter, $colour);
        imagefilledellipse($canvas, $right - $radius, $top + $radius, $diameter, $diameter, $colour);
        imagefilledellipse($canvas, $left + $radius, $bottom - $radius, $diameter, $diameter, $colour);
        imagefilledellipse($canvas, $right - $radius, $bottom - $radius, $diameter, $diameter, $colour);
    }

    /**
     * Draw a picture inside a box without stretching it: scaled to fit, then
     * centred on whatever space is left over.
     *
     * @param array{path:string,width:int,height:int} $image
     */
    private function pptPlaceImage(
        \PhpOffice\PhpPresentation\Slide $slide,
        array $image,
        int $boxX,
        int $boxY,
        int $boxWidth,
        int $boxHeight
    ): void {
        $scale = min($boxWidth / $image['width'], $boxHeight / $image['height']);

        $drawWidth  = max(1, (int) round($image['width'] * $scale));
        $drawHeight = max(1, (int) round($image['height'] * $scale));

        try {
            $slide->createDrawingShape()
                ->setPath($image['path'])
                ->setResizeProportional(false)
                ->setWidth($drawWidth)
                ->setHeight($drawHeight)
                ->setOffsetX($boxX + (int) round(($boxWidth - $drawWidth) / 2))
                ->setOffsetY($boxY + (int) round(($boxHeight - $drawHeight) / 2));
        } catch (\Throwable $e) {
            // A single unreadable picture must not cost the whole deck.
            Log::warning('Campaign PPT: could not place image', [
                'path'    => $image['path'],
                'message' => $e->getMessage(),
            ]);
        }
    }
}

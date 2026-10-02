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
        // VERY IMPORTANT: Clean output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        /* ================= CAMPAIGN ================= */
        $campaign = DB::table('campaign')
            ->where('id', $campaignId)
            ->first();

        if (!$campaign) {
            throw new \Exception('Campaign not found');
        }

        /* ================= ITEMS ================= */
        $items = DB::table('cart_items as ci')
            ->join('media_management as m', 'm.id', '=', 'ci.media_id')
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
                'ci.from_date',
                'ci.to_date',
                'a.area_name',
                'a.common_stdiciar_name',
                'c.city_name',
                'i.illumination_name',
                'hw.highway_name',
                DB::raw('(SELECT GROUP_CONCAT(l.landmark_name SEPARATOR ", ") FROM media_landmark ml JOIN landmark l ON l.id = ml.landmark_id WHERE ml.media_id = m.id AND l.is_deleted = 0) as landmark_names'),
                'cat.category_name as media_type',
                'mi.all_images'
            )
            ->where('ci.campaign_id', $campaignId)
            ->where('ci.cart_type', 'CAMPAIGN')
            ->get();

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
        SLIDE 1 : COVER
        ===================================================== */
        $slide1 = $ppt->getActiveSlide();

        $this->pptBackdrop($slide1);

        $title = $slide1->createRichTextShape()
            ->setOffsetX(180)
            ->setOffsetY(205)
            ->setWidth(600)
            ->setHeight(150);

        $title->getActiveParagraph()
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $title->createTextRun("Campaign Name\n")
            ->getFont()->setSize(34)->setBold(true)
            ->setColor(new Color(self::PPT_INK));

        $title->createTextRun($campaign->campaign_name)
            ->getFont()->setSize(22)
            ->setColor(new Color(self::PPT_INK));

        /* =====================================================
        MEDIA SLIDES — image 70% / details 30%
        ===================================================== */
        foreach ($items as $item) {

            $slide = $ppt->createSlide();

            $this->pptBackdrop($slide);

            /* ---------- TITLE ---------- */
            $heading = $slide->createRichTextShape()
                ->setOffsetX(self::PPT_MARGIN)
                ->setOffsetY(78)
                ->setWidth(self::PPT_W - (2 * self::PPT_MARGIN))
                ->setHeight(34);

            $heading->createTextRun(trim(
                ($item->media_title ?: $item->media_type) . ' ' . ($item->area_name ?? '')
            ))
                ->getFont()->setSize(22)->setBold(true)
                ->setColor(new Color(self::PPT_INK));

            /* ---------- IMAGES : LEFT 70% ---------- */
            $images = [];

            foreach (explode(',', (string) $item->all_images) as $stored) {
                $prepared = $this->pptImage($stored, $tempPaths);

                if ($prepared !== null) {
                    $images[] = $prepared;
                }

                // Four is all the panel can show: one hero plus a strip of three.
                if (count($images) === 4) {
                    break;
                }
            }

            if ($images === []) {
                $placeholder = $slide->createRichTextShape()
                    ->setOffsetX(self::PPT_PANEL_X)
                    ->setOffsetY(self::PPT_PANEL_Y)
                    ->setWidth(self::PPT_PANEL_W)
                    ->setHeight(self::PPT_PANEL_H);

                $placeholder->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->setStartColor(new Color('FFF2F2F2'));

                $placeholder->getActiveParagraph()->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $placeholder->createTextRun('NO IMAGES AVAILABLE')
                    ->getFont()->setSize(16)->setBold(true)
                    ->setColor(new Color('FF8A8A8A'));
            } else {
                // One image fills the panel. More than one and the first keeps
                // the top of it, with the rest as a strip underneath.
                $heroHeight = count($images) > 1
                    ? self::PPT_HERO_H
                    : self::PPT_PANEL_H;

                $this->pptPlaceImage(
                    $slide,
                    array_shift($images),
                    self::PPT_PANEL_X,
                    self::PPT_PANEL_Y,
                    self::PPT_PANEL_W,
                    $heroHeight
                );

                $thumbGap   = 8;
                $thumbWidth = (int) floor((self::PPT_PANEL_W - (2 * $thumbGap)) / 3);
                $thumbY     = self::PPT_PANEL_Y + self::PPT_HERO_H + 12;
                $thumbH     = self::PPT_PANEL_Y + self::PPT_PANEL_H - $thumbY;

                foreach (array_values($images) as $index => $thumb) {
                    $this->pptPlaceImage(
                        $slide,
                        $thumb,
                        self::PPT_PANEL_X + ($index * ($thumbWidth + $thumbGap)),
                        $thumbY,
                        $thumbWidth,
                        $thumbH
                    );
                }
            }

            /* ---------- SITE DETAILS : RIGHT 30% ---------- */
            $from = $item->from_date
                ? \Carbon\Carbon::parse($item->from_date)->format('d M Y')
                : '-';

            $to = $item->to_date
                ? \Carbon\Carbon::parse($item->to_date)->format('d M Y')
                : '-';

            $rows = [
                'Code'       => $item->hoarding_code ?: '-',
                'Location'   => $item->common_stdiciar_name ?: '-',
                'Area'       => $item->area_name ?: '-',
                'City'       => $item->city_name ?: '-',
                'Size'       => $item->width . ' × ' . $item->height,
                'Media type' => $item->media_type ?: '-',
                'Price'      => '₹ ' . number_format((float) $item->price),
                'From Date'  => $from,
                'To Date'    => $to,
                'Lighting'   => $item->illumination_name ?: '-',
                'Highway'    => $item->highway_name ?: '-',
                'Landmarks'  => $item->landmark_names ?: '-',
            ];

            $panel = $this->pptDetailsPanel($rows, $tempPaths);

            if ($panel !== null) {
                $slide->createDrawingShape()
                    ->setPath($panel)
                    ->setResizeProportional(false)
                    ->setWidth(self::PPT_DETAIL_W)
                    ->setHeight(self::PPT_PANEL_H)
                    ->setOffsetX(self::PPT_DETAIL_X)
                    ->setOffsetY(self::PPT_PANEL_Y);
            }
        }

        /* =====================================================
        LAST SLIDE : THANK YOU
        ===================================================== */
        $last = $ppt->createSlide();

        $this->pptBackdrop($last);

        $thanks = $last->createRichTextShape()
            ->setOffsetX(180)
            ->setOffsetY(230)
            ->setWidth(600)
            ->setHeight(90);

        $thanks->getActiveParagraph()->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $thanks->createTextRun('Thank You..!')
            ->getFont()->setSize(40)->setBold(true)->setItalic(true)
            ->setColor(new Color(self::PPT_INK));

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

    private const PPT_W      = 960;
    private const PPT_H      = 540;
    private const PPT_MARGIN = 28;

    /** Body text colour — dark, for the light centre of the backdrop. */
    private const PPT_INK = 'FF1A1A1A';

    /** Picture panel: the left 70% of the slide. */
    private const PPT_PANEL_X = 28;
    private const PPT_PANEL_Y = 124;
    private const PPT_PANEL_W = 644;   // 28 + 644 = 672 = 70% of 960
    private const PPT_PANEL_H = 386;

    /** Height the first picture keeps when a thumbnail strip sits below it. */
    private const PPT_HERO_H = 288;

    /** Details column: the right 30%. */
    private const PPT_DETAIL_X = 688;
    private const PPT_DETAIL_W = 244;


    /** Longest edge a picture keeps once it is in the deck. */
    private const PPT_IMAGE_MAX_EDGE = 1400;

    /** JPEG quality for everything this class re-encodes. */
    private const PPT_JPEG_QUALITY = 82;

    /** Pixel size of the cached backdrop — 16:9, sharp on a projector. */
    private const PPT_BACKDROP_W = 1440;
    private const PPT_BACKDROP_H = 810;

    /** Where the two corner logos sit, in slide pixels. */
    private const PPT_LOGO_H = 44;
    private const PPT_LOGO_Y = 24;

    /**
     * Backdrop plus logo — on every slide, so the deck reads as one piece.
     *
     * One picture, not two: PhpPresentation stores a separate copy of every
     * drawing on every slide, so a 1 MB background and a logo would be re-
     * embedded per slide and a twenty site campaign would weigh tens of MB.
     * They are composited once into a small cached JPEG instead.
     */
    private function pptBackdrop(\PhpOffice\PhpPresentation\Slide $slide): void
    {
        $backdrop = $this->pptBackdropFile();

        if ($backdrop === null) {
            return;
        }

        $slide->createDrawingShape()
            ->setPath($backdrop)
            ->setResizeProportional(false)
            ->setWidth(self::PPT_W)
            ->setHeight(self::PPT_H)
            ->setOffsetX(0)
            ->setOffsetY(0);
    }

    /**
     * Build (or reuse) the composited backdrop: the campaign background with
     * the logo burned into its top-left corner.
     *
     * Cached under storage/app/ppt, keyed on both source files, so it survives
     * between downloads and rebuilds by itself if either artwork is replaced.
     */
    private function pptBackdropFile(): ?string
    {
        $background = public_path('assets/img/logo/brand_adda_ppt.png');

        if (!is_file($background)) {
            return null;
        }

        // Brand Adda, top-right. The .png rather than the .webp twin:
        // PowerPoint before 365 cannot draw WebP, and a logo that fails to
        // render is worse than no logo.
        $logos = [
            'right' => public_path('assets/img/logo/brand_adda.png'),
        ];

        $key = md5(implode('|', [
            $background,
            (string) @filemtime($background),
            // Keyed on whichever logos are in play, so adding, moving or
            // replacing one rebuilds the backdrop by itself.
            implode(',', array_map(
                fn(string $align, string $path) => $align . ':' . (is_file($path) ? @filemtime($path) : 'missing'),
                array_keys($logos),
                $logos
            )),
            self::PPT_BACKDROP_W . 'x' . self::PPT_BACKDROP_H,
        ]));

        $cacheDir  = storage_path('app/ppt');
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'backdrop_' . $key . '.jpg';

        if (is_file($cacheFile)) {
            return $cacheFile;
        }

        if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
            return null;
        }

        $source = @imagecreatefromstring((string) @file_get_contents($background));

        if ($source === false) {
            return null;
        }

        $canvas = imagecreatetruecolor(self::PPT_BACKDROP_W, self::PPT_BACKDROP_H);

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

        foreach ($logos as $align => $logo) {
            if (is_file($logo)) {
                $this->pptStampLogo($canvas, $logo, $align);
            }
        }

        $written = @imagejpeg($canvas, $cacheFile, self::PPT_JPEG_QUALITY);
        imagedestroy($canvas);

        if (!$written) {
            return null;
        }

        return $cacheFile;
    }

    /**
     * Draw a logo into one of the backdrop's top corners, on the slide margin
     * the shapes themselves use.
     *
     * @param 'left'|'right' $align which corner it sits in
     */
    private function pptStampLogo(\GdImage $canvas, string $logo, string $align): void
    {
        $mark = @imagecreatefromstring((string) @file_get_contents($logo));

        if ($mark === false) {
            return;
        }

        // The backdrop is drawn larger than the slide so it stays sharp when
        // projected; everything placed on it scales by the same factor.
        $ratio = self::PPT_BACKDROP_H / self::PPT_H;

        $height = (int) round(self::PPT_LOGO_H * $ratio);
        $width  = (int) round($height * (imagesx($mark) / imagesy($mark)));

        $x = $align === 'right'
            ? (int) round((self::PPT_W - self::PPT_MARGIN) * $ratio) - $width
            : (int) round(self::PPT_MARGIN * $ratio);

        imagealphablending($canvas, true);

        imagecopyresampled(
            $canvas,
            $mark,
            $x,
            (int) round(self::PPT_LOGO_Y * $ratio),
            0,
            0,
            $width,
            $height,
            imagesx($mark),
            imagesy($mark)
        );

        imagedestroy($mark);
    }

    /**
     * Make one stored media image usable in a .pptx.
     *
     * Two things are wrong with handing the stored file straight over. Most of
     * the inventory is WebP, which PowerPoint cannot draw at all; and the
     * originals are far larger than the panel they are shown in, which matters
     * because every slide carries its own copy. Both are settled here: decode
     * whatever it is, shrink it to the longest edge the deck needs, and write
     * a JPEG.
     *
     * Returns the path and the size it was written at, or null when the file is
     * missing or will not decode.
     *
     * @param  list<string> $tempPaths  collects temp files for the caller to delete
     * @return array{path:string,width:int,height:int}|null
     */
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
    private const PPT_VALUE  = [22, 36, 63];      // navy, for the values
    private const PPT_LABEL  = [85, 96, 110];     // grey, for the labels

    /** Font Awesome Solid glyph per row, keyed on the label. */
    private const PPT_ROW_ICONS = [
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
        $h = self::PPT_PANEL_H * $s;

        $canvas = imagecreatetruecolor($w, $h);

        // Transparent: the panel sits on the campaign backdrop, and the pills
        // are meant to let that gradient show through.
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefilledrectangle($canvas, 0, 0, $w, $h, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagealphablending($canvas, true);

        $orange = imagecolorallocate($canvas, ...self::PPT_ORANGE);
        $navy   = imagecolorallocate($canvas, ...self::PPT_VALUE);
        $grey   = imagecolorallocate($canvas, ...self::PPT_LABEL);
        $white  = imagecolorallocate($canvas, 255, 255, 255);
        $pill   = imagecolorallocatealpha($canvas, 255, 255, 255, 18);

        /* ---------- heading ---------- */
        $headFont = 12 * $s;
        $badge    = 10 * $s;

        imagefilledellipse($canvas, $badge, $badge + (2 * $s), $badge * 2, $badge * 2, $orange);
        $this->pptGlyph($canvas, $iconFont, 0xf3c5, 9 * $s, $badge, $badge + (2 * $s), $white);

        $headX = 27 * $s;
        $headY = 16 * $s;

        imagettftext($canvas, $headFont, 0, $headX, $headY, $navy, $boldFont, 'SITE');

        $siteBox   = imagettfbbox($headFont, 0, $boldFont, 'SITE ');
        $siteWidth = $siteBox[2] - $siteBox[0];

        imagettftext($canvas, $headFont, 0, $headX + $siteWidth, $headY, $orange, $boldFont, 'DETAILS');

        // Orange rule under SITE only, as in the reference.
        imagefilledrectangle(
            $canvas,
            $headX,
            $headY + (3 * $s),
            $headX + ($siteBox[2] - $siteBox[0]) - (4 * $s),
            $headY + (5 * $s),
            $orange
        );

        /* ---------- rows ---------- */
        $top    = 28 * $s;
        $pitch  = (int) floor(($h - $top - (5 * $s)) / max(1, count($rows)));
        $pillH  = (int) round($pitch * 0.78);
        $circleR = (int) round($pitch * 0.36);
        $font   = 8 * $s;

        // The colon lines up past the widest label, so the values form a column.
        $labelX   = 27 * $s;
        $widest   = 0;

        foreach (array_keys($rows) as $label) {
            $box    = imagettfbbox($font, 0, $textFont, $label);
            $widest = max($widest, $box[2] - $box[0]);
        }

        $colonX = $labelX + $widest + (5 * $s);
        $valueX = $colonX + (7 * $s);
        $valueW = $w - $valueX - (4 * $s);

        $index = 0;

        foreach ($rows as $label => $value) {
            $rowTop = $top + ($index * $pitch);
            $midY   = $rowTop + (int) round($pitch / 2);

            $this->pptRoundedRect(
                $canvas,
                $circleR,
                $midY - (int) round($pillH / 2),
                $w - 1,
                $midY + (int) round($pillH / 2),
                (int) round($pillH / 2),
                $pill
            );

            imagefilledellipse($canvas, $circleR, $midY, $circleR * 2, $circleR * 2, $orange);

            $this->pptGlyph(
                $canvas,
                $iconFont,
                self::PPT_ROW_ICONS[$label] ?? 0xf111,
                (int) round($circleR * 0.9),
                $circleR,
                $midY,
                $white
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

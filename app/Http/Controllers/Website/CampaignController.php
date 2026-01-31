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

            //  MAIL CALL
            $this->campaignService->sendCampaignMailToAdmin(
                Auth::guard('website')->id()
            );

            return redirect()
                ->route('campaigns.open')
                ->with('success', 'Campaign created successfully');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }


    public function openCampaigns(Request $request)
    {
        $campaigns = $this->campaignService->getOpenCampaigns(
            Auth::guard('website')->id(),
            $request
        );

        return view('website.campaign-list', [
            'campaigns' => $campaigns,
            'type'      => 'open',
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
            Log::error($e->getMessage());
            return redirect()->route('campaign.list')
                ->with('error', 'Campaign details not found');
        }
    }
    public function exportExcel($campaignId)
    {
        $campaignId = base64_decode($campaignId);

        return Excel::download(
            new CampaignExport(
                Auth::guard('website')->id(),
                $campaignId
            ),
            'campaign_items.xlsx'
        );
    }
    // public function exportPpt($campaignId)
    // {
    //     while (ob_get_level() > 0) {
    //         ob_end_clean();
    //     }

    //     $campaignId = base64_decode($campaignId);

    //     /* ================= CAMPAIGN ================= */
    //     $campaign = DB::table('campaign')
    //         ->where('id', $campaignId)
    //         ->first();

    //     if (!$campaign) {
    //         abort(404, 'Campaign not found');
    //     }

    //     $items = DB::table('cart_items as ci')
    //         ->join('media_management as m', 'm.id', '=', 'ci.media_id')
    //         ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
    //         ->leftJoin('cities as c', 'c.id', '=', 'm.city_id')
    //         ->leftJoin('illuminations as i', 'i.id', '=', 'm.illumination_id')
    //         ->leftJoin('category as cat', 'cat.id', '=', 'm.category_id') //  NEW JOIN
    //         ->leftJoin(DB::raw("
    //     (
    //         SELECT media_id, GROUP_CONCAT(images) AS all_images
    //         FROM media_images
    //         WHERE is_deleted = 0
    //         GROUP BY media_id
    //     ) mi
    // "), 'mi.media_id', '=', 'm.id')
    //         ->select(
    //             'm.id as media_id',
    //             'm.media_title',
    //             'm.width',
    //             'm.height',
    //             'm.price',
    //             'ci.from_date',
    //             'ci.to_date',
    //             'a.area_name',
    //             'a.common_stdiciar_name',
    //             'c.city_name as city_name',
    //             'i.illumination_name',
    //             'cat.category_name as media_type', //  GET CATEGORY NAME
    //             'mi.all_images'
    //         )
    //         ->where('ci.campaign_id', $campaignId)
    //         ->where('ci.cart_type', 'CAMPAIGN')
    //         ->get();





    //     /* ================= INIT PPT ================= */
    //     $ppt = new PhpPresentation();

    //     /* =====================================================
    //    SLIDE 1 : COVER SLIDE
    // ===================================================== */
    //     $slide1 = $ppt->getActiveSlide();

    //     // Background
    //     $bg = $slide1->createDrawingShape();
    //     $bg->setPath(public_path('asset/theamoriginalalf/images/bluebg.png'))
    //         ->setWidth(960)
    //         ->setHeight(540)
    //         ->setOffsetX(0)
    //         ->setOffsetY(0);

    //     // Logo
    //     $logo = $slide1->createDrawingShape();
    //     $logo->setPath(public_path('asset/theamoriginalalf/images/logo.png'))
    //         ->setHeight(60)
    //         ->setOffsetX(40)
    //         ->setOffsetY(40);

    //     // Title
    //     $title = $slide1->createRichTextShape()
    //         ->setOffsetX(260)
    //         ->setOffsetY(220)
    //         ->setWidth(500);

    //     $title->getActiveParagraph()
    //         ->getAlignment()
    //         ->setHorizontal(Alignment::HORIZONTAL_CENTER);

    //     $title->createTextRun("Campaing Name\n")
    //         ->getFont()->setSize(34)->setBold(true);

    //     $title->createTextRun($campaign->campaign_name)
    //         ->getFont()->setSize(22);

    //     foreach ($items as $item) {

    //         $slide = $ppt->createSlide();

    //         /* ---------- TITLE ---------- */
    //         $siteTitle = $slide->createRichTextShape()
    //             ->setOffsetX(40)
    //             ->setOffsetY(20)
    //             ->setWidth(850);

    //         $siteTitle->getActiveParagraph()->getAlignment()
    //             ->setHorizontal(Alignment::HORIZONTAL_LEFT);

    //         $siteTitle->createTextRun($item->media_title)
    //             ->getFont()->setSize(26)->setBold(true);

    //         /* ---------- IMAGES LEFT COLUMN ---------- */
    //         $images = !empty($item->all_images) ? explode(',', $item->all_images) : [];

    //         $x = 40;   // Left padding
    //         $y = 90;   // Top padding
    //         $w = 200;  // Image width
    //         $h = 140;  // Image height
    //         $gap = 10; // Space between images
    //         $maxWidth = 420; // Keep images IN left column

    //         $hasValidImage = false;

    //         if (!empty($images)) {
    //             foreach ($images as $img) {

    //                 $path = public_path('storage/upload/images/media/' . trim($img));

    //                 // 🔐 HARD SAFETY CHECKS
    //                 if (
    //                     empty($img) ||
    //                     !file_exists($path) ||
    //                     !is_readable($path) ||
    //                     filesize($path) === 0 ||
    //                     filesize($path) > (3 * 1024 * 1024) // 3MB limit
    //                 ) {
    //                     continue;
    //                 }

    //                 try {
    //                     $slide->createDrawingShape()
    //                         ->setPath($path)
    //                         ->setWidth($w)
    //                         ->setHeight($h)
    //                         ->setOffsetX($x)
    //                         ->setOffsetY($y);

    //                     $hasValidImage = true;

    //                     $x += $w + $gap;

    //                     if ($x > $maxWidth) {
    //                         $x = 40;
    //                         $y += $h + $gap;
    //                     }
    //                 } catch (\Throwable $e) {
    //                     // image corrupt असेल तरी PPT break होणार नाही
    //                     continue;
    //                 }
    //             }
    //         } else {
    //             // If no images
    //             $placeholder = $slide->createRichTextShape()
    //                 ->setOffsetX(40)->setOffsetY(90)
    //                 ->setWidth(360)->setHeight(240);

    //             $placeholder->getFill()->setFillType(Fill::FILL_SOLID)
    //                 ->setStartColor(new Color('FFEFEFEF'));

    //             $placeholder->createTextRun("NO IMAGES AVAILABLE")
    //                 ->getFont()->setSize(18)->setBold(true);
    //         }

    //         /* ---------- SITE DETAILS RIGHT COLUMN ---------- */
    //         $details = $slide->createRichTextShape()
    //             ->setOffsetX(520)   // right column start
    //             ->setOffsetY(90)
    //             ->setWidth(420);

    //         $details->createTextRun("SITE DETAILS\n\n")
    //             ->getFont()->setSize(22)->setBold(true);


    //         $from = $item->from_date
    //             ? \Carbon\Carbon::parse($item->from_date)->format('d M Y')
    //             : '-';

    //         $to = $item->to_date
    //             ? \Carbon\Carbon::parse($item->to_date)->format('d M Y')
    //             : '-';


    //         $details->getActiveParagraph()->getAlignment()
    //             ->setHorizontal(Alignment::HORIZONTAL_LEFT);

    //         $details->createTextRun(

    //             "Location  : {$item->common_stdiciar_name}\n" .
    //                 "Area      : {$item->area_name}\n" .
    //                 "City      : {$item->city_name}\n" .
    //                 "Size      : {$item->width} × {$item->height}\n" .
    //                 "Media type: {$item->media_type}\n" .
    //                 "Price     : ₹ " . number_format($item->price) . "\n" .
    //                 "From Date : $from\n" .
    //                 "To Date   : $to\n" .
    //                 "Lighting  : {$item->illumination_name}\n"
    //         )->getFont()->setSize(18);
    //     }



    //     /* =====================================================
    //    LAST SLIDE : THANK YOU
    // ===================================================== */
    //     $thankSlide = $ppt->createSlide();

    //     $thankBg = $thankSlide->createDrawingShape();
    //     $thankBg->setPath(public_path('asset/theamoriginalalf/images/thankyou.png'))
    //         ->setWidth(960)
    //         ->setHeight(540)
    //         ->setOffsetX(0)
    //         ->setOffsetY(0);

    //     /* ================= DOWNLOAD ================= */
    //     $campaignId = base64_decode($campaignId);


    //     $campaignName = preg_replace('/[^A-Za-z0-9_-]/', '_', $campaign->campaign_name);
    //     $fileName = $campaignName . '_' . now()->format('d-m-Y') . '.pptx';

    //     $writer = IOFactory::createWriter($ppt, 'PowerPoint2007');

    //     // return response()->streamDownload(function () use ($writer) {
    //     //     $writer->save('php://output');
    //     // }, $fileName);
    //     return response()->streamDownload(function () use ($writer) {

    //         while (ob_get_level() > 0) {
    //             ob_end_clean();
    //         }

    //         $writer->save('php://output');
    //     }, $fileName, [
    //         'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    //         'Cache-Control' => 'no-store, no-cache',
    //     ]);
    // }

    public function exportPpt($campaignId)
    {
        $campaignId = base64_decode($campaignId);

        $binary = $this->generatePptBinary($campaignId);

        $campaign = DB::table('campaign')->where('id', $campaignId)->first();

        $fileName = preg_replace(
            '/[^A-Za-z0-9_-]/',
            '_',
            $campaign->campaign_name
        ) . '_' . now()->format('d-m-Y') . '.pptx';

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
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
                'm.width',
                'm.height',
                'm.price',
                'ci.from_date',
                'ci.to_date',
                'a.area_name',
                'a.common_stdiciar_name',
                'c.city_name',
                'i.illumination_name',
                'cat.category_name as media_type',
                'mi.all_images'
            )
            ->where('ci.campaign_id', $campaignId)
            ->where('ci.cart_type', 'CAMPAIGN')
            ->get();

        /* ================= INIT PPT ================= */
        $ppt = new PhpPresentation();

        /* =====================================================
        SLIDE 1 : COVER SLIDE
        ===================================================== */
        $slide1 = $ppt->getActiveSlide();

        // Background
        $slide1->createDrawingShape()
            ->setPath(public_path('asset/theamoriginalalf/images/bluebg.png'))
            ->setWidth(960)
            ->setHeight(540)
            ->setOffsetX(0)
            ->setOffsetY(0);

        // Logo
        $slide1->createDrawingShape()
            ->setPath(public_path('asset/theamoriginalalf/images/logo.png'))
            ->setHeight(60)
            ->setOffsetX(40)
            ->setOffsetY(40);

        // Title
        $title = $slide1->createRichTextShape()
            ->setOffsetX(260)
            ->setOffsetY(220)
            ->setWidth(500);

        $title->getActiveParagraph()
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $title->createTextRun("Campaign Name\n")
            ->getFont()->setSize(34)->setBold(true);

        $title->createTextRun($campaign->campaign_name)
            ->getFont()->setSize(22);

        /* =====================================================
        MEDIA SLIDES
        ===================================================== */
        foreach ($items as $item) {

            $slide = $ppt->createSlide();

            /* ---------- TITLE ---------- */
            $slide->createRichTextShape()
                ->setOffsetX(40)
                ->setOffsetY(20)
                ->setWidth(850)
                ->createTextRun($item->media_title)
                ->getFont()->setSize(26)->setBold(true);

            /* ---------- IMAGES LEFT COLUMN ---------- */
            $images = !empty($item->all_images)
                ? explode(',', $item->all_images)
                : [];

            $x = 40;
            $y = 90;
            $w = 200;
            $h = 140;
            $gap = 10;
            $maxWidth = 420;

            $hasValidImage = false;

            foreach ($images as $img) {

                $path = public_path('storage/upload/images/media/' . trim($img));

                // HARD SAFETY CHECKS
                if (
                    empty($img) ||
                    !file_exists($path) ||
                    !is_readable($path) ||
                    filesize($path) === 0 ||
                    filesize($path) > (3 * 1024 * 1024)
                ) {
                    continue;
                }

                try {
                    $slide->createDrawingShape()
                        ->setPath($path)
                        ->setWidth($w)
                        ->setHeight($h)
                        ->setOffsetX($x)
                        ->setOffsetY($y);

                    $hasValidImage = true;

                    $x += $w + $gap;

                    if ($x > $maxWidth) {
                        $x = 40;
                        $y += $h + $gap;
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }

            // Placeholder if no images
            if (!$hasValidImage) {
                $placeholder = $slide->createRichTextShape()
                    ->setOffsetX(40)
                    ->setOffsetY(90)
                    ->setWidth(360)
                    ->setHeight(240);

                $placeholder->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->setStartColor(new Color('FFEFEFEF'));

                $placeholder->createTextRun('NO IMAGES AVAILABLE')
                    ->getFont()->setSize(18)->setBold(true);
            }

            /* ---------- SITE DETAILS RIGHT COLUMN ---------- */
            $from = $item->from_date
                ? \Carbon\Carbon::parse($item->from_date)->format('d M Y')
                : '-';

            $to = $item->to_date
                ? \Carbon\Carbon::parse($item->to_date)->format('d M Y')
                : '-';

            $details = $slide->createRichTextShape()
                ->setOffsetX(520)
                ->setOffsetY(90)
                ->setWidth(420);

            $details->getActiveParagraph()->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $details->createTextRun(
                "SITE DETAILS\n\n" .
                "Location  : {$item->common_stdiciar_name}\n" .
                "Area      : {$item->area_name}\n" .
                "City      : {$item->city_name}\n" .
                "Size      : {$item->width} × {$item->height}\n" .
                "Media type: {$item->media_type}\n" .
                "Price     : ₹ " . number_format($item->price) . "\n" .
                "From Date : $from\n" .
                "To Date   : $to\n" .
                "Lighting  : {$item->illumination_name}\n"
            )->getFont()->setSize(18);
        }

        /* =====================================================
        LAST SLIDE : THANK YOU
        ===================================================== */
        $ppt->createSlide()
            ->createDrawingShape()
            ->setPath(public_path('asset/theamoriginalalf/images/thankyou.png'))
            ->setWidth(960)
            ->setHeight(540)
            ->setOffsetX(0)
            ->setOffsetY(0);

        /* ================= RETURN BINARY ================= */
        $writer = IOFactory::createWriter($ppt, 'PowerPoint2007');

        ob_start();
        $writer->save('php://output');
        return ob_get_clean(); // FINAL PPT CONTENT
    }

    // public function generatePptFile(int $campaignId, string $savePath): void
    // {
    //     // clean output buffer (VERY IMPORTANT for PPT)
    //     while (ob_get_level() > 0) {
    //         ob_end_clean();
    //     }

    //     /* ================= CAMPAIGN ================= */
    //     $campaign = DB::table('campaign')
    //         ->where('id', $campaignId)
    //         ->first();

    //     if (!$campaign) {
    //         throw new \Exception('Campaign not found');
    //     }

    //     $items = DB::table('cart_items as ci')
    //         ->join('media_management as m', 'm.id', '=', 'ci.media_id')
    //         ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
    //         ->leftJoin('cities as c', 'c.id', '=', 'm.city_id')
    //         ->leftJoin('illuminations as i', 'i.id', '=', 'm.illumination_id')
    //         ->leftJoin('category as cat', 'cat.id', '=', 'm.category_id')
    //         ->leftJoin(DB::raw("
    //         (
    //             SELECT media_id, GROUP_CONCAT(images) AS all_images
    //             FROM media_images
    //             WHERE is_deleted = 0
    //             GROUP BY media_id
    //         ) mi
    //     "), 'mi.media_id', '=', 'm.id')
    //         ->select(
    //             'm.media_title',
    //             'm.width',
    //             'm.height',
    //             'm.price',
    //             'ci.from_date',
    //             'ci.to_date',
    //             'a.area_name',
    //             'a.common_stdiciar_name',
    //             'c.city_name',
    //             'i.illumination_name',
    //             'cat.category_name as media_type',
    //             'mi.all_images'
    //         )
    //         ->where('ci.campaign_id', $campaignId)
    //         ->where('ci.cart_type', 'CAMPAIGN')
    //         ->get();

    //     /* ================= INIT PPT ================= */
    //     $ppt = new PhpPresentation();

    //     /* ================= COVER SLIDE ================= */
    //     $slide1 = $ppt->getActiveSlide();

    //     // Background
    //     $slide1->createDrawingShape()
    //         ->setPath(public_path('asset/theamoriginalalf/images/bluebg.png'))
    //         ->setWidth(960)->setHeight(540);

    //     // Logo
    //     $slide1->createDrawingShape()
    //         ->setPath(public_path('asset/theamoriginalalf/images/logo.png'))
    //         ->setHeight(60)->setOffsetX(40)->setOffsetY(40);

    //     // Title
    //     $title = $slide1->createRichTextShape()
    //         ->setOffsetX(260)->setOffsetY(220)->setWidth(500);

    //     $title->getActiveParagraph()->getAlignment()
    //         ->setHorizontal(Alignment::HORIZONTAL_CENTER);

    //     $title->createTextRun("Campaign Name\n")
    //         ->getFont()->setSize(34)->setBold(true);

    //     $title->createTextRun($campaign->campaign_name)
    //         ->getFont()->setSize(22);

    //     /* ================= MEDIA SLIDES ================= */
    //     foreach ($items as $item) {

    //         $slide = $ppt->createSlide();

    //         // Title
    //         $slide->createRichTextShape()
    //             ->setOffsetX(40)->setOffsetY(20)->setWidth(850)
    //             ->createTextRun($item->media_title)
    //             ->getFont()->setSize(26)->setBold(true);

    //         /* ---------- IMAGES ---------- */
    //         $images = $item->all_images ? explode(',', $item->all_images) : [];

    //         $x = 40;
    //         $y = 90;
    //         $w = 200;
    //         $h = 140;
    //         $gap = 10;
    //         $hasImage = false;

    //         foreach ($images as $img) {

    //             $path = public_path('storage/upload/images/media/' . trim($img));

    //             if (!file_exists($path) || !is_readable($path) || filesize($path) === 0) {
    //                 continue;
    //             }

    //             try {
    //                 $slide->createDrawingShape()
    //                     ->setPath($path)
    //                     ->setWidth($w)->setHeight($h)
    //                     ->setOffsetX($x)->setOffsetY($y);

    //                 $hasImage = true;
    //                 $x += $w + $gap;

    //                 if ($x > 420) {
    //                     $x = 40;
    //                     $y += $h + $gap;
    //                 }
    //             } catch (\Throwable $e) {
    //                 continue;
    //             }
    //         }

    //         // Placeholder if no images
    //         if (!$hasImage) {
    //             $ph = $slide->createRichTextShape()
    //                 ->setOffsetX(40)->setOffsetY(90)
    //                 ->setWidth(360)->setHeight(240);

    //             $ph->getFill()->setFillType(Fill::FILL_SOLID)
    //                 ->setStartColor(new Color('FFEFEFEF'));

    //             $ph->createTextRun('NO IMAGES AVAILABLE')
    //                 ->getFont()->setSize(18)->setBold(true);
    //         }



    //         /* ---------- SITE DETAILS RIGHT COLUMN ---------- */
    //         $from = $item->from_date
    //             ? \Carbon\Carbon::parse($item->from_date)->format('d M Y')
    //             : '-';

    //         $to = $item->to_date
    //             ? \Carbon\Carbon::parse($item->to_date)->format('d M Y')
    //             : '-';

    //         $details = $slide->createRichTextShape()
    //             ->setOffsetX(520)
    //             ->setOffsetY(90)
    //             ->setWidth(420);

    //         $details->getActiveParagraph()->getAlignment()
    //             ->setHorizontal(Alignment::HORIZONTAL_LEFT);

    //         $details->createTextRun(
    //             "SITE DETAILS\n\n" .
    //                 "Location  : {$item->common_stdiciar_name}\n" .
    //                 "Area      : {$item->area_name}\n" .
    //                 "City      : {$item->city_name}\n" .
    //                 "Size      : {$item->width} × {$item->height}\n" .
    //                 "Media type: {$item->media_type}\n" .
    //                 "Price     : ₹ " . number_format($item->price) . "\n" .
    //                 "From Date : $from\n" .
    //                 "To Date   : $to\n" .
    //                 "Lighting  : {$item->illumination_name}\n"
    //         )->getFont()->setSize(18);
    //     }



    //     /* ================= THANK YOU SLIDE ================= */
    //     $ppt->createSlide()
    //         ->createDrawingShape()
    //         ->setPath(public_path('asset/theamoriginalalf/images/thankyou.png'))
    //         ->setWidth(960)->setHeight(540);

    //     /* ================= SAVE FILE ================= */
    //     $writer = IOFactory::createWriter($ppt, 'PowerPoint2007');
    //     $writer->save($savePath); //  FINAL OUTPUT
    // }
}

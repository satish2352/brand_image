<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Services\Website\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Exports\CampaignExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CampaignExcelExport;
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

            return redirect()
                ->route('campaign.list')
                ->with('success', 'Campaign created successfully');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }
    // public function getCampaignList(Request $request)
    // {
    //     try {
    //         $userId = Auth::guard('website')->id();

    //         $campaigns = $this->campaignService->getCampaignList(
    //             $userId,
    //             $request
    //         );

    //         // ✅ FETCH INVOICE & PAYMENTS DATA
    //         $payments = $this->campaignService->getInvoicePayments($userId);

    //         return view(
    //             'website.campaign-list',
    //             compact('campaigns', 'payments')
    //         );
    //     } catch (\Exception $e) {
    //         Log::error($e->getMessage());
    //         return back()->with('error', 'Unable to load campaigns');
    //     }
    // }
    public function getCampaignList(Request $request)
    {
        try {
            $userId = Auth::guard('website')->id();

            if (!$userId) {
                return redirect()->route('website.login');
            }

            $campaigns = $this->campaignService->getCampaignList(
                $userId,
                $request
            );

            $payments = $this->campaignService->getInvoicePayments($userId);

            return view('website.campaign-list', [
                'campaigns' => $campaigns,
                'payments'  => $payments
            ]);
        } catch (\Throwable $e) {
            Log::error('Campaign List Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile()
            ]);

            abort(500, 'Campaign page failed');
        }
    }

    // public function getCampaignList(Request $request)
    // {
    //     try {
    //         $campaigns = $this->campaignService->getCampaignList(
    //             Auth::guard('website')->id(),
    //             $request
    //         );
    //         // dd($campaigns);

    //         return view('website.campaign-list', compact('campaigns'));
    //     } catch (\Exception $e) {
    //         Log::error($e->getMessage());
    //         return back()->with('error', 'Unable to load campaigns');
    //     }
    // }
    public function viewDetails($cartItemId)
    {
        try {

            $cartItemId = base64_decode($cartItemId); // 🔐 DECRYPT HERE

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

    // public function exportExcel(Request $request)
    // {
    //     try {
    //         return Excel::download(
    //             new CampaignExcelExport(Auth::guard('website')->id()),
    //             'campaign_list.xlsx'
    //         );
    //     } catch (\Exception $e) {
    //         Log::error($e->getMessage());
    //         return back()->with('error', 'Unable to export campaigns');
    //     }
    // }
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
    //     $campaignId = base64_decode($campaignId);

    //     $campaign = DB::table('campaign')
    //         ->where('id', $campaignId)
    //         ->first();

    //     $items = DB::table('cart_items as ci')
    //         ->join('media_management as m', 'm.id', '=', 'ci.media_id')
    //         ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
    //         ->select(
    //             'm.media_title',
    //             'm.width',
    //             'm.height',
    //             'm.illumination_id',
    //             'm.price',
    //             'a.common_stdiciar_name',
    //             'ci.qty'
    //         )
    //         ->where('ci.campaign_id', $campaignId)
    //         ->where('ci.cart_type', 'CAMPAIGN')
    //         ->get();

    //     $ppt = new PhpPresentation();

    //     /* ================= SLIDE 1 : TITLE ================= */
    //     $slide1 = $ppt->getActiveSlide();
    //     $title = $slide1->createRichTextShape()
    //         ->setHeight(100)
    //         ->setWidth(800)
    //         ->setOffsetX(100)
    //         ->setOffsetY(200);

    //     $title->getActiveParagraph()->getAlignment()
    //         ->setHorizontal(Alignment::HORIZONTAL_CENTER);

    //     $title->createTextRun("CAMPAIGN DETAILS")
    //         ->getFont()
    //         ->setSize(36)
    //         ->setBold(true)
    //         ->setColor(new Color('FF000000'));

    //     /* ================= SLIDE 2 : SITE DETAILS ================= */
    //     foreach ($items as $item) {

    //         $slide = $ppt->createSlide();

    //         $content = "SITE NAME : {$item->media_title}\n";
    //         $content .= "MEDIA TYPE : Billboard\n\n";
    //         $content .= "CAMPAIGN DETAILS OF SITE\n\n";
    //         $content .= "City        : Nashik\n";
    //         $content .= "Location    : {$item->common_stdiciar_name}\n";
    //         $content .= "GEO Location: -\n";
    //         $content .= "Size        : {$item->width} × {$item->height}\n";
    //         $content .= "Illumination: Nonlit\n";
    //         $content .= "Price       : ₹ " . number_format($item->price, 2) . "\n";
    //         $content .= "Foot fall   : 0";

    //         $shape = $slide->createRichTextShape()
    //             ->setHeight(500)
    //             ->setWidth(900)
    //             ->setOffsetX(50)
    //             ->setOffsetY(50);

    //         $shape->getActiveParagraph()->getFont()->setSize(18);
    //         $shape->createTextRun($content);
    //     }

    //     /* ================= DOWNLOAD ================= */
    //     $fileName = 'Campaign_Details_' . now()->format('d-m-Y') . '.pptx';

    //     $writer = IOFactory::createWriter($ppt, 'PowerPoint2007');

    //     return response()->streamDownload(function () use ($writer) {
    //         $writer->save('php://output');
    //     }, $fileName);
    // }





    // public function exportPpt($campaignId)
    // {
    //     $campaignId = base64_decode($campaignId);

    //     /* ================= FETCH DATA ================= */
    //     $campaign = DB::table('campaign')->where('id', $campaignId)->first();

    //     $items = DB::table('cart_items as ci')
    //         ->join('media_management as m', 'm.id', '=', 'ci.media_id')
    //         ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
    //         ->select(
    //             'm.media_title',
    //             'm.width',
    //             'm.height',
    //             'm.price',
    //             'a.common_stdiciar_name'
    //         )
    //         ->where('ci.campaign_id', $campaignId)
    //         ->where('ci.cart_type', 'CAMPAIGN')
    //         ->get();

    //     /* ================= INIT PPT ================= */
    //     $ppt = new PhpPresentation();

    //     /* =====================================================
    //    SLIDE 1 : COVER SLIDE (BLUE BACKGROUND)
    // ===================================================== */
    //     $slide1 = $ppt->getActiveSlide();

    //     // Background Image (ALWAYS FIRST)
    //     $bg = $slide1->createDrawingShape();
    //     $bg->setPath(public_path('asset/theamoriginalalf/images/bluebg.png'))
    //         ->setWidth(960)
    //         ->setHeight(540)
    //         ->setOffsetX(0)
    //         ->setOffsetY(0);

    //     // Logo White Background
    //     $logoBg = $slide1->createRichTextShape();
    //     $logoBg->setOffsetX(30)
    //         ->setOffsetY(30)
    //         ->setWidth(220)
    //         ->setHeight(80);

    //     $logoBg->getFill()
    //         ->setFillType(Fill::FILL_SOLID)
    //         ->setStartColor(new Color('FFFFFFFF'));

    //     // Logo
    //     $logo = $slide1->createDrawingShape();
    //     $logo->setPath(public_path('asset/theamoriginalalf/images/logo.png'))
    //         ->setHeight(60)
    //         ->setOffsetX(40)
    //         ->setOffsetY(40);

    //     // Title
    //     $title = $slide1->createRichTextShape()
    //         ->setOffsetX(260)
    //         ->setOffsetY(230)
    //         ->setWidth(500);

    //     $title->getActiveParagraph()->getAlignment()
    //         ->setHorizontal(Alignment::HORIZONTAL_CENTER);

    //     $title->createTextRun("MEDIA PLAN PRESENTATION\n")
    //         ->getFont()
    //         ->setSize(34)
    //         ->setBold(true)
    //         ->setColor(new Color('FF0F2A44'));

    //     $title->createTextRun($campaign->campaign_name)
    //         ->getFont()
    //         ->setSize(22)
    //         ->setColor(new Color('FF0F2A44'));

    //     /* =====================================================
    //    SLIDE 2 : CAMPAIGN OVERVIEW
    // ===================================================== */
    //     $slide2 = $ppt->createSlide();

    //     $overview = $slide2->createRichTextShape()
    //         ->setOffsetX(80)
    //         ->setOffsetY(120)
    //         ->setWidth(800);

    //     $overview->createTextRun("CAMPAIGN OVERVIEW\n\n")
    //         ->getFont()
    //         ->setSize(26)
    //         ->setBold(true);

    //     $overview->createTextRun(
    //         "Campaign Name : {$campaign->campaign_name}\n" .
    //             "City          : Nashik\n" .
    //             "Total Sites   : {$items->count()}\n" .
    //             "Date          : " . now()->format('d M Y')
    //     )->getFont()->setSize(18);

    //     /* =====================================================
    //    SLIDE 3+ : SITE DETAIL SLIDES
    // ===================================================== */
    //     foreach ($items as $item) {

    //         $slide = $ppt->createSlide();

    //         // Site Title
    //         $siteTitle = $slide->createRichTextShape()
    //             ->setOffsetX(40)
    //             ->setOffsetY(30)
    //             ->setWidth(850);

    //         $siteTitle->createTextRun($item->media_title)
    //             ->getFont()
    //             ->setSize(22)
    //             ->setBold(true);

    //         // Left Image Placeholder
    //         $imageBox = $slide->createRichTextShape()
    //             ->setOffsetX(40)
    //             ->setOffsetY(120)
    //             ->setWidth(320)
    //             ->setHeight(220);

    //         $imageBox->getFill()
    //             ->setFillType(Fill::FILL_SOLID)
    //             ->setStartColor(new Color('FFEFEFEF'));

    //         $imageBox->getActiveParagraph()
    //             ->getAlignment()
    //             ->setHorizontal(Alignment::HORIZONTAL_CENTER);

    //         $imageBox->createTextRun("SITE IMAGE")
    //             ->getFont()
    //             ->setSize(14)
    //             ->setBold(true)
    //             ->setColor(new Color('FF777777'));

    //         // Details Box
    //         $details = $slide->createRichTextShape()
    //             ->setOffsetX(400)
    //             ->setOffsetY(120)
    //             ->setWidth(450);

    //         $details->createTextRun("SITE DETAILS\n\n")
    //             ->getFont()
    //             ->setSize(18)
    //             ->setBold(true);

    //         $details->createTextRun(
    //             "Location : {$item->common_stdiciar_name}\n" .
    //                 "Size     : {$item->width} × {$item->height}\n" .
    //                 "Media    : Billboard\n" .
    //                 "Price    : ₹ " . number_format($item->price, 2) . "\n" .
    //                 "Lighting : Non-Lit"
    //         )->getFont()->setSize(14);
    //     }

    //     /* =====================================================
    //    LAST SLIDE : THANK YOU (FULL IMAGE)
    // ===================================================== */
    //     $thankSlide = $ppt->createSlide();

    //     $thankBg = $thankSlide->createDrawingShape();
    //     $thankBg->setPath(public_path('asset/theamoriginalalf/images/thankyou.png'))
    //         ->setWidth(960)
    //         ->setHeight(540)
    //         ->setOffsetX(0)
    //         ->setOffsetY(0);

    //     /* ================= DOWNLOAD ================= */
    //     $fileName = 'Media_Plan_' . now()->format('d-m-Y') . '.pptx';

    //     $writer = IOFactory::createWriter($ppt, 'PowerPoint2007');

    //     return response()->streamDownload(function () use ($writer) {
    //         $writer->save('php://output');
    //     }, $fileName);
    // }

    public function exportPpt($campaignId)
    {
        $campaignId = base64_decode($campaignId);

        /* ================= CAMPAIGN ================= */
        $campaign = DB::table('campaign')
            ->where('id', $campaignId)
            ->first();

        if (!$campaign) {
            abort(404, 'Campaign not found');
        }

        /* ================= ITEMS + FIRST IMAGE ================= */
        $items = DB::table('cart_items as ci')
            ->join('media_management as m', 'm.id', '=', 'ci.media_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin(DB::raw('
        (
            SELECT mi1.media_id, mi1.images
            FROM media_images mi1
            INNER JOIN (
                SELECT media_id, MIN(id) AS min_id
                FROM media_images
                WHERE is_deleted = 1
                GROUP BY media_id
            ) mi2 ON mi2.min_id = mi1.id
        ) mi
    '), 'mi.media_id', '=', 'm.id')
            ->select(
                'm.media_title',
                'm.width',
                'm.height',
                'm.price',
                'a.common_stdiciar_name',
                'mi.images as first_image'
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
        $bg = $slide1->createDrawingShape();
        $bg->setPath(public_path('asset/theamoriginalalf/images/bluebg.png'))
            ->setWidth(960)
            ->setHeight(540)
            ->setOffsetX(0)
            ->setOffsetY(0);

        // Logo
        $logo = $slide1->createDrawingShape();
        $logo->setPath(public_path('asset/theamoriginalalf/images/logo.png'))
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

        $title->createTextRun("Campaing Name\n")
            ->getFont()->setSize(34)->setBold(true);

        $title->createTextRun($campaign->campaign_name)
            ->getFont()->setSize(22);

        /* =====================================================
       SLIDE 2 : OVERVIEW
    ===================================================== */
        $slide2 = $ppt->createSlide();

        $overview = $slide2->createRichTextShape()
            ->setOffsetX(80)
            ->setOffsetY(120)
            ->setWidth(800);

        $overview->createTextRun("CAMPAIGN DETAILS OF SITE\n\n")
            ->getFont()->setSize(26)->setBold(true);

        $overview->createTextRun(
            "Campaign Name : {$campaign->campaign_name}\n" .
                "City          : Nashik\n" .
                "Total Sites   : {$items->count()}\n" .
                "Date          : " . now()->format('d M Y')
        )->getFont()->setSize(18);

        /* =====================================================
       SLIDE 3+ : SITE DETAILS
    ===================================================== */
        foreach ($items as $item) {

            $slide = $ppt->createSlide();

            /* ---------- TITLE ---------- */
            $siteTitle = $slide->createRichTextShape()
                ->setOffsetX(40)
                ->setOffsetY(30)
                ->setWidth(850);

            $siteTitle->createTextRun($item->media_title)
                ->getFont()->setSize(22)->setBold(true);

            /* ---------- IMAGE ---------- */
            // $imagePath = null;

            // if (!empty($item->first_image)) {
            //     $imagePath = public_path(
            //         config('fileConstants.IMAGE_VIEW') . $item->first_image
            //     );
            // }
            $imagePath = null;

            if (!empty($item->first_image)) {
                $imagePath = storage_path(
                    'app/public/upload/images/media/' . $item->first_image
                );
            }

            if ($imagePath && file_exists($imagePath)) {

                $siteImage = $slide->createDrawingShape();
                $siteImage->setPath($imagePath)
                    ->setWidth(320)
                    ->setHeight(220)
                    ->setOffsetX(40)
                    ->setOffsetY(120);
            } else {

                $imageBox = $slide->createRichTextShape()
                    ->setOffsetX(40)
                    ->setOffsetY(120)
                    ->setWidth(320)
                    ->setHeight(220);

                $imageBox->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->setStartColor(new Color('FFEFEFEF'));

                $imageBox->getActiveParagraph()
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $imageBox->createTextRun("NO IMAGE AVAILABLE")
                    ->getFont()->setSize(14)->setBold(true);
            }


            // dd($imagePath);
            // die();
            if ($imagePath && file_exists($imagePath)) {

                $siteImage = $slide->createDrawingShape();
                $siteImage->setPath($imagePath)
                    ->setWidth(320)
                    ->setHeight(220)
                    ->setOffsetX(40)
                    ->setOffsetY(120);
            } else {

                $imageBox = $slide->createRichTextShape()
                    ->setOffsetX(40)
                    ->setOffsetY(120)
                    ->setWidth(320)
                    ->setHeight(220);

                $imageBox->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->setStartColor(new Color('FFEFEFEF'));

                $imageBox->getActiveParagraph()
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $imageBox->createTextRun("NO IMAGE AVAILABLE")
                    ->getFont()->setSize(14)->setBold(true);
            }

            /* ---------- DETAILS ---------- */
            $details = $slide->createRichTextShape()
                ->setOffsetX(400)
                ->setOffsetY(120)
                ->setWidth(450);

            $details->createTextRun("SITE DETAILS\n\n")
                ->getFont()->setSize(18)->setBold(true);

            $details->createTextRun(
                "Location : {$item->common_stdiciar_name}\n" .
                    "Size     : {$item->width} × {$item->height}\n" .
                    "Media    : Billboard\n" .
                    "Price    : ₹ " . number_format($item->price, 2) . "\n" .
                    "Lighting : Non-Lit"
            )->getFont()->setSize(14);
        }

        /* =====================================================
       LAST SLIDE : THANK YOU
    ===================================================== */
        $thankSlide = $ppt->createSlide();

        $thankBg = $thankSlide->createDrawingShape();
        $thankBg->setPath(public_path('asset/theamoriginalalf/images/thankyou.png'))
            ->setWidth(960)
            ->setHeight(540)
            ->setOffsetX(0)
            ->setOffsetY(0);

        /* ================= DOWNLOAD ================= */
        $fileName = 'Media_Plan_' . now()->format('d-m-Y') . '.pptx';

        $writer = IOFactory::createWriter($ppt, 'PowerPoint2007');

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName);
    }

    public function viewInvoice($orderId)
    {
        try {
            $orderId = base64_decode($orderId);

            $items = $this->campaignService->getInvoiceDetails($orderId);

            return view('website.campaign-invoice-details', compact('items'));
        } catch (\Throwable $e) {
            Log::error('Invoice View Error', [
                'message' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Unable to load invoice');
        }
    }
}

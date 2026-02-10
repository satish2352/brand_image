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
    public function isCampaignBooked($items)
    {
        foreach ($items as $row) {

            $exists = \DB::table('media_booked_date')
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


    // public function openCampaigns(Request $request)
    // {
    //     $campaigns = $this->campaignService->getOpenCampaigns(
    //         Auth::guard('website')->id(),
    //         $request
    //     );

    //     return view('website.campaign-list', [
    //         'campaigns' => $campaigns,
    //         'type'      => 'open',
    //     ]);
    // }

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
            dd($e->getMessage());
        }
    }
    // public function exportExcel($campaignId)
    // {
    //     $campaignId = base64_decode($campaignId);

    //     return Excel::download(
    //         new CampaignExport(
    //             Auth::guard('website')->id(),
    //             $campaignId
    //         ),
    //         'campaign_items.xlsx'
    //     );
    // }
    public function exportExcel($campaignId)
    {
        $campaignId = base64_decode($campaignId);

        $campaign = DB::table('campaign')->where('id', $campaignId)->first();

        $fileName = preg_replace('/[^A-Za-z0-9_-]/', '_', $campaign->campaign_name)
            . '_' . now()->format('d-m-Y') . '.xlsx';

        return Excel::download(
            new CampaignExport(Auth::guard('website')->id(), $campaignId),
            $fileName
        );
    }



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

        $ppt->getLayout()->setDocumentLayout(
            \PhpOffice\PhpPresentation\DocumentLayout::LAYOUT_SCREEN_16X9

        );

        /* =====================================================
        SLIDE 1 : COVER SLIDE
        ===================================================== */
        $slide1 = $ppt->getActiveSlide();

        // Background
        $bg = $slide1->createDrawingShape();
        $bg->setPath(public_path('asset/theamoriginalalf/images/bluebg1.png'))
            ->setWidth(800)          // reduce width
            ->setHeight(540)        // keep ratio
            ->setOffsetX(0)        // center horizontally ( (960-800)/2 )
            ->setOffsetY(0);       // center vertically ( (540-450)/2 )


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

                if (empty($img)) continue;

                // $originalPath = public_path('storage/upload/images/media/' . trim($img));
                $originalPath = storage_path('app/public/upload/images/media/' . trim($img));


                if (!file_exists($originalPath) || !is_readable($originalPath)) {
                    continue;
                }

                // STEP 1: Read image binary
                $imageData = file_get_contents($originalPath);
                if ($imageData === false) continue;

                // STEP 2: Convert to base64 (optional but safe)
                $base64 = base64_encode($imageData);

                // STEP 3: Write TEMP image file
                $tempPath = storage_path(
                    'app/temp_ppt_' . md5($img) . '.png'
                );

                file_put_contents(
                    $tempPath,
                    base64_decode($base64)
                );

                // STEP 4: Embed image into PPT
                try {
                    $shape = $slide->createDrawingShape();
                    $shape->setPath($tempPath)
                        ->setWidth(200)
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

            // $details->createTextRun(
            //     "SITE DETAILS\n\n" .
            //     "Location  : {$item->common_stdiciar_name}\n" .
            //     "Area      : {$item->area_name}\n" .
            //     "City      : {$item->city_name}\n" .
            //     "Size      : {$item->width} × {$item->height}\n" .
            //     "Media type: {$item->media_type}\n" .
            //     "Price     : ₹ " . number_format($item->price) . "\n" .
            //     "From Date : $from\n" .
            //     "To Date   : $to\n" .
            //     "Lighting  : {$item->illumination_name}\n"
            // )->getFont()->setSize(18);
            // Heading
            $details->createTextRun("SITE DETAILS\n\n")
                ->getFont()->setBold(true)->setSize(18);

            // Location
            $details->createTextRun("Location  : ")
                ->getFont()->setBold(true)->setSize(18);

            $details->createTextRun("{$item->common_stdiciar_name}\n")
                ->getFont()->setSize(18);

            // Area
            $details->createTextRun("Area      : ")
                ->getFont()->setBold(true)->setSize(18);

            $details->createTextRun("{$item->area_name}\n")
                ->getFont()->setSize(18);

            // City
            $details->createTextRun("City      : ")
                ->getFont()->setBold(true)->setSize(18);

            $details->createTextRun("{$item->city_name}\n")
                ->getFont()->setSize(18);

            // Size
            $details->createTextRun("Size      : ")
                ->getFont()->setBold(true)->setSize(18);

            $details->createTextRun("{$item->width} × {$item->height}\n")
                ->getFont()->setSize(18);

            // Media Type
            $details->createTextRun("Media type: ")
                ->getFont()->setBold(true)->setSize(18);

            $details->createTextRun("{$item->media_type}\n")
                ->getFont()->setSize(18);

            // Price
            $details->createTextRun("Price     : ")
                ->getFont()->setBold(true)->setSize(18);

            $details->createTextRun("₹ " . number_format($item->price) . "\n")
                ->getFont()->setSize(18);

            // From Date
            $details->createTextRun("From Date : ")
                ->getFont()->setBold(true)->setSize(18);

            $details->createTextRun("$from\n")
                ->getFont()->setSize(18);

            // To Date
            $details->createTextRun("To Date   : ")
                ->getFont()->setBold(true)->setSize(18);

            $details->createTextRun("$to\n")
                ->getFont()->setSize(18);

            // Lighting
            $details->createTextRun("Lighting  : ")
                ->getFont()->setBold(true)->setSize(18);

            $details->createTextRun("{$item->illumination_name}\n")
                ->getFont()->setSize(18);
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
}

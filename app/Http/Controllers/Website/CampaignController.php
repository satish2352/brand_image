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
        return Excel::download(
            new CampaignExport(
                Auth::guard('website')->id(),
                $campaignId
            ),
            'campaign_items.xlsx'
        );
    }
    public function invoicePayments()
    {
        $invoices = $this->campaignService->getInvoicePayments(
            auth()->guard('website')->id()
        );

        return view('website.campaign-invoice-list', compact('invoices'));
    }

    public function viewInvoice($orderId)
    {
        $orderId = decrypt($orderId);

        $items = DB::table('order_items as oi')
            ->join('media_management as m', 'm.id', '=', 'oi.media_id')
            ->leftJoin('media_images as mi', function ($join) {
                $join->on('mi.media_id', '=', 'm.id')
                    ->where('mi.is_deleted', 0);
            })
            ->select(
                'm.media_title',
                'm.width',
                'm.height',
                'oi.price',
                'oi.qty',
                DB::raw('GROUP_CONCAT(mi.images) as images')
            )
            ->where('oi.order_id', $orderId)
            ->groupBy(
                'm.media_title',
                'm.width',
                'm.height',
                'oi.price',
                'oi.qty'
            )
            ->get();

        return view('website.campaign-invoice-details', compact('items'));
    }
}

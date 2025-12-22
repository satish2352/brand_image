<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Services\Website\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


use Illuminate\Support\Facades\Log;

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

            return redirect()->back()->with('success', 'Campaign saved successfully');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }

    public function getCampaignList()
    {
        try {
            $campaigns = $this->campaignService->getCampaignList(
                Auth::guard('website')->id()
            );

            return view('website.campaign-list', compact('campaigns'));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->with('error', 'Unable to load campaigns');
        }
    }
}

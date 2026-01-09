<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\CampaingService;
use App\Http\Repository\Superadm\CampaingRepository;
use Illuminate\Http\Request;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AdminCampaignExport;

class CampaingController extends Controller
{
	protected $service;

	public function __construct(CampaingService $service)
	{
		$this->service = $service;
	}

	// public function index()
	// {
	// 	$campaigns = $this->service->list();
	// 	return view('superadm.campaing.campaing-list', compact('campaigns'));
	// }

	public function index()
	{
		$campaigns = $this->service->adminCampaignList();
		return view('superadm.campaing.campaing-list', compact('campaigns'));
	}

	public function details($userId, CampaingService $service)
	{
		$userId = base64_decode($userId);

		$campaigns = $service->getCampaignByUserForAdmin($userId);

		return view('superadm.campaing.details', compact('campaigns'));
	}

	public function delete(Request $request)
	{
		$this->service->delete(base64_decode($request->id));
		return back()->with('success', 'User deleted successfully');
	}
	public function toggleStatus(Request $request)
	{
		$this->service->toggleStatus(base64_decode($request->id));

		return response()->json([
			'status' => true,
			'message' => 'Status updated successfully'
		]);
	}

	public function exportExcel($campaignId)
	{
		$campaignId = base64_decode($campaignId);

		return Excel::download(
			new AdminCampaignExport($campaignId),
			'admin_campaign_'.$campaignId.'.xlsx'
		);
	}
	
}

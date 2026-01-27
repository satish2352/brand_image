<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\CampaingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AdminCampaignExport;
use Carbon\Carbon;
use App\Http\Controllers\Website\CampaignController as WebsiteCampaignController;


class CampaingController extends Controller
{
	protected $service;

	public function __construct(CampaingService $service)
	{
		$this->service = $service;
	}
	public function index()
	{
		$campaigns = $this->service->adminCampaignList();
		return view('superadm.campaing.campaing-list', compact('campaigns'));
	}

	public function details($campaignId, $userId, CampaingService $service)
	{
		$userId = base64_decode($userId);
		$campaignId = base64_decode($campaignId);

		$campaigns = $service->getCampaignByUserForAdmin($userId)
			->where('campaign_id', $campaignId)
			->values();       // index reset


		return view('superadm.campaing.details', compact('campaigns'));
	}
	public function book(Request $request)
	{
		$campaignId = $request->campaign_id;
		$mediaId    = $request->media_id;
		$from       = $request->from_date;
		$to         = $request->to_date;

		$monthlyPrice = $request->price;
		$perDayPrice  = $request->per_day_price;
		$days         = $request->total_days;
		$totalAmount  = $request->total_price;

		// GST 18%
		$gstAmount  = round($totalAmount * 0.18, 2);
		$grandTotal = $totalAmount + $gstAmount;

		/** Check overlap **/
		$exists = DB::table('media_booked_date')
			->where('media_id', $mediaId)
			->where('is_deleted', 0)
			->where(function ($q) use ($from, $to) {
				$q->whereBetween('from_date', [$from, $to])
					->orWhereBetween('to_date', [$from, $to])
					->orWhereRaw("'$from' BETWEEN from_date AND to_date")
					->orWhereRaw("'$to' BETWEEN from_date AND to_date");
			})
			->exists();

		if ($exists) {
			return back()->with('error', 'Already booked in these dates!');
		}

		DB::table('media_booked_date')->insert([
			'media_id'   => $mediaId,
			'from_date'  => $from,
			'to_date'    => $to,
			'is_active'  => 1,
			'is_deleted' => 0,
			'created_at' => now(),
		]);

		$orderId = DB::table('orders')->insertGetId([
			'user_id'        => $request->user_id,
			'campaign_id'    => $campaignId,
			'order_no'       => 'ORD-' . time(),
			'total_amount'   => $totalAmount,
			'gst_amount'     => $gstAmount,
			'grand_total'    => $grandTotal,
			'payment_status' => 'ADMIN_BOOKED',
			'is_active'      => 1,
			'is_deleted'     => 0,
			'created_at'     => now(),
		]);

		DB::table('order_items')->insert([
			'order_id'      => $orderId,
			'media_id'      => $mediaId,
			'price'         => $monthlyPrice,
			'per_day_price' => $perDayPrice,
			'total_days'    => $days,
			'total_price'   => $totalAmount,
			'qty'           => 1,
			'from_date'     => $from,
			'to_date'       => $to,
			'is_active'     => 1,
			'is_deleted'    => 0,
			'created_at'    => now(),
		]);

		return back()->with('success', 'Booking Done & Order Created!');
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
			'admin_campaign_' . $campaignId . '.xlsx'
		);
	}
	public function exportPpt($campaignId)
	{
		return app(WebsiteCampaignController::class)->exportPpt($campaignId);
	}
}

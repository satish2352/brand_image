<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\WebsiteUserService;
use Illuminate\Http\Request;

class CampaingController extends Controller
{
	protected $service;

	public function __construct(WebsiteUserService $service)
	{
		$this->service = $service;
	}

	public function index()
	{
		$users = $this->service->list();
		return view('superadm.website-user.website-user-list', compact('users'));
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
}

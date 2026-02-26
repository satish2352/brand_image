<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\WebsiteUserService;
use Illuminate\Http\Request;

class WebsiteUserController extends Controller
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
		$id = base64_decode($request->id);

		$result = $this->service->delete($id);

		if (!$result) {
			return back()->with('error', 'This user cannot be deleted because orders exist.');
		}

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

	public function view(Request $request)
	{
		$id = base64_decode($request->id);
		$user = $this->service->getById($id);

		if (!$user) {
			return response()->json([
				'status' => false,
				'message' => 'User not found'
			], 404);
		}

		return response()->json($user);
	}
}

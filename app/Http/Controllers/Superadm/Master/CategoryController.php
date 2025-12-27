<?php

namespace App\Http\Controllers\Superadm\Master;

use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\Master\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Exception;

class CategoryController extends Controller
{
	protected $service;

	public function __construct()
	{
		$this->service = new CategoryService();
	}

	public function index()
	{
		$categories = $this->service->list();
		return view('superadm.master.category.list', compact('categories'));
	}

	public function create()
	{
		return view('superadm.master.category.create');
	}

	public function store(Request $request)
	{
		$request->validate([
			'category_name' => [
				'required',
				'max:255',
				Rule::unique('category', 'category_name')
					->where(fn($q) => $q->where('is_deleted', 0)),
			],
		], [
			'category_name.required' => 'Category name is required',
			'category_name.unique' => 'Category already exists',
		]);

		try {
			$this->service->store($request);
			return redirect()->route('category.list')->with('success', 'Category added successfully');
		} catch (Exception $e) {
			return back()->withInput()->with('error', $e->getMessage());
		}
	}

	public function edit($encodedId)
	{
		$id = base64_decode($encodedId);
		$category = $this->service->edit($id);

		if (!$category) {
			return redirect()->route('category.list')->with('error', 'Category not found');
		}

		return view('superadm.master.category.edit', compact('category', 'encodedId'));
	}

	public function update(Request $request)
	{
		$request->validate([
			'id' => 'required',
			'category_name' => [
				'required',
				'max:255',
				Rule::unique('categories', 'category_name')
					->where(fn($q) => $q->where('is_deleted', 0))
					->ignore($request->id),
			],
			'is_active' => 'required|in:0,1',
		]);

		try {
			$this->service->update($request);
			return redirect()->route('category.list')->with('success', 'Category updated successfully');
		} catch (Exception $e) {
			return back()->withInput()->with('error', $e->getMessage());
		}
	}

	public function delete(Request $request)
	{
		try {
			$this->service->delete($request->id);
			return redirect()->route('category.list')->with('success', 'Category deleted successfully');
		} catch (Exception $e) {
			return back()->with('error', $e->getMessage());
		}
	}

	public function updateStatus(Request $request)
	{
		try {
			$this->service->updateStatus($request->id, $request->is_active);
			return response()->json(['status' => true, 'message' => 'Status updated']);
		} catch (Exception $e) {
			return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
		}
	}
}

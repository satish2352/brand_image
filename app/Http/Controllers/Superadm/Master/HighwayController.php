<?php

namespace App\Http\Controllers\Superadm\Master;

use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\Master\HighwayService;
use Illuminate\Http\Request;
use Exception;

class HighwayController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new HighwayService();
    }

    public function index()
    {
        $highway = $this->service->list();
        return view('superadm.highway.list', compact('highway'));
    }

    public function create()
    {
        return view('superadm.highway.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'highway_name' => [
                'required',
                'max:255',
                // letters, numbers, spaces and dash (e.g. "NH-48", "Mumbai-Agra Highway")
                'regex:/^[A-Za-z0-9\s\-]+$/'
            ],
            'highway_type' => ['nullable', 'max:100']
        ], [
            'highway_name.required' => 'Highway name is required',
            'highway_name.regex' =>
            'Only letters, numbers, spaces and dash (-) are allowed'
        ]);

        try {
            $this->service->store($validated);

            return redirect()->route('highway.list')
                ->with('success', 'Highway added successfully');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function edit($encodedId)
    {
        $id = base64_decode($encodedId);
        $highway = $this->service->find($id);
        return view('superadm.highway.edit', compact('highway', 'encodedId'));
    }

    public function update(Request $request, $encodedId)
    {
        $id = base64_decode($encodedId);

        $validated = $request->validate([
            'highway_name' => [
                'required',
                'max:255',
                'regex:/^[A-Za-z0-9\s\-]+$/'
            ],
            'highway_type' => ['nullable', 'max:100']
        ]);

        try {
            $this->service->update($id, $validated);
            return redirect()->route('highway.list')
                ->with('success', 'Highway updated successfully');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function updateStatus(Request $request)
    {
        $id = base64_decode($request->id);
        $this->service->toggleStatus($id);

        return response()->json([
            'status' => true,
            'message' => 'Highway updated successfully'
        ]);
    }

    public function delete(Request $request)
    {
        $id = base64_decode($request->id);
        $this->service->delete($id);

        return response()->json([
            'status' => true,
            'message' => 'Highway deleted successfully'
        ]);
    }
}

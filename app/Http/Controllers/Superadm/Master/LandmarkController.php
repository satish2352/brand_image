<?php

namespace App\Http\Controllers\Superadm\Master;

use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\Master\LandmarkService;
use Illuminate\Http\Request;
use Exception;

class LandmarkController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new LandmarkService();
    }

    public function index()
    {
        $landmark = $this->service->list();
        return view('superadm.landmark.list', compact('landmark'));
    }

    public function create()
    {
        return view('superadm.landmark.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'landmark_name' => [
                'required',
                'max:255',
                'regex:/^[A-Za-z0-9\s\-]+$/'
            ]
        ], [
            'landmark_name.required' => 'Landmark name is required',
            'landmark_name.regex' =>
            'Only letters, numbers, spaces and dash (-) are allowed'
        ]);

        try {
            $this->service->store($validated);

            return redirect()->route('landmark.list')
                ->with('success', 'Landmark added successfully');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function edit($encodedId)
    {
        $id = base64_decode($encodedId);
        $landmark = $this->service->find($id);
        return view('superadm.landmark.edit', compact('landmark', 'encodedId'));
    }

    public function update(Request $request, $encodedId)
    {
        $id = base64_decode($encodedId);

        $validated = $request->validate([
            'landmark_name' => [
                'required',
                'max:255',
                'regex:/^[A-Za-z0-9\s\-]+$/'
            ]
        ]);

        try {
            $this->service->update($id, $validated);
            return redirect()->route('landmark.list')
                ->with('success', 'Landmark updated successfully');
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
            'message' => 'Landmark updated successfully'
        ]);
    }

    public function delete(Request $request)
    {
        $id = base64_decode($request->id);
        $this->service->delete($id);

        return response()->json([
            'status' => true,
            'message' => 'Landmark deleted successfully'
        ]);
    }
}

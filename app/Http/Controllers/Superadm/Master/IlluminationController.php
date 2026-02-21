<?php

namespace App\Http\Controllers\Superadm\Master;

use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\Master\IlluminationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\{
    Illumination
};

class IlluminationController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new IlluminationService();
    }

    public function index()
    {
        $illuminations = $this->service->list();
        return view('superadm.illumination.list', compact('illuminations'));
    }

    public function create()
    {
        return view('superadm.illumination.create');
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'illumination_name' => [
                'required',
                'max:255',
                'regex:/^[A-Za-z\s\-]+$/'
            ]
        ], [
            'illumination_name.required' => 'Illumination name is required',
            'illumination_name.regex' =>
            'Only letters, spaces and dash (-) are allowed'
        ]);

        try {
            $this->service->store($validated);

            return redirect()->route('illumination.list')
                ->with('success', 'Illumination added successfully');
        } catch (Exception $e) {

            return back()->withInput()
                ->with('error', $e->getMessage());
        }
    }
    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'illumination_name' => [
    //             'required',
    //             'max:255',
    //             'regex:/^[A-Za-z\s\-]+$/'
    //         ]
    //     ], [
    //         'illumination_name.required' => 'Illumination name is required',
    //         'illumination_name.regex' =>
    //         'Only letters, spaces and dash (-) are allowed'
    //     ]);

    //     try {
    //         $this->service->store($validated);
    //         return redirect()->route('illumination.list')
    //             ->with('success', 'Illumination added successfully');
    //     } catch (Exception $e) {
    //         return back()->withInput()->with('error', $e->getMessage());
    //     }
    // }

    public function edit($encodedId)
    {
        $id = base64_decode($encodedId);
        $illumination = $this->service->find($id);
        return view('superadm.illumination.edit', compact('illumination', 'encodedId'));
    }

    public function update(Request $request, $encodedId)
    {
        $id = base64_decode($encodedId);

        $validated = $request->validate([
            'illumination_name' => [
                'required',
                'max:255',
                'regex:/^[A-Za-z\s\-]+$/'
            ]
        ]);

        try {
            $this->service->update($id, $validated);
            return redirect()->route('illumination.list')
                ->with('success', 'Illumination updated successfully');
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
            'message' => 'Status updated successfully'
        ]);
    }

    public function delete(Request $request)
    {
        $id = base64_decode($request->id);
        $this->service->delete($id);

        return response()->json([
            'status' => true,
            'message' => 'Illumination deleted successfully'
        ]);
    }
}

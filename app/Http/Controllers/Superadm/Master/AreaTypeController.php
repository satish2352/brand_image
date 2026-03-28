<?php

namespace App\Http\Controllers\Superadm\Master;

use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\Master\AreaTypeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\{
    AreaType
};

class AreaTypeController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new AreaTypeService();
    }

    public function index()
    {
        $areatype = $this->service->list();
        return view('superadm.areatype.list', compact('areatype'));
    }

    public function create()
    {
        return view('superadm.areatype.create');
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'areatype_name' => [
                'required',
                'max:255',
                'regex:/^[A-Za-z\s\-]+$/'
            ]
        ], [
            'areatype_name.required' => 'areatype name is required',
            'areatype_name.regex' =>
            'Only letters, spaces and dash (-) are allowed'
        ]);

        try {
            $this->service->store($validated);

            return redirect()->route('areatype.list')
                ->with('success', 'area type added successfully');
        } catch (Exception $e) {

            return back()->withInput()
                ->with('error', $e->getMessage());
        }
    }
    public function edit($encodedId)
    {
        $id = base64_decode($encodedId);
        $areatype = $this->service->find($id);
        return view('superadm.areatype.edit', compact('areatype', 'encodedId'));
    }

    public function update(Request $request, $encodedId)
    {
        $id = base64_decode($encodedId);

        $validated = $request->validate([
            'areatype_name' => [
                'required',
                'max:255',
                'regex:/^[A-Za-z\s\-]+$/'
            ]
        ]);

        try {
            $this->service->update($id, $validated);
            return redirect()->route('areatype.list')
                ->with('success', 'areatype updated successfully');
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
            'message' => 'areatype updated successfully'
        ]);
    }

    public function delete(Request $request)
    {
        $id = base64_decode($request->id);
        $this->service->delete($id);

        return response()->json([
            'status' => true,
            'message' => 'areatype deleted successfully'
        ]);
    }
}

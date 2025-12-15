<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\RadiusService;
use Illuminate\Http\Request;
use Exception;

class RadiusController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new RadiusService();
    }

    public function index()
    {
        $radius = $this->service->list();
        return view('superadm.radius.list', compact('radius'));
    }

    public function create()
    {
        return view('superadm.radius.create');
    }

    public function save(Request $req)
    {
        $req->validate([
            'radius' => [
                'required',
                'max:50',
                'regex:/^\s*\d+\s*km\s*-\s*\d+\s*km\s*$/i',
            ]
        ]);

        try {
            $this->service->save($req);
            return redirect()->route('radius.list')->with('success', 'Radius added successfully.');
        } catch (Exception $e) {
            return back()->withErrors(['radius' => $e->getMessage()])->withInput();
        }
    }

    public function edit($encodedId)
    {
        $id = base64_decode($encodedId);
        $data = $this->service->edit($id);
        return view('superadm.radius.edit', compact('data', 'encodedId'));
    }

    public function update(Request $req)
    {
        $req->validate([
            'radius' => [
                'required',
                'max:50',
                'regex:/^\s*\d+\s*km\s*-\s*\d+\s*km\s*$/i',
            ]
        ]);

        try {
            $this->service->update($req);
            return redirect()->route('radius.list')->with('success', 'Radius updated successfully.');
        } catch (Exception $e) {
            return back()->withErrors(['radius' => $e->getMessage()])->withInput();
        }
    }

    public function delete(Request $req)
    {
        try {
            $this->service->delete($req);
            return redirect()->route('radius.list')->with('success', 'Radius deleted successfully.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateStatus(Request $request)
    {
        try {
            $id = base64_decode($request->id);

            $radius = \DB::table('radius_master')->where('id', $id)->first();

            if (!$radius) {
                return response()->json(['status' => false, 'message' => 'Radius not found'], 404);
            }

            \DB::table('radius_master')
                ->where('id', $id)
                ->update(['is_active' => $request->is_active]);

            $statusText = $request->is_active ? 'Activated' : 'Deactivated';

            return response()->json([
                'status' => true,
                'radius' => $radius->radius,
                'message' => "Radius {$radius->radius} $statusText Successfully"
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to update: ' . $e->getMessage()]);
        }
    }



}

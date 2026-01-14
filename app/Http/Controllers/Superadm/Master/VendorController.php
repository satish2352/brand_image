<?php

// app/Http/Controllers/Superadm/VendorController.php
namespace App\Http\Controllers\Superadm\Master;

use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\Master\VendorService;
use Illuminate\Http\Request;
use Exception;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\VendorExport;

class VendorController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new VendorService();
    }

    public function index()
    {
        $vendors = $this->service->list();
        return view('superadm.vendor.list', compact('vendors'));
    }

    public function create()
    {
        return view('superadm.vendor.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'state_id'    => 'required|integer|exists:states,id',
            'district_id' => 'required|integer|exists:districts,id',
            'city_id'     => 'required|integer|exists:cities,id',

            'vendor_name' => 'required|string|max:255',
            'vendor_code' => 'required|string|max:100',
            'mobile'      => 'required|digits:10',
            'email'       => 'required|email:rfc,dns',
            'address'     => 'required|string|min:5',
        ], [
            'mobile.digits' => 'Mobile number must be exactly 10 digits',
            'email.email'   => 'Enter a valid email (example@domain.co)',
        ]);

        try {
            $this->service->store($validated);
            return redirect()->route('vendor.list')->with('success', 'Vendor added successfully');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit($encodedId)
    {
        $id = base64_decode($encodedId);
        $vendor = $this->service->find($id);

        return view('superadm.vendor.edit', compact('vendor', 'encodedId'));
    }

    public function update(Request $request, $encodedId)
    {
        $id = base64_decode($encodedId);

        $validated = $request->validate([
            'state_id'    => 'required|integer|exists:states,id',
            'district_id' => 'required|integer|exists:districts,id',
            'city_id'     => 'required|integer|exists:cities,id',

            'vendor_name' => 'required|string|max:255',
            'vendor_code' => 'required|string|max:100',
            'mobile'      => 'required|digits:10',
            'email'       => 'required|email:rfc,dns',
            'address'     => 'required|string|min:5',
        ]);

        try {
            $this->service->update($id, $validated);
            return redirect()->route('vendor.list')->with('success', 'Vendor updated successfully');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function updateStatus(Request $request)
    {
        $id = base64_decode($request->id);
        $this->service->toggleStatus($id);

        return response()->json(['status' => true, 'message' => 'Status updated']);
    }

    public function delete(Request $request)
    {
        $id = base64_decode($request->id);
        $this->service->delete($id);

        return response()->json(['status' => true, 'message' => 'Vendor deleted']);
    }

    public function exportExcel()
    {
        return Excel::download(new VendorExport, 'vendors.xlsx');
    }
}

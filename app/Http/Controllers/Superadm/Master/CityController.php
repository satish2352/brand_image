<?php

namespace App\Http\Controllers\Superadm\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Services\Superadm\Master\CityService;
use Exception;
use Illuminate\Support\Facades\Log;

class CityController extends Controller
{
    protected $cityService;

    public function __construct()
    {
        $this->cityService = new CityService();
    }

    public function index()
    {
        try {
            $cities = $this->cityService->getAllCities();
            return view('superadm.city.list', compact('cities'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function create()
    {
        return view('superadm.city.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'state_id'    => 'required|exists:states,id',
            'district_id' => 'required|exists:districts,id',
            'city_name'   => 'required|string|max:255',
            'latitude'    => 'required|numeric',
            'longitude'   => 'required|numeric',
        ]);

        try {
            $this->cityService->storeCity($validated);

            return redirect()->route('city.list')
                ->with('success', 'City added successfully.');
        } catch (Exception $e) {
            Log::error('City Store Error: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['city_name' => $e->getMessage()]);
        }
    }

    public function updateStatus(Request $request)
    {
        $id = base64_decode($request->id);
        $this->cityService->toggleStatus($id);

        return response()->json(['status' => true, 'message' => 'Status updated']);
    }

    public function delete(Request $req)
    {
        try {
            $id = base64_decode($req->id);
            $this->cityService->deleteCity($id);
            return redirect()->route('city.list')->with('success', 'City deleted successfully.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    public function edit($encodedId)
    {
        try {
            $id = base64_decode($encodedId);
            $city = $this->cityService->getCityById($id);

            return view('superadm.city.edit', compact('city', 'encodedId'));
        } catch (\Exception $e) {
            return redirect()->route('city.list')->with('error', 'City not found');
        }
    }

    public function update(Request $request, $encodedId)
    {
        $id = base64_decode($encodedId);

        $validated = $request->validate([
            'state_id'    => 'required|exists:states,id',
            'district_id' => 'required|exists:districts,id',
            'city_name'   => 'required|string|max:255',
            'latitude'    => 'required|numeric',
            'longitude'   => 'required|numeric',
        ]);

        try {
            $this->cityService->updateCity($id, $validated);

            return redirect()->route('city.list')
                ->with('success', 'City updated successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}

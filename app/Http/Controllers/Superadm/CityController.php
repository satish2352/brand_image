<?php

namespace App\Http\Controllers\Superadm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\CityService;
use App\Models\State;
use Illuminate\Validation\Rule;
use Exception;

class CityController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new CityService();
    }

    public function index()
    {
        $cities = $this->service->list();
        return view('superadm.city.list', compact('cities'));
    }

    public function create()
    {
        $states = State::where('is_active', 1)->where('is_deleted', 0)->get();
        // optionally send empty districts array
        $districts = collect();
        return view('superadm.city.create', compact('states','districts'));
    }

    public function save(Request $req)
    {
        $req->validate([
            'state_id' => 'required',
            'district_id' => 'required',
            'city' => [
                'required',
                'regex:/^[A-Za-z ]+$/',
                Rule::unique('cities', 'city')->where(fn($q) => $q->where('district_id', $req->district_id)->where('is_deleted', 0)),
            ]
        ], [
            'state_id.required' => 'Select State',
            'district_id.required' => 'Select District',
            'city.required' => 'Enter City Name',
            'city.regex' => 'City May Only Contain Letters And Spaces.',
            'city.unique' => 'This City Already Exists Under Selected District.'
        ]);

        $this->service->save($req);

        return redirect()->route('cities.list')->with('success', 'City Added Successfully.');
    }

    public function edit($idEncoded)
    {
        try {
            $id = base64_decode($idEncoded);
            $cities = $this->service->edit($id);

            // Load all states
            $states = State::where('is_active', 1)->where('is_deleted', 0)->get();

            // Load districts of selected state
            $districts = \App\Models\District::where('state_id', $cities->state_id)
                        ->where('is_active', 1)
                        ->where('is_deleted', 0)
                        ->get();

            return view('superadm.city.edit', compact('cities', 'states', 'districts', 'idEncoded'));

        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong: '.$e->getMessage());
        }
    }

    public function update(Request $req)
    {
        $req->validate([
            'state_id' => 'required',
            'district_id' => 'required',
            'city' => [
                'required',
                'regex:/^[A-Za-z ]+$/',

                Rule::unique('cities', 'city')
                    ->where(function ($query) use ($req) {
                        return $query->where('state_id', $req->state_id)
                                    ->where('district_id', $req->district_id)
                                    ->where('is_deleted', 0);
                    })
                    ->ignore($req->id)
            ],
        ], [
            'state_id.required' => 'Select State',
            'district_id.required' => 'Select District',
            'city.required' => 'Enter City Name',
            'city.regex' => 'City May Only Contain Letters And Spaces.',
            'city.unique' => 'This City Already Exists Under Selected District.'
        ]);

        $this->service->update($req);

        return redirect()->route('cities.list')->with('success', 'City Updated Successfully.');
    }

    public function delete(Request $req)
    {
        $req->validate(['id' => 'required']);

        $this->service->delete($req);

        return redirect()->route('cities.list')->with('success', 'City Deleted Successfully.');
    }

    public function updateStatus(Request $request)
    {
        try {
            $id = base64_decode($request->id);
            $city = $this->service->find($id);

            if (!$city) {
                return response()->json(['status' => false, 'message' => 'City not found'], 404);
            }

            $city->is_active = $request->is_active;
            $city->save();

            $statusText = $city->is_active ? 'Activated' : 'Deactivated';
            $message = "City '{$city->city}' Status {$statusText} Successfully";

            return response()->json(['status' => true, 'message' => $message]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getByDistrictWebsite(Request $req)
    {
        $cities = \App\Models\City::where('district_id', $req->district_id)
            ->where('is_active', 1)
            ->where('is_deleted', 0)
            ->get();

        return response()->json($cities);
    }

}

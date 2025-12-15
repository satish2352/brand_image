<?php
namespace App\Http\Controllers\Superadm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\LocationService;
use App\Models\State;
use Illuminate\Validation\Rule;
use Exception;

class LocationController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new LocationService();
    }

    public function index()
    {
        $locations = $this->service->list();
        // fetch types map for label showing
        $types = \DB::table('types')->where('is_deleted', 0)->pluck('type', 'id')->toArray();
        return view('superadm.location.list', compact('locations','types'));
    }

    public function create()
    {
        $states = State::where('is_active',1)->where('is_deleted',0)->get();
        $types = \DB::table('types')->where('is_active',1)->where('is_deleted',0)->get();
        $districts = collect();
        $cities = collect();
        return view('superadm.location.create', compact('states','districts','cities','types'));
    }

    public function save(Request $req)
    {
        $req->validate([
            'state_id' => 'required',
            'district_id' => 'required',
            'city_id' => 'required',
            'radius' => [
                'required',
                'max:50',
                'regex:/^\s*\d+\s*km\s*-\s*\d+\s*km\s*$/i'
            ],
            'type_id' => 'required|exists:types,id',
        ], [
            'state_id.required' => 'Select State',
            'district_id.required' => 'Select District/Area',
            'city_id.required' => 'Select City',
            'radius.required' => 'Enter Radius (e.g. 5km-10km)',
            'radius.regex' => 'Radius must be in format: 5km-10km".',
            'type_id.required' => 'Select Type',
            'type_id.exists' => 'Selected Type invalid',
        ]);

        if (preg_match('/(\d+)\s*km\s*-\s*(\d+)\s*km/i', $req->radius, $m)) {
            if ((int)$m[1] > (int)$m[2]) {
                return back()->withInput()->withErrors(['radius' => 'Invalid radius range, start must be <= end.']);
            }
        }

        try {
            $this->service->save($req);
            return redirect()->route('locations.list')->with('success','Location saved successfully.');
        } catch (Exception $e) {
            return back()->withErrors(['radius' => $e->getMessage()])->withInput();
        }
    }

    public function edit($encodedId)
    {
        try {
            $id = base64_decode($encodedId);
            $data = $this->service->edit($id);

            $states = State::where('is_active',1)->where('is_deleted',0)->get();
            $types = \DB::table('types')->where('is_active',1)->where('is_deleted',0)->get();

            $districts = \App\Models\District::where('state_id', $data->state_id)->where('is_deleted',0)->get();
            $cities = \App\Models\City::where('district_id', $data->district_id)->where('is_deleted',0)->get();

            return view('superadm.location.edit', compact('data','states','districts','cities','types','encodedId'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function update(Request $req)
    {
        $req->validate([
            'id' => 'required',
            'state_id' => 'required',
            'district_id' => 'required',
            'city_id' => 'required',
            'radius' => [
                'required',
                'max:50',
                'regex:/^\s*\d+\s*km\s*-\s*\d+\s*km\s*$/i'
            ],
            'type_id' => 'required|exists:types,id',
        ], [
            'id.required' => 'ID required',
            'state_id.required' => 'Select State',
            'district_id.required' => 'Select District/Area',
            'city_id.required' => 'Select City',
            'radius.required' => 'Enter Radius',
            'radius.regex' => 'Radius must be in format: 5km-10km".',
            'type_id.required' => 'Select Type',
        ]);

        if (preg_match('/(\d+)\s*km\s*-\s*(\d+)\s*km/i', $req->radius, $m)) {
            if ((int)$m[1] > (int)$m[2]) {
                return back()->withInput()->withErrors(['radius' => 'Invalid radius range, start must be <= end.']);
            }
        }

        try {
            $this->service->update($req);
            return redirect()->route('locations.list')->with('success','Location updated successfully.');
        } catch (Exception $e) {
            return back()->withErrors(['radius' => $e->getMessage()])->withInput();
        }
    }

    public function delete(Request $req)
    {
        $req->validate(['id' => 'required']);
        try {
            $this->service->delete($req);
            return redirect()->route('locations.list')->with('success','Location deleted successfully.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateStatus(Request $request)
    {
        try {
            $id = base64_decode($request->id);
            $location = $this->service->find($id);
            if (!$location) {
                return response()->json(['status'=>false,'message'=>'Location not found'],404);
            }
            $location->is_active = $request->is_active;
            $location->save();

            $statusText = $location->is_active ? 'Activated' : 'Deactivated';
            $message = "Location status {$statusText} Successfully";

            return response()->json(['status'=>true,'message'=>$message]);
        } catch (Exception $e) {
            return response()->json(['status'=>false,'message'=>'Failed to update status: '.$e->getMessage()],500);
        }
    }
}

<?php
namespace App\Http\Controllers\Superadm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\DistrictService;
use App\Models\State;
use Illuminate\Validation\Rule;
use Exception;

class DistrictController extends Controller
{
    protected $service;
    public function __construct()
    {
        $this->service = new DistrictService();
    }

    public function index()
    {
        $districts = $this->service->list();
        return view('superadm.district.list', compact('districts'));
    }

    public function create()
    {
        $states = State::where('is_active',1)->where('is_deleted',0)->get();
        return view('superadm.district.create', compact('states'));
    }

    public function save(Request $req)
    {
        $req->validate([
            'state_id' => 'required',
            'district' => [
                'required',
                'max:255',
                'regex:/^[A-Za-z ]+$/',
                Rule::unique('districts', 'district')->where(fn($q) => $q->where('state_id', $req->state_id)->where('is_deleted',0)),
            ],
        ], [
            'state_id.required' => 'Select State',
            'district.required' => 'Enter District Name',
            'district.max' => 'District Name Must Not Exceed 255 Characters.',
            'district.unique' => 'This District Already Exists In Selected State.',
            'district.regex' => 'District Name Must Contain Only Letters And Spaces.',
        ]);

        try {
            $this->service->save($req);
            return redirect()->route('districts.list')->with('success', 'District Added Successfully.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit($encodedId)
    {
        $id = base64_decode($encodedId);
        $data = $this->service->edit($id);
        $states = State::where('is_deleted',0)->get();
        return view('superadm.district.edit', compact('data','encodedId','states'));
    }

    public function update(Request $req)
    {
        $req->validate([
            'state_id' => 'required',
            'district' => [
                'required',
                'max:255',
                'regex:/^[A-Za-z ]+$/',
                Rule::unique('districts', 'district')->where(fn($q) => $q->where('state_id', $req->state_id)->where('is_deleted',0))->ignore($req->id),
            ],
            'id' => 'required',
        ], [
            'state_id.required' => 'Select State',
            'district.required' => 'Enter District Name',
            'district.unique' => 'This District Already Exists In Selected State.',
            'id.required' => 'ID Required',
            'district.regex' => 'District Name Must Contain Only Letters And Spaces.',
        ]);

        try {
            $this->service->update($req);
            return redirect()->route('districts.list')->with('success', 'District Updated Successfully.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function delete(Request $req)
    {
        $req->validate(['id'=>'required']);
        try {
            $this->service->delete($req);
            return redirect()->route('districts.list')->with('success','District Deleted Successfully.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // API: get districts for a state (AJAX)
    public function getByState(Request $req)
    {
        $stateId = $req->state_id;
        $list = $this->service->getByState($stateId);
        return response()->json(['status'=>true,'data'=>$list]);
    }

    public function updateStatus(Request $request)
    {
        try {
            $id = base64_decode($request->id);
            $district = $this->service->find($id);

            if (!$district) {
                return response()->json([
                    'status' => false,
                    'message' => 'District not found'
                ], 404);
            }

            $district->is_active = $request->is_active;
            $district->save();

            $statusText = $district->is_active ? 'Activated' : 'Deactivated';
            $message = "District '{$district->district}' Status {$statusText} Successfully";

            return response()->json([
                'status' => true,
                'message' => $message
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update status: '.$e->getMessage()
            ], 500);
        }
    }

    public function getByStateWebsite(Request $req)
    {
        $districts = \App\Models\District::where('state_id', $req->state_id)
            ->where('is_active', 1)
            ->where('is_deleted', 0)
            ->get();

        return response()->json($districts);
    }

}

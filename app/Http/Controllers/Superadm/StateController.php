<?php
namespace App\Http\Controllers\Superadm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\StateService;
use Illuminate\Validation\Rule;
use Exception;

class StateController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new StateService();
    }

    public function index()
    {
        try {
            $states = $this->service->list();
            return view('superadm.state.list', compact('states'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return view('superadm.state.create');
    }

    public function save(Request $req)
    {
        $req->validate([
            'state' => [
                'required',
                'max:255',
                'regex:/^[A-Za-z\s&]+$/',
                Rule::unique('states', 'state')->where(fn($q) => $q->where('is_deleted', 0)),
            ],
        ], [
            'state.required' => 'Enter State Name',
            'state.max' => 'State Name Must Not Exceed 255 Characters.',
            'state.unique' => 'This State Already Exists.',
            'state.regex' => 'State Name Must Contain Only Letters, Spaces, And "&". Numbers Or Special Characters Are Not Allowed.',
        ]);

        try {
            $this->service->save($req);
            return redirect()->route('states.list')->with('success', 'State Added Successfully.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit($encodedId)
    {
        try {
            $id = base64_decode($encodedId);
            $data = $this->service->edit($id);
            return view('superadm.state.edit', compact('data', 'encodedId'));
        } catch (Exception $e) {
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function update(Request $req)
    {
        $req->validate([
            'state' => [
                'required',
                'max:255',
                'regex:/^[A-Za-z\s&]+$/',
                Rule::unique('states', 'state')
                    ->where(fn($q) => $q->where('is_deleted', 0))
                    ->ignore($req->id),
            ],
            'id' => 'required',
        ], [
            'state.required' => 'Enter State Name',
            'state.max' => 'State Name Must Not Exceed 255 Characters.',
            'state.unique' => 'This State Already Exists.',
            'state.regex' => 'State Name Must Contain Only Letters, Spaces, And "&". Numbers Or Special Characters Are Not Allowed.',
            'id.required' => 'ID Required',
        ]);

        try {
            $this->service->update($req);
            return redirect()->route('states.list')->with('success', 'State Updated Successfully.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function delete(Request $req)
    {
        $req->validate([
            'id' => 'required',
        ], [
            'id.required' => 'ID Required',
        ]);

        try {
            $this->service->delete($req);
            return redirect()->route('states.list')->with('success', 'State Deleted Successfully.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateStatus(Request $request)
    {
        try {
            $id = base64_decode($request->id);
            $state = $this->service->find($id);

            if (!$state) {
                return response()->json(['status' => false, 'message' => 'State not found'], 404);
            }

            $state->is_active = $request->is_active;
            $state->save();

            $statusText = $state->is_active ? 'Activated' : 'Deactivated';
            $message = "State '{$state->state}' Status {$statusText} Successfully";

            return response()->json(['status' => true, 'message' => $message]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

}

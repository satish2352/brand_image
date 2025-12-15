<?php
namespace App\Http\Controllers\Superadm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\MediaService;
use App\Models\State;
use Exception;
use Illuminate\Validation\Rule;

class MediaController extends Controller
{
    protected $service;
    public function __construct()
    {
        $this->service = new MediaService();
    }

    public function index()
    {
        $media = $this->service->list();
        // fetch types for label
        $types = \DB::table('types')->where('is_deleted',0)->pluck('type','id')->toArray();
        return view('superadm.media.list', compact('media','types'));
    }

    public function create()
    {
        $states = State::where('is_active',1)->where('is_deleted',0)->get();
        $types = \DB::table('types')->where('is_active',1)->where('is_deleted',0)->get();
        $radii = \DB::table('radius_master')->where('is_deleted',0)->get();

        $districts = collect();
        $cities = collect();
        return view('superadm.media.create', compact('states','districts','cities','types','radii'));
    }

    public function save(Request $req)
    {
        $req->validate([
            'state_id' => 'required',
            'district_id' => 'required',
            'city_id' => 'required',
            'location_name' => 'required|max:255',
            'type_id' => 'required|exists:types,id',
            'radius_id' => 'nullable|exists:radius_master,id',
            'price' => 'nullable|numeric',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(['Available','Booked','Under Maintenance'])],
            'images' => 'required|array|min:1',
            'images.*' => 'image|max:2048', // max 2MB per image
        ], [
            'images.required' => 'Please upload at least one image (max 2MB each).',
            'images.*.image' => 'Each file must be an image.',
            'images.*.max' => 'Each image must be <= 2MB.',
        ]);

        try {
            $this->service->save($req);
            return redirect()->route('media.list')->with('success','Media saved successfully.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit($idEncoded)
    {
        try {
            $id = base64_decode($idEncoded);
            $data = $this->service->edit($id);

            $states = State::where('is_active',1)->where('is_deleted',0)->get();
            $types = \DB::table('types')->where('is_active',1)->where('is_deleted',0)->get();
            $radii = \DB::table('radius_master')->where('is_deleted',0)->get();

            $districts = \App\Models\District::where('state_id', $data->state_id)->where('is_deleted',0)->get();
            $cities = \App\Models\City::where('district_id', $data->district_id)->where('is_deleted',0)->get();

            return view('superadm.media.edit', compact('data','states','districts','cities','types','radii','idEncoded'));
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
            'location_name' => 'required|max:255',
            'type_id' => 'required|exists:types,id',
            'radius_id' => 'nullable|exists:radius_master,id',
            'price' => 'nullable|numeric',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(['Available','Booked','Under Maintenance'])],
            'images.*' => 'sometimes|image|max:2048',
        ], [
            'images.*.image' => 'Each file must be image.',
            'images.*.max' => 'Each image must be <= 2MB.',
        ]);

        try {
            $this->service->update($req);
            return redirect()->route('media.list')->with('success','Media updated successfully.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function delete(Request $req)
    {
        $req->validate(['id' => 'required']);
        try {
            $this->service->delete($req);
            return redirect()->route('media.list')->with('success','Media deleted successfully.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateStatus(Request $request)
    {
        try {
            $id = base64_decode($request->id);
            $media = $this->service->find($id);
            if (!$media) return response()->json(['status'=>false,'message'=>'Media not found'],404);

            $media->is_active = $request->is_active;
            $media->save();

            $statusText = $media->is_active ? 'Activated' : 'Deactivated';
            return response()->json(['status'=>true,'message'=>"Media status {$statusText} Successfully"]);
        } catch (Exception $e) {
            return response()->json(['status'=>false,'message'=>'Failed: '.$e->getMessage()],500);
        }
    }

    // soft delete image AJAX
    public function deleteImage(Request $req)
    {
        $req->validate(['image_id' => 'required']);
        try {
            $this->service->softDeleteImage($req->image_id);
            return response()->json(['status'=>true,'message'=>'Image removed (soft deleted)']);
        } catch (Exception $e) {
            return response()->json(['status'=>false,'message'=>$e->getMessage()],500);
        }
    }
}

<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\HomeSliderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HomeSliderController extends Controller
{
    protected $service;

    public function __construct(HomeSliderService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $sliders = $this->service->list();
        return view('superadm.homesliders.list', compact('sliders'));
    }

    public function create()
    {
        return view('superadm.homesliders.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'desktop_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:1024',
            'mobile_image'  => 'required|image|mimes:jpg,jpeg,png,webp|max:1024',
        ];

        $messages = [
            'desktop_image.required' => 'Desktop image is required',
            'desktop_image.max' => 'Desktop image size must be less than 1 MB',
            'mobile_image.required' => 'Mobile image is required',
            'mobile_image.max' => 'Mobile image size must be less than 1 MB',
        ];

        Validator::make($request->all(), $rules, $messages)
            ->after(function ($validator) use ($request) {

                if ($request->hasFile('desktop_image')) {
                    [$w, $h] = getimagesize($request->file('desktop_image'));
                    if ($w != 2000 || $h != 600) {
                        $validator->errors()->add(
                            'desktop_image',
                            'Desktop image size must be exactly 2000 x 600 pixels'
                        );
                    }
                }

                if ($request->hasFile('mobile_image')) {
                    [$w, $h] = getimagesize($request->file('mobile_image'));
                    if ($w != 2000 || $h != 900) {
                        $validator->errors()->add(
                            'mobile_image',
                            'Mobile image size must be exactly 2000 x 900 pixels'
                        );
                    }
                }
            })->validate();

        $desktop = uploadImage($request->desktop_image, config('fileConstants.IMAGE_ADD'));
        $mobile  = uploadImage($request->mobile_image, config('fileConstants.IMAGE_ADD'));

        $this->service->store([
            'desktop_image' => $desktop,
            'mobile_image'  => $mobile,
            'is_active'     => 1,
            'is_deleted'    => 0,
        ]);

        return redirect()->route('homeslider.list')
            ->with('success', 'Slider added successfully');
    }


    public function toggleStatus(Request $request)
    {
        $this->service->toggleStatus(base64_decode($request->id));
        return response()->json(['status' => true]);
    }

    public function delete(Request $request)
    {
        $this->service->delete(base64_decode($request->id));
        return back()->with('success', 'Slider deleted successfully');
    }

    public function edit($encodedId)
    {
        $id = base64_decode($encodedId);
        $slider = $this->service->find($id);

        return view('superadm.homesliders.edit', compact('slider'));
    }

    public function update(Request $request, $encodedId)
    {
        $id = base64_decode($encodedId);

        $rules = [
            'desktop_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:1024',
            'mobile_image'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:1024',
        ];

        $messages = [
            'desktop_image.max' => 'Desktop image size must be less than 1 MB',
            'mobile_image.max'  => 'Mobile image size must be less than 1 MB',
        ];

        Validator::make($request->all(), $rules, $messages)
            ->after(function ($validator) use ($request) {

                if ($request->hasFile('desktop_image')) {
                    [$w, $h] = getimagesize($request->file('desktop_image'));
                    if ($w != 2000 || $h != 600) {
                        $validator->errors()->add(
                            'desktop_image',
                            'Desktop image must be exactly 2000 × 600 pixels'
                        );
                    }
                }

                if ($request->hasFile('mobile_image')) {
                    [$w, $h] = getimagesize($request->file('mobile_image'));
                    if ($w != 2000 || $h != 900) {
                        $validator->errors()->add(
                            'mobile_image',
                            'Mobile image must be exactly 2000 × 900 pixels'
                        );
                    }
                }
            })->validate();

        $data = [];

        if ($request->hasFile('desktop_image')) {
            $data['desktop_image'] =
                uploadImage($request->desktop_image, config('fileConstants.IMAGE_ADD'));
        }

        if ($request->hasFile('mobile_image')) {
            $data['mobile_image'] =
                uploadImage($request->mobile_image, config('fileConstants.IMAGE_ADD'));
        }

        $this->service->update($id, $data);

        return redirect()->route('homeslider.list')
            ->with('success', 'Slider updated successfully');
    }
}

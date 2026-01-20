<?php

namespace App\Http\Controllers\Website;

use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use App\Http\Services\Website\ContactService;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    protected $contactService;

    public function __construct(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    public function create(Request $request)
    {
        $encodedMediaId = $request->query('media');

        if ($encodedMediaId && base64_encode(base64_decode($encodedMediaId, true)) === $encodedMediaId) {
            $mediaId = base64_decode($encodedMediaId); // decoded ONCE
        } else {
            $mediaId = null;
        }

        return view('website.contact-us', compact('mediaId'));
    }



    public function store(Request $request)
    {

        $request->validate([
            'media_id'  => 'nullable',
            'full_name' => 'required|string|max:255',
            'mobile_no' => 'required|digits_between:10,15',
            'email'     => 'required|email|max:255',
            'address'   => 'required|string',
            'remark'    => 'required|string',
            'g-recaptcha-response' => 'required',

        ], [
            'g-recaptcha-response.required' => 'Please verify that you are not a robot',

        ]);

        try {
            $mediaId = $request->filled('media_id')
                ? (int) $request->media_id
                : null;


            $this->contactService->save([
                'media_id'  => $mediaId,
                'full_name' => $request->full_name,
                'mobile_no' => $request->mobile_no,
                'email'     => $request->email,
                'address'   => $request->address,
                'remark'    => $request->remark,
                'is_active' => 1,
                'is_delete' => 0,
            ]);

            return redirect()->back()->with('success', 'Thank you! We will contact you soon.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }
}

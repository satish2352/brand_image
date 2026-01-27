<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Services\Website\ContactService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    protected $contactService;

    public function __construct(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    public function create(Request $request)
    {
        $mediaId = null;

        if ($request->filled('media')) {
            $decoded = base64_decode($request->media, true);
            if ($decoded !== false) {
                $mediaId = $decoded;
            }
        }

        return view('website.contact-us', compact('mediaId'));
    }

    public function store(Request $request)
    {
        //  VALIDATION ONLY (NO SERVER CAPTCHA CALL)
        $request->validate([
            'media_id'  => 'nullable|integer',
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

            $this->contactService->save([
                'media_id'  => $request->media_id,
                'full_name' => $request->full_name,
                'mobile_no' => $request->mobile_no,
                'email'     => $request->email,
                'address'   => $request->address,
                'remark'    => $request->remark,
                // 'is_active' => 1,
                // 'is_delete' => 0,
            ]);

            return back()->with('success', 'Thank you! We will contact you soon.');
        } catch (\Throwable $e) {

            Log::error('Contact form error: ' . $e->getMessage());

            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }
}

<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Services\Website\ContactService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    protected ContactService $contactService;

    public function __construct(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    /**
     * Show contact form
     */
    public function create(Request $request)
    {
        $mediaId = null;

        if ($request->filled('media')) {
            $decoded = base64_decode($request->media, true);
            if ($decoded !== false) {
                $mediaId = (int) $decoded;
            }
        }

        return view('website.contact-us', compact('mediaId'));
    }

    /**
     * Store contact request
     */
    public function store(Request $request)
    {
        /* ================= VALIDATION ================= */
        $request->validate([
            'media_id'  => 'nullable|integer',
            'full_name' => 'required|string|max:255',
            'mobile_no' => 'required|digits_between:10,15',
            'email'     => 'required|email|max:255',
            'address'   => 'required|string|max:200',
            'remark'    => 'required|string|max:300',
            'g-recaptcha-response' => 'required',
        ], [
            'g-recaptcha-response.required' => 'Please verify that you are not a robot',
        ]);

        /* ================= RECAPTCHA VERIFY ================= */
        $captchaResponse = Http::asForm()->post(
            'https://www.google.com/recaptcha/api/siteverify',
            [
                'secret'   => env('RECAPTCHA_SECRET_KEY'),
                'response' => $request->input('g-recaptcha-response'),
                'remoteip' => $request->ip(),
            ]
        );

        if (!$captchaResponse->json('success')) {
            return back()
                ->withErrors([
                    'g-recaptcha-response' => 'Captcha verification failed, please try again',
                ])
                ->withInput();
        }

        /* ================= SAVE DATA ================= */
        try {
            $this->contactService->save([
                'media_id'   => $request->filled('media_id') ? (int) $request->media_id : null,
                'full_name'  => $request->full_name,
                'mobile_no'  => $request->mobile_no,
                'email'      => $request->email,
                'address'    => $request->address,
                'remark'     => $request->remark,
                'is_active'  => 1,
                'is_deleted' => 0,
            ]);

            return redirect()
                ->back()
                ->with('success', 'Thank you! We will contact you shortly.');
        } catch (\Throwable $e) {

            Log::error('Contact form error: ' . $e->getMessage());

            return back()
                ->with('error', 'Something went wrong. Please try again later.')
                ->withInput();
        }
    }
}

// namespace App\Http\Controllers\Website;

// use Illuminate\Support\Facades\Http;
// use App\Http\Controllers\Controller;
// use App\Http\Services\Website\ContactService;
// use Illuminate\Http\Request;

// class ContactController extends Controller
// {
//     protected $contactService;

//     public function __construct(ContactService $contactService)
//     {
//         $this->contactService = $contactService;
//     }

//     public function create(Request $request)
//     {
//         $encodedMediaId = $request->query('media');

//         if ($encodedMediaId && base64_encode(base64_decode($encodedMediaId, true)) === $encodedMediaId) {
//             $mediaId = base64_decode($encodedMediaId); // decoded ONCE
//         } else {
//             $mediaId = null;
//         }

//         return view('website.contact-us', compact('mediaId'));
//     }



//     public function store(Request $request)
//     {

//         $request->validate([
//             'media_id'  => 'nullable',
//             'full_name' => 'required|string|max:255',
//             'mobile_no' => 'required|digits_between:10,15',
//             'email'     => 'required|email|max:255',
//             'address'   => 'required|string',
//             'remark'    => 'required|string',
//             'g-recaptcha-response' => 'required',

//         ], [
//             'g-recaptcha-response.required' => 'Please verify that you are not a robot',

//         ]);

//         try {
//             $mediaId = $request->filled('media_id')
//                 ? (int) $request->media_id
//                 : null;


//             $this->contactService->save([
//                 'media_id'  => $mediaId,
//                 'full_name' => $request->full_name,
//                 'mobile_no' => $request->mobile_no,
//                 'email'     => $request->email,
//                 'address'   => $request->address,
//                 'remark'    => $request->remark,
//                 'is_active' => 1,
//                 'is_delete' => 0,
//             ]);

//             return redirect()->back()->with('success', 'Thank you! We will contact you soon.');
//         } catch (\Exception $e) {
//             return redirect()->back()->with('error', 'Something went wrong');
//         }
//     }
// }

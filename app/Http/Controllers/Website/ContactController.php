<?php

namespace App\Http\Controllers\Website;

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

    public function create()
    {
        return view('website.contact-us');
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name'  => 'required|string|max:255',
            'mobile_no'  => 'required|digits_between:10,15',
            'email'      => 'required|email|max:255',
            'address'    => 'required|string',
            'remark'     => 'required|string',
        ]);

        try {
            $this->contactService->save([
                'full_name' => $request->full_name,
                'mobile_no' => $request->mobile_no,
                'email'     => $request->email,
                'address'   => $request->address,
                'remark'    => $request->remark,
                'is_active' => 1,
                'is_delete' => 0,
            ]);

            return redirect()->back()->with('success', 'Contact submitted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }
}

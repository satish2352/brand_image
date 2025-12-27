<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\ContactUsService;
use Illuminate\Http\Request;

class ContactUsController extends Controller
{
    protected $service;

    public function __construct(ContactUsService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $contacts = $this->service->list();
        return view('superadm.contact-us.contact-us-list', compact('contacts'));
    }

    public function delete(Request $request)
    {
        $this->service->delete(base64_decode($request->id));
        return back()->with('success', 'Contact deleted successfully');
    }
}

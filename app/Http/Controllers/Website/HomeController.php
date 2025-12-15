<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\State;

class HomeController extends Controller
{
    public function index()
    {
        // fetch active states for search dropdown
        $states = State::where('is_active', 1)
                       ->where('is_deleted', 0)
                       ->orderBy('state', 'asc')
                       ->get();

        return view('website.home', compact('states'));
    }
}

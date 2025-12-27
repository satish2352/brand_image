<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Services\Website\HomeService;
use Illuminate\Support\Facades\Log;
use Throwable;

class HomeController extends Controller
{
    protected HomeService $homeService;

    // ✅ Dependency Injection
    public function __construct(HomeService $homeService)
    {
        $this->homeService = $homeService;
    }

    public function index()
    {
        try {
            $mediaList = $this->homeService->getAllMediaCartsData();

            return view('website.home', compact('mediaList'));
        } catch (Throwable $e) {

            // ✅ Log exact error
            Log::error('Home page error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            // ✅ Safe fallback
            return view('website.home', [
                'mediaList' => []
            ])->with('error', 'Unable to load media at the moment.');
        }
    }
}

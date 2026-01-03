<?php

namespace App\Http\Controllers\Website;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Services\Website\HomeService;
use Illuminate\Http\Request;
use Throwable;
use Exception;
use Illuminate\Support\Facades\DB;


class HomeController extends Controller
{
    public function __construct(private HomeService $homeService) {}

    // Initial page load (NO FILTERS)
    public function index()
    {
        $filters = []; //  IMPORTANT
        $mediaList = $this->homeService->searchMedia($filters);

        return view('website.home', compact('mediaList', 'filters'));
    }
    public function search(Request $request)
    {
        if ($request->filled('clear')) {
            return redirect()->route('website.home');
        }

        $filters = $request->only([
            'category_id',
            'state_id',
            'district_id',
            'city_id',
            'area_id',
            'radius_id',
            'from_date',
            'to_date',
            'area_type',
            'available_days',
        ]);

        $mediaList = $this->homeService->searchMedia($filters);

        //  Lazy load AJAX
        if ($request->ajax()) {
            return view('website.media-home-list', compact('mediaList'))->render();
        }

        //  IMPORTANT: load SEARCH page, not HOME
        return view('website.search', compact('mediaList', 'filters'));
    }



    public function getMediaDetails($mediaId)
    {
        try {
            $mediaId = base64_decode($mediaId);

            if (!$mediaId) {
                abort(404);
            }

            $media = $this->homeService->getMediaDetails($mediaId);

            if (!$media) {
                abort(404);
            }

            //  Get booked date ranges from order_items
            $bookedRanges = DB::table('order_items')
                ->where('media_id', $mediaId)
                ->select('from_date', 'to_date')
                ->get();

            return view('website.media-details', compact('media', 'bookedRanges'));
        } catch (Exception $e) {

            Log::error('Media Details Error', [
                'media_id' => $mediaId,
                'message'  => $e->getMessage()
            ]);

            return redirect()
                ->route('website.home')
                ->with('error', 'Unable to load media details.');
        }
    }
}

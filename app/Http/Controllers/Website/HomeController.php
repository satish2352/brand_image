<?php

namespace App\Http\Controllers\Website;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Services\Website\HomeService;
use Illuminate\Http\Request;
use Throwable;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\HomeSlider;


class HomeController extends Controller
{
    public function __construct(private HomeService $homeService) {}

    public function index()
    {
        $filters = [];
        $mediaList = $this->homeService->searchMedia($filters);

        // ADD THIS (ONLY ACTIVE SLIDERS)
        $sliders = HomeSlider::where('is_active', 1)
            ->where('is_deleted', 0)
            ->orderBy('id', 'desc')
            ->get();

        // NEW (Other Media latest per category)
        $otherMedia = $this->homeService->getLatestOtherMediaByCategory();

        return view('website.home', compact('mediaList', 'filters', 'sliders', 'otherMedia'));
    }
    /** POST SEARCH - NO PARAMS IN URL */
    public function search(Request $request)
    {
        // if ($request->filled('clear')) {
        //     return redirect()->route('website.home');
        // }

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
            'min_price',   // <- add
            'max_price',   // <- add
        ]);

        $mediaList = $this->homeService->searchMedia($filters);

        // Lazy load POST
        if ($request->ajax()) {
            return view('website.media-home-list', compact('mediaList'))->render();
        }

        // IMPORTANT — return the view (NO redirect)
        return view('website.search', compact('mediaList', 'filters'));
    }

    // public function searchView()
    // {
    //     return redirect()->route('website.home');
    // }
    public function searchView()
{
    $filters = [];
    $mediaList = $this->homeService->searchMedia($filters);

    return view('website.search', compact('mediaList', 'filters'));
}

    public function getMediaDetails($mediaId)
    {
        try {
            $mediaId = base64_decode($mediaId);

            if (!$mediaId) abort(404);

            $media = $this->homeService->getMediaDetails($mediaId);

            if (!$media) abort(404);
            $orders = DB::table('order_items as oi')
                ->join('orders as o', 'o.id', '=', 'oi.order_id')
                ->where('oi.media_id', $mediaId)
                ->where('oi.is_deleted', 0)
                ->where('o.payment_status', 'PAID') // 🔑 KEY FIX
                ->select('oi.from_date', 'oi.to_date')
                ->orderBy('oi.from_date')
                ->get();

            // MERGE OVERLAPPING RANGES
            $merged = [];
            foreach ($orders as $range) {
                if (empty($merged)) {
                    $merged[] = [
                        'from_date' => $range->from_date,
                        'to_date'   => $range->to_date
                    ];
                    continue;
                }

                $lastIndex = count($merged) - 1;
                $last = $merged[$lastIndex];

                // If overlapping or touching (14-30 and 15-31)
                if ($range->from_date <= $last['to_date']) {
                    $merged[$lastIndex]['to_date'] = max($last['to_date'], $range->to_date);
                } else {
                    $merged[] = [
                        'from_date' => $range->from_date,
                        'to_date'   => $range->to_date
                    ];
                }
            }

            $bookedRanges = $merged;

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

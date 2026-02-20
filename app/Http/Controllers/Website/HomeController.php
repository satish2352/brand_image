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

        $billboards = DB::table('media_management as m')

            ->leftJoin('cities as city', 'city.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->leftJoin('category as ct', 'ct.id', '=', 'm.category_id')
            ->leftJoin(DB::raw('
        (SELECT media_id, MIN(images) AS first_image
         FROM media_images
         WHERE is_deleted = 0 AND is_active = 1
         GROUP BY media_id
        ) mi
    '), 'mi.media_id', '=', 'm.id')

            ->where('m.is_deleted', 0)
            ->where('m.is_active', 1)

            ->select([
                'm.id',
                'm.media_title',
                'm.price',
                'm.category_id',
                'm.latitude',
                'm.longitude',
                'm.width',
                'm.height',
                'm.facing',
                'm.video_link',
                'ct.category_name',
                'a.area_name',
                's.state_name as state_name',
                'd.district_name as district_name',
                'city.city_name as city_name',
                'm.area_type',
                'a.common_stdiciar_name as common_area_name',
                'mi.first_image',
                DB::raw('ROUND(m.price / DAY(LAST_DAY(CURDATE())), 2) as per_day_price'),
                DB::raw("CASE
                    WHEN EXISTS (
                        SELECT 1 FROM media_booked_date mbd
                        WHERE mbd.media_id = m.id
                        AND mbd.is_deleted = 0
                        AND mbd.is_active = 1
                    )
                    THEN 1 ELSE 0
                END AS is_booked
            "),

            ])

            ->get();




        return view('website.home', compact('mediaList', 'filters', 'sliders', 'otherMedia', 'billboards'));
    }
    /** POST SEARCH - NO PARAMS IN URL */
    public function search(Request $request)
    {
        // if ($request->filled('clear')) {
        //     return redirect()->route('website.home');
        // }
        //  IF CLEAR BUTTON CLICKED
        if ($request->filled('clear')) {
            session()->forget('search_filters');   // ⭐ IMPORTANT

            $filters = [];
            $mediaList = $this->homeService->searchMedia($filters);
            return view('website.search', compact('mediaList', 'filters'));
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
            'min_price',   // <- add
            'max_price',   // <- add
        ]);
        // ⭐ SAVE FILTERS IN SESSION
        session(['search_filters' => $filters]);
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
        // $filters = [];
        // $filters = session('search_filters', []);
        // If page opened directly (reload/manual)
        if (!session()->has('search_filters')) {
            $filters = [];
        } else {
            $filters = session('search_filters');
        }


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
                ->whereIn('o.payment_status', ['PAID', 'ADMIN_BOOKED'])
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

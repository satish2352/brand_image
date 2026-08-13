<?php

namespace App\Http\Controllers\Website;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Services\Website\HomeService;
use Illuminate\Http\Request;
use Throwable;
use Illuminate\Support\Facades\DB;
use App\Models\HomeSlider;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;


class HomeController extends Controller
{
    public function __construct(private HomeService $homeService) {}

    private function getCachedAreaTypes()
    {
        return Cache::remember(
            'home_area_types',
            3600,
            fn() =>
            DB::table('areatype')->where('is_active', 1)->where('is_deleted', 0)->get()
        );
    }

    private function getCachedAreaRange()
    {
        return Cache::remember(
            'home_area_range',
            300,
            fn() =>
            DB::table('media_management')
                ->where('is_deleted', 0)
                ->where('is_active', 1)
                ->selectRaw('CAST(MIN(area_auto) AS UNSIGNED) as min_area, CAST(MAX(area_auto) AS UNSIGNED) as max_area')
                ->first()
        );
    }

    public function index()
    {
        $filters = [];
        // The search-form only reads $mediaList when a category filter is set,
        // which never happens on the home page — so skip the heavy search query
        // here and hand the view a cheap empty paginator instead.
        $mediaList = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);

        $sliders = Cache::remember(
            'home_sliders',
            1800,
            fn() =>
            HomeSlider::where('is_active', 1)->where('is_deleted', 0)->orderBy('id', 'desc')->get()
        );

        $otherMedia = Cache::remember(
            'home_other_media',
            600,
            fn() =>
            $this->homeService->getLatestOtherMediaByCategory()
        );

        $billboards = Cache::remember('home_billboards', 300, fn() =>
            DB::table('media_management as m')

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
                // 'm.video_link',
                'ct.category_name',
                'a.area_name',
                's.state_name as state_name',
                'd.district_name as district_name',
                'city.city_name as city_name',
                // 'm.area_type',
                'a.common_stdiciar_name as common_area_name',
                'mi.first_image',
                'm.panorama_image',
                DB::raw('ROUND(m.price / DAY(LAST_DAY(CURDATE())), 2) as per_day_price'),
                DB::raw("CASE
                    WHEN EXISTS (
                        SELECT 1 FROM media_booked_date mbd
                        WHERE mbd.media_id = m.id
                        AND mbd.is_deleted = 0
                        AND mbd.is_active = 1
                        AND mbd.to_date >= CURDATE()
                    )
                    THEN 1 ELSE 0
                END AS is_booked
            "),

            ])

            ->get()
        );

        $areaTypes = $this->getCachedAreaTypes();
        $areaRange = $this->getCachedAreaRange();

        return view('website.home', compact('mediaList', 'filters', 'sliders', 'otherMedia', 'billboards', 'areaRange', 'areaTypes'));
    }
    /** POST SEARCH - NO PARAMS IN URL */
    public function search(Request $request)
    {
        if ($request->filled('clear')) {

            session()->forget('search_filters');

            $filters = [];
            $mediaList = $this->homeService->searchMedia($filters);
            $mapMedia  = $this->homeService->getMapMarkers($filters);
            // $sizes = $this->homeService->getUniqueSizes();

            $areaRange = $this->getCachedAreaRange();
            $areaTypes = $this->getCachedAreaTypes();

            return view('website.search', compact(
                'mediaList',
                'mapMedia',
                'filters',
                'areaRange',
                'areaTypes'
            ));
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
            'areatype_id',
            'available_days',
            'min_price',   // <- add
            'max_price',   // <- add
            'size_id',   //  FIXED
            'min_area',   //  add
            'max_area',    //  add
            'highway_id',     // Highway filter (single)
            'landmark_ids'    // Landmark filter (multiple, OR logic)
        ]);
        //  SAVE FILTERS IN SESSION
        session(['search_filters' => $filters]);
        $mediaList = $this->homeService->searchMedia($filters);

        // Lazy load POST
        if ($request->ajax()) {
            if ($mediaList->isEmpty()) {
                return '';
            }
            return view('website.media-home-list', compact('mediaList'))->render();
        }
        $areaTypes = $this->getCachedAreaTypes();
        $areaRange = $this->getCachedAreaRange();
        $mapMedia  = $this->homeService->getMapMarkers($filters);

        return view('website.search', compact('mediaList', 'mapMedia', 'filters', 'areaRange', 'areaTypes'));
    }
    public function searchView()
    {
        // Always start fresh on GET /search — don't restore old session filters
        session()->forget('search_filters');
        $filters = [];

        $mediaList = $this->homeService->searchMedia($filters);
        $mapMedia  = $this->homeService->getMapMarkers($filters);

        // $sizes = $this->homeService->getUniqueSizes();
        $areaTypes = $this->getCachedAreaTypes();
        $areaRange = $this->getCachedAreaRange();

        return view('website.search', compact('mediaList', 'mapMedia', 'filters',  'areaRange', 'areaTypes'));
    }
    public function getMediaDetails($mediaId)
    {
        $rawMediaId = $mediaId;

        try {
            $mediaId = base64_decode($mediaId);

            if (!$mediaId || !is_numeric($mediaId)) abort(404);

            $mediaId = (int) $mediaId;

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
        } catch (HttpExceptionInterface $e) {

            // abort(404) — the media simply doesn't exist (or is deleted).
            // Let it render as a real 404 instead of masking it as a redirect.
            throw $e;
        } catch (Throwable $e) {

            Log::error('Media Details Error', [
                'media_id'  => $mediaId,
                'raw_param' => $rawMediaId,
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'file'      => $e->getFile() . ':' . $e->getLine()
            ]);

            return redirect()
                ->route('website.home')
                ->with('error', 'Unable to load media details.');
        }
    }
}

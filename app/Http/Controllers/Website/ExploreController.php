<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Services\Website\ExploreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * New multi-select Explore page (Feature 4) + map synchronisation (Feature 5).
 * Completely separate from HomeController / the existing /search page.
 */
class ExploreController extends Controller
{
    public function __construct(private ExploreService $service) {}

    /**
     * Full Explore page (left = filters + cards, right = map).
     */
    public function index(Request $request)
    {
        $filters = $this->extractFilters($request);

        [$col, $dir] = $this->service->resolveSort($request->input('sort'));
        $filters['_sort_col'] = $col;
        $filters['_sort_dir'] = $dir;

        $mediaList = $this->service->searchMedia($filters, 50, (int) $request->input('page', 1));
        $mapMedia  = $this->service->getMapMarkers($filters);

        $masters = $this->masters();

        // Grand total of all active hoardings (denominator for "showing X / Y").
        $grandTotal = DB::table('media_management')
            ->where('is_active', 1)->where('is_deleted', 0)->count();

        // Dynamic min/max bounds for the Budget + Media Size range sliders.
        $ranges = $this->ranges();

        return view('website.explore', array_merge($masters, [
            'mediaList'   => $mediaList,
            'mapMarkers'  => $this->buildMarkers($mapMedia),
            'filters'     => $request->all(),
            'grandTotal'  => $grandTotal,
            'areaRange'   => $ranges['area'],
            'priceRange'  => $ranges['price'],
        ]));
    }

    /**
     * AJAX search — returns cards HTML + map markers + pagination meta as JSON.
     */
    public function search(Request $request)
    {
        $filters = $this->extractFilters($request);

        [$col, $dir] = $this->service->resolveSort($request->input('sort'));
        $filters['_sort_col'] = $col;
        $filters['_sort_dir'] = $dir;

        $page      = (int) $request->input('page', 1);
        $mediaList = $this->service->searchMedia($filters, 50, $page);
        $mapMedia  = $this->service->getMapMarkers($filters);

        $cardsHtml = view('website.explore-cards', ['mediaList' => $mediaList])->render();

        $markers = $this->buildMarkers($mapMedia);

        return response()->json([
            'cards'        => $cardsHtml,
            'markers'      => $markers,
            'pagination'   => $mediaList->appends($request->except('page'))->links()->toHtml(),
            'total_count'  => $mediaList->total(),
            'current_page' => $mediaList->currentPage(),
            'last_page'    => $mediaList->lastPage(),
            'is_empty'     => $mediaList->isEmpty(),
        ]);
    }

    /**
     * Public AJAX: active landmarks list.
     */
    public function landmarks()
    {
        return response()->json(
            Cache::remember('explore_landmarks', 3600, fn() =>
            DB::table('landmark')->where('is_active', 1)->where('is_deleted', 0)
                ->select('id', 'landmark_name')->orderBy('landmark_name')->get())
        );
    }

    /**
     * Public AJAX: active highways list.
     */
    public function highways()
    {
        return response()->json(
            Cache::remember('explore_highways', 3600, fn() =>
            DB::table('highway')->where('is_active', 1)->where('is_deleted', 0)
                ->select('id', 'highway_name')->orderBy('highway_name')->get())
        );
    }

    /**
     * Accept both single and multi values for every group.
     */
    private function extractFilters(Request $request): array
    {
        return [
            'state_id'     => $request->input('state_id'),
            'district_id'  => $request->input('district_id'),
            'city_id'      => $request->input('city_id'),
            'area_id'      => $request->input('area_id'),
            'category_id'  => $request->input('category_id'),
            'areatype_id'  => $request->input('areatype_id'),
            'highway_id'   => $request->input('highway_id'),
            'landmark_ids' => $request->input('landmark_ids'),
            'min_price'    => $request->input('min_price'),
            'max_price'    => $request->input('max_price'),
            'min_area'     => $request->input('min_area'),
            'max_area'     => $request->input('max_area'),
            'from_date'    => $request->input('from_date'),
            'to_date'      => $request->input('to_date'),
            'q'            => $request->input('q'),
        ];
    }

    /**
     * Shape the map markers payload from a result collection.
     */
    private function buildMarkers($mapMedia): array
    {
        return $mapMedia->map(function ($m) {
            return [
                'id'            => $m->id,
                'eid'           => base64_encode($m->id),
                // Media Title is only mandatory for Hoardings/Billboards, so a
                // mall or transit record falls back to its category name — the
                // same fallback the result cards use.
                'title'         => trim(
                    (($m->media_title ?: $m->category_name) ?? '') . ' ' . ($m->area_name ?? '')
                ),
                'hoarding_code' => $m->hoarding_code,
                'lat'           => (float) $m->latitude,
                'lng'           => (float) $m->longitude,
                'price'         => $m->price,
                'width'         => $m->width,
                'height'        => $m->height,
                'area_name'     => $m->area_name,
                'city_name'     => $m->city_name,
                'highway_name'  => $m->highway_name,
                'landmarks'     => $m->landmark_names,
                'image'         => $m->first_image
                    ? config('fileConstants.IMAGE_VIEW') . $m->first_image
                    : null,
            ];
        })->values()->all();
    }

    /**
     * Dynamic min/max bounds (cached) for the Budget + Media Size sliders.
     */
    private function ranges(): array
    {
        return Cache::remember('explore_ranges', 300, function () {
            $area = DB::table('media_management')
                ->where('is_deleted', 0)->where('is_active', 1)
                ->selectRaw('CAST(MIN(area_auto) AS UNSIGNED) as min_area, CAST(MAX(area_auto) AS UNSIGNED) as max_area')
                ->first();

            $price = DB::table('media_management')
                ->where('is_deleted', 0)->where('is_active', 1)
                ->selectRaw('CAST(MIN(price) AS UNSIGNED) as min_price, CAST(MAX(price) AS UNSIGNED) as max_price')
                ->first();

            // Fallbacks so the sliders are always valid even with no data.
            $area->min_area  = (int) ($area->min_area ?? 0);
            $area->max_area  = (int) max($area->max_area ?? 0, $area->min_area + 1);
            $price->min_price = (int) ($price->min_price ?? 0);
            $price->max_price = (int) max($price->max_price ?? 0, $price->min_price + 1);

            return ['area' => $area, 'price' => $price];
        });
    }

    private function masters(): array
    {
        return [
            'states'     => DB::table('states')->where('is_active', 1)->where('is_deleted', 0)->orderBy('state_name')->get(),
            'districts'  => DB::table('districts')->where('is_active', 1)->where('is_deleted', 0)->orderBy('district_name')->get(),
            'cities'     => DB::table('cities')->where('is_active', 1)->where('is_deleted', 0)->orderBy('city_name')->get(),
            'areas'      => DB::table('areas')->where('is_active', 1)->where('is_deleted', 0)->orderBy('area_name')->get(),
            'categories' => DB::table('category')->where('is_active', 1)->where('is_deleted', 0)->orderBy('category_name')->get(),
            'areaTypes'  => DB::table('areatype')->where('is_active', 1)->where('is_deleted', 0)->orderBy('areatype_name')->get(),
            'highways'   => DB::table('highway')->where('is_active', 1)->where('is_deleted', 0)->orderBy('highway_name')->get(),
            'landmarks'  => DB::table('landmark')->where('is_active', 1)->where('is_deleted', 0)->orderBy('landmark_name')->get(),
        ];
    }
}

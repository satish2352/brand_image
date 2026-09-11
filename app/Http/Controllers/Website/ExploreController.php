<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Services\Website\ExploreService;
use App\Support\MasterCache;
use App\Support\RadiusRange;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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

        // ?embed=1 is the home page hero panel, which shows the map and nothing
        // else. The filter sidebar is not rendered there at all, so the work
        // that exists purely to fill it is skipped: the eight master lists and
        // the first page of results, whose only use on this page is the "showing
        // X" count inside that sidebar. The home page pays for this render on
        // every visit, so it should only pay for the map.
        $embed = $request->boolean('embed');

        $mediaList = $embed
            ? new LengthAwarePaginator([], 0, 50)
            : $this->service->searchMedia($filters, 50, (int) $request->input('page', 1));

        $mapMedia  = $this->service->getMapMarkers($filters);

        $masters = $embed ? $this->emptyMasters() : $this->masters();

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
            // Bounds for the Radius slider — the same ceiling the repository
            // clamps a submitted radius to.
            'radiusMin'   => RadiusRange::MIN,
            'radiusMax'   => RadiusRange::max(),
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
            Cache::remember(MasterCache::EXPLORE_LANDMARKS, MasterCache::TTL, fn() =>
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
            Cache::remember(MasterCache::EXPLORE_HIGHWAYS, MasterCache::TTL, fn() =>
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
            'radius_id'    => $request->input('radius_id'),
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
                // area_auto is a VARCHAR, so MIN()/MAX() compare it as TEXT: the old
                // 'CAST(MIN(area_auto) AS UNSIGNED)' aggregated the strings first and
                // only then cast, which returned min=1122 / max=800 — a backwards
                // range that left the size slider dead. Cast per row, then aggregate.
                ->selectRaw('MIN(CAST(area_auto AS DECIMAL(15,2))) as min_area, MAX(CAST(area_auto AS DECIMAL(15,2))) as max_area')
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

    /**
     * The same shape as masters(), with nothing in it.
     *
     * The embed never renders the filter sidebar, so the view never reads these
     * — but handing it the full set of keys keeps the two paths interchangeable
     * and means a future reference cannot land on an undefined variable.
     *
     * @return array<string,\Illuminate\Support\Collection>
     */
    private function emptyMasters(): array
    {
        return array_fill_keys(
            ['states', 'districts', 'cities', 'areas', 'categories', 'areaTypes', 'highways', 'landmarks'],
            collect()
        );
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

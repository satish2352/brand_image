<?php

namespace App\Http\Controllers\Common;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Support\LocationCache;

class LocationController extends Controller
{
    // Location master data rarely changes, so cache each lookup for an hour to
    // keep these cascade AJAX calls instant and off the (slow, shared) DB.
    // The master repositories evict these keys on write - see LocationCache.
    private const TTL = LocationCache::TTL;

    // Cap for the type-ahead lookups. The searchable dropdowns only ever show a
    // scrolling page of matches, so there is no point shipping more than this.
    private const SEARCH_LIMIT = 50;

    public function getStates()
    {
        return Cache::remember(LocationCache::statesKey(), self::TTL, fn() =>
            DB::table('states')
                ->where(['is_active' => 1, 'is_deleted' => 0])
                ->orderBy('state_name')
                ->get(['id', 'state_name']));
    }

    public function getDistricts(Request $request)
    {
        $stateId = (int) $request->state_id;

        if (($term = $this->searchTerm($request)) !== null) {
            return $this->searchLocations('districts', 'district_name', 'state_id', $stateId, $term);
        }

        return Cache::remember(LocationCache::districtsKey($stateId), self::TTL, fn() =>
            DB::table('districts')
                ->where(['state_id' => $stateId, 'is_active' => 1, 'is_deleted' => 0])
                ->orderBy('district_name')
                ->get(['id', 'district_name']));
    }

    public function getCities(Request $request)
    {
        $districtId = (int) $request->district_id;

        if (($term = $this->searchTerm($request)) !== null) {
            return $this->searchLocations('cities', 'city_name', 'district_id', $districtId, $term);
        }

        return Cache::remember(LocationCache::citiesKey($districtId), self::TTL, fn() =>
            DB::table('cities')
                ->where(['district_id' => $districtId, 'is_active' => 1, 'is_deleted' => 0])
                ->orderBy('city_name')
                ->get(['id', 'city_name']));
    }

    public function getAreas(Request $request)
    {
        $cityId = (int) $request->city_id;
        return Cache::remember(LocationCache::areasKey($cityId), self::TTL, fn() =>
            DB::table('areas')
                ->where(['city_id' => $cityId, 'is_active' => 1, 'is_deleted' => 0])
                ->orderBy('area_name')
                ->get(['id', 'area_name']));
    }

    /**
     * The term typed into a searchable dropdown, or null when the caller just
     * wants the whole (cached) list. An all-whitespace term counts as "no term"
     * so an accidental space still returns the full list.
     */
    private function searchTerm(Request $request): ?string
    {
        $term = trim((string) $request->input('q', ''));

        return $term === '' ? null : $term;
    }

    /**
     * Type-ahead lookup for a searchable cascade dropdown, scoped to the parent
     * that is currently selected.
     *
     * Deliberately not cached: the term space is unbounded, so caching would
     * fill the store with single-use keys and still miss on the next keystroke.
     * A parent-scoped LIKE with a hard limit stays cheap without it.
     */
    private function searchLocations(
        string $table,
        string $nameColumn,
        string $parentColumn,
        int $parentId,
        string $term
    ) {
        // % and _ are LIKE wildcards - escape them so a literal one typed by the
        // user narrows the search instead of matching everything.
        $escaped = addcslashes($term, '\\%_');

        return DB::table($table)
            ->where([$parentColumn => $parentId, 'is_active' => 1, 'is_deleted' => 0])
            ->where($nameColumn, 'like', '%' . $escaped . '%')
            ->orderBy($nameColumn)
            ->limit(self::SEARCH_LIMIT)
            ->get(['id', $nameColumn]);
    }
}

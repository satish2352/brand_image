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
        return Cache::remember(LocationCache::districtsKey($stateId), self::TTL, fn() =>
            DB::table('districts')
                ->where(['state_id' => $stateId, 'is_active' => 1, 'is_deleted' => 0])
                ->orderBy('district_name')
                ->get(['id', 'district_name']));
    }

    public function getCities(Request $request)
    {
        $districtId = (int) $request->district_id;
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
}

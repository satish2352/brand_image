<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Cache keys for the cascading state → district → city → area dropdowns
 * served by App\Http\Controllers\Common\LocationController.
 *
 * The lookups are cached for an hour, so every write to a location master
 * must evict the affected bucket here — otherwise a city that was just
 * deactivated or deleted keeps showing up in the dropdowns until the TTL
 * expires.
 */
class LocationCache
{
    public const TTL = 3600;

    /**
     * Flat "every active row" lists behind the Explore page's filter panel.
     *
     * The keys above are per-parent, because the cascading dropdowns ask for
     * one state's districts at a time. Explore renders the whole country at
     * once, so it needs its own four buckets — evicted by the same methods, so
     * an admin write cannot update one view and leave the other stale.
     */
    public const EXPLORE_STATES    = 'explore_states';
    public const EXPLORE_DISTRICTS = 'explore_districts';
    public const EXPLORE_CITIES    = 'explore_cities';
    public const EXPLORE_AREAS     = 'explore_areas';

    public static function statesKey(): string
    {
        return 'loc_states';
    }

    public static function districtsKey($stateId): string
    {
        return 'loc_districts_' . (int) $stateId;
    }

    public static function citiesKey($districtId): string
    {
        return 'loc_cities_' . (int) $districtId;
    }

    public static function areasKey($cityId): string
    {
        return 'loc_areas_' . (int) $cityId;
    }

    public static function forgetStates(): void
    {
        Cache::forget(self::statesKey());
        Cache::forget(self::EXPLORE_STATES);
    }

    /**
     * Nulls are ignored, so callers can pass an "old" id that may not exist.
     */
    public static function forgetDistricts(...$stateIds): void
    {
        self::forget('districtsKey', $stateIds);
        Cache::forget(self::EXPLORE_DISTRICTS);
    }

    public static function forgetCities(...$districtIds): void
    {
        self::forget('citiesKey', $districtIds);
        Cache::forget(self::EXPLORE_CITIES);
    }

    public static function forgetAreas(...$cityIds): void
    {
        self::forget('areasKey', $cityIds);
        Cache::forget(self::EXPLORE_AREAS);
    }

    private static function forget(string $keyMethod, array $ids): void
    {
        foreach (array_unique(array_filter($ids)) as $id) {
            Cache::forget(self::{$keyMethod}($id));
        }
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Cache keys for the non-location master lists the public site reads -
 * categories, highways, landmarks, radius options and area types.
 *
 * These are cached for an hour so the search form and the Explore page stay
 * cheap, which means every admin write MUST evict the affected bucket here.
 * Without that, a category renamed in the admin panel keeps its old name on the
 * site (and a newly added one never appears) until the TTL happens to expire.
 *
 * The states / districts / cities / areas equivalents live in LocationCache.
 */
class MasterCache
{
    public const TTL = 3600;

    // One master can back several caches - a rename has to clear all of them.
    public const CATEGORIES        = 'search_form_categories';
    public const FIRST_CATEGORY    = 'admin_first_category';
    public const HIGHWAYS          = 'search_form_highways';
    public const EXPLORE_HIGHWAYS  = 'explore_highways';
    public const LANDMARKS         = 'search_form_landmarks';
    public const EXPLORE_LANDMARKS = 'explore_landmarks';
    public const RADIUS            = 'search_form_radius';
    public const AREA_TYPES        = 'home_area_types';

    // Flat lists behind the Explore page's filter panel. Separate from the
    // AJAX ones above because those carry two columns and these carry the
    // whole row, but they go stale on exactly the same writes.
    public const EXPLORE_CATEGORIES = 'explore_categories';
    public const EXPLORE_AREA_TYPES = 'explore_areatypes';
    public const EXPLORE_HIGHWAY_ROWS = 'explore_highway_rows';
    public const EXPLORE_LANDMARK_ROWS = 'explore_landmark_rows';

    public static function forgetCategories(): void
    {
        self::forget(self::CATEGORIES, self::FIRST_CATEGORY, self::EXPLORE_CATEGORIES);
    }

    public static function forgetHighways(): void
    {
        self::forget(self::HIGHWAYS, self::EXPLORE_HIGHWAYS, self::EXPLORE_HIGHWAY_ROWS);
    }

    public static function forgetLandmarks(): void
    {
        self::forget(self::LANDMARKS, self::EXPLORE_LANDMARKS, self::EXPLORE_LANDMARK_ROWS);
    }

    public static function forgetRadius(): void
    {
        self::forget(self::RADIUS);
    }

    public static function forgetAreaTypes(): void
    {
        self::forget(self::AREA_TYPES, self::EXPLORE_AREA_TYPES);
    }

    private static function forget(string ...$keys): void
    {
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}

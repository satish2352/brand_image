<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The radius master (radius_master) behind the search form's Radius slider.
 *
 * The slider runs from 0 km up to the largest radius the admin has configured,
 * and the SAME ceiling is enforced server-side - the slider is just an <input>,
 * so a hand-crafted request must not be able to ask for a 10,000 km radius and
 * quietly turn the city filter off with it.
 *
 * 0 km is not a radius, it is the off switch: the search then falls back to the
 * plain city match, which is what the form shows before the user touches it.
 */
class RadiusRange
{
    public const MIN = 0;

    /** Ceiling used when the master table is empty, so the slider is never 0..0. */
    public const FALLBACK_MAX = 50;

    // Shared with the search-form view composers - one key, one query, so the
    // slider's ceiling can never drift from the options the admin maintains.
    // The key itself lives in MasterCache, which is what the admin's Radius
    // screen evicts on write.
    public static function options()
    {
        return Cache::remember(MasterCache::RADIUS, MasterCache::TTL, fn() =>
            DB::table('radius_master')
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                // `radius` is a varchar, so sort it as a number - otherwise
                // "100" would order before "20".
                ->orderByRaw('CAST(radius AS UNSIGNED)')
                ->get());
    }

    public static function max(): int
    {
        $max = (int) self::options()->max(fn($row) => (int) $row->radius);

        return $max > 0 ? $max : self::FALLBACK_MAX;
    }

    /**
     * A submitted radius pinned to the range the slider actually offers.
     * Anything unparseable, negative or over the ceiling collapses to a usable
     * value, and 0 means "no radius filter".
     */
    public static function clamp($value): float
    {
        if (!is_numeric($value)) {
            return 0.0;
        }

        return (float) min(max((float) $value, self::MIN), self::max());
    }
}

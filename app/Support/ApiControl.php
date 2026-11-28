<?php

namespace App\Support;

use App\Models\ApiUsageLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Super Admin control over the location APIs (API Request Manager page).
 *
 * Every setting falls back to config/services.php (and so .env) until a Super
 * Admin saves the page; saved values then win. apply() runs at boot and writes
 * the saved values into config, so the existing services keep reading config.
 *
 * Services: geoapify (Geoapify Places), tomtom (TomTom Places), tomtom_traffic
 * (TomTom Traffic Flow). TomTom Places and Traffic share one key and one plan,
 * so besides each one's own daily limit there is a shared daily plan limit.
 */
class ApiControl
{
    public const GROUP = 'api_control';

    public const SERVICES = [
        'geoapify'       => 'Geoapify Places',
        'tomtom'         => 'TomTom Places',
        'tomtom_traffic' => 'TomTom Traffic',
    ];

    /**
     * Free plan per day, for display. Geoapify counts credits (1 credit per 20
     * places); TomTom counts requests, and Places + Traffic share the 2,500.
     */
    public const FREE_LIMITS = [
        'geoapify'       => ['limit' => 3000, 'unit' => 'credits', 'note' => '1 credit = up to 20 places'],
        'tomtom'         => ['limit' => 2500, 'unit' => 'requests', 'note' => 'shared with TomTom Traffic'],
        'tomtom_traffic' => ['limit' => 2500, 'unit' => 'requests', 'note' => 'shared with TomTom Places'],
    ];

    /** Every hoarding is refreshed once in this many days unless the page says otherwise. */
    public const REFRESH_DAYS = 8;

    /**
     * Auto refresh days keep this share of each daily limit free for page views and
     * Super Admin runs; the hourly runs use the rest.
     */
    public const AUTO_RESERVE = 0.2;

    /** Auto refreshes each hoarding at most this often unless the page says otherwise (once a week). */
    public const AUTO_MIN_DAYS = 7;

    /** Places asked from Geoapify per hoarding; every 20 cost 1 credit. */
    public const PLACES_CHOICES = [20, 40, 60, 80, 100];

    /** Saved values, falling back to config / .env. */
    public static function settings(): array
    {
        return [
            'places_provider'      => in_array($p = (string) Setting::get('api_places_provider', config('services.insights.places_provider', 'geoapify')), ['geoapify', 'tomtom'], true) ? $p : 'geoapify',
            'geoapify_enabled'     => Setting::getBool('api_geoapify_enabled', true),
            'tomtom_enabled'       => Setting::getBool('api_tomtom_enabled', true),
            'tomtom_traffic_enabled' => Setting::getBool('api_tomtom_traffic_enabled', true),
            'geoapify_daily_limit' => self::int('api_geoapify_daily_limit', (int) config('services.geoapify.daily_credit_limit', 2500)),
            'tomtom_daily_limit'   => self::int('api_tomtom_daily_limit', (int) config('services.tomtom.daily_request_limit', 2000)),
            'tomtom_traffic_daily_limit' => self::int('api_tomtom_traffic_daily_limit', (int) config('services.tomtom.traffic_daily_limit', 2000)),
            'tomtom_plan_daily_limit' => self::int('api_tomtom_plan_daily_limit', (int) config('services.tomtom.plan_daily_limit', 2500)),
            'places_auto'          => Setting::getBool('api_places_auto', true),
            'traffic_auto'         => Setting::getBool('api_traffic_auto', true),
            'fetch_on_view'        => Setting::getBool('api_fetch_on_view', (bool) config('services.insights.fetch_on_view', true)),
            'places_cache_days'    => self::int('api_places_cache_days', self::REFRESH_DAYS, 1),
            'traffic_refresh_days' => self::int('api_traffic_refresh_days', self::REFRESH_DAYS, 1),
            'geoapify_places'      => in_array($n = self::int('api_geoapify_places', (int) config('services.geoapify.limit', 60), 1), self::PLACES_CHOICES, true) ? $n : 60,
            'spread_evenly'        => Setting::getBool('api_spread_evenly', true),
            // Auto: refresh days follow the number of active hoardings (see autoCycle()).
            'auto_cycle'           => Setting::getBool('api_auto_cycle', true),
            // Auto never refreshes more often than this (a week by default), even when the free
            // limits would allow it; with many hoardings the worked-out cycle is longer anyway.
            'auto_min_days'        => self::int('api_auto_min_days', self::AUTO_MIN_DAYS, 1),
        ];
    }

    /** Write the saved values into config. Safe before migrations (falls back silently). */
    public static function apply(): void
    {
        try {
            $s = self::settings();
        } catch (Throwable) {
            return;
        }

        // Auto: as hoardings are added (15,000 this month, 20,000 next), the refresh days grow so the
        // hourly runs keep fitting inside the free plans. The hand-set days apply when Auto is off.
        if ($s['auto_cycle']) {
            try {
                $auto = self::autoCycle($s);
                $s['places_cache_days'] = $auto['places_days'];
                $s['traffic_refresh_days'] = $auto['traffic_days'];
            } catch (Throwable) {
                // no media table yet: keep the saved days
            }
        }

        config([
            'services.insights.places_provider'   => $s['places_provider'],
            'services.insights.fetch_on_view'     => $s['fetch_on_view'],
            'services.geoapify.daily_credit_limit' => $s['geoapify_daily_limit'],
            'services.geoapify.cache_days'        => $s['places_cache_days'],
            'services.geoapify.limit'             => $s['geoapify_places'],
            'services.tomtom.daily_request_limit' => $s['tomtom_daily_limit'],
            'services.tomtom.cache_days'          => $s['places_cache_days'],
            'services.tomtom.traffic_daily_limit' => $s['tomtom_traffic_daily_limit'],
            'services.tomtom.traffic_refresh_days' => $s['traffic_refresh_days'],
            'services.tomtom.plan_daily_limit'    => $s['tomtom_plan_daily_limit'],
            'services.api_control.enabled'        => [
                'geoapify'       => $s['geoapify_enabled'],
                'tomtom'         => $s['tomtom_enabled'],
                'tomtom_traffic' => $s['tomtom_traffic_enabled'],
            ],
            'services.api_control.places_auto'    => $s['places_auto'],
            'services.api_control.traffic_auto'   => $s['traffic_auto'],
            'services.api_control.spread_evenly'  => $s['spread_evenly'],
        ]);
    }

    public static function save(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::put('api_' . $key, is_bool($value) ? (int) $value : $value, self::GROUP);
        }
        self::apply();
    }

    /** May this service send requests at all? */
    public static function enabled(string $service): bool
    {
        return (bool) (config('services.api_control.enabled.' . $service) ?? true);
    }

    public static function autoRun(string $kind): bool
    {
        return (bool) (config('services.api_control.' . $kind . '_auto') ?? true);
    }

    /** Requests (or credits) logged today for one service. */
    public static function usedToday(string $service): int
    {
        return (int) ApiUsageLog::where('provider', $service)->where('requested_at', '>=', now()->startOfDay())->sum('credits_consumed');
    }

    /** TomTom Places + Traffic today against the shared plan. */
    public static function tomTomShared(): array
    {
        $limit = max(0, (int) config('services.tomtom.plan_daily_limit', 2500));
        $used = self::usedToday('tomtom') + self::usedToday('tomtom_traffic');

        return ['limit' => $limit, 'used' => $used, 'remaining' => max(0, $limit - $used)];
    }

    /** Active hoardings with a usable location: the ones the hourly runs keep fresh. */
    public static function activeHoardings(): int
    {
        return DB::table('media_management')->where('is_deleted', 0)->where('is_active', 1)
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->where('latitude', '!=', 0)->where('longitude', '!=', 0)
            ->count();
    }

    /**
     * Spread evenly: hoardings the hourly run may still fetch today, so that
     * every hoarding is refreshed once per refresh period and each day carries
     * about the same load (hoardings / days, plus 10% to catch up after a bad
     * day). Page views and Super Admin runs are not limited by this.
     * null = not spreading (only the daily limits apply).
     *
     * @param  string  $kind  places|traffic
     */
    public static function scheduleRoom(string $kind): ?int
    {
        if (!(bool) (config('services.api_control.spread_evenly') ?? true)) {
            return null;
        }
        $days = $kind === 'traffic'
            ? max(1, (int) config('services.tomtom.traffic_refresh_days', self::REFRESH_DAYS))
            : max(1, (int) config('services.geoapify.cache_days', self::REFRESH_DAYS));
        $perDay = (int) ceil(self::activeHoardings() / $days * 1.1);
        $provider = $kind === 'traffic' ? 'tomtom_traffic' : (string) config('services.insights.places_provider', 'geoapify');
        $done = ApiUsageLog::where('provider', $provider)->where('source', 'scheduler')
            ->where('requested_at', '>=', now()->startOfDay())->where('credits_consumed', '>', 0)->count();

        return max(0, $perDay - $done);
    }

    /** Active hoardings, counted at most once an hour (apply() runs on every request). */
    public static function cachedHoardings(): int
    {
        return (int) Cache::remember('api_control.active_hoardings', 3600, fn () => self::activeHoardings());
    }

    /**
     * Auto refresh days: the shortest cycle in which the hourly runs can refresh every active
     * hoarding without passing the daily limits (each capped at its free plan), keeping
     * AUTO_RESERVE of each limit free. The 1.1 matches scheduleRoom()'s 10% catch-up.
     *
     * @return array{hoardings:int,places_days:int,traffic_days:int,places_per_day:int,traffic_per_day:int,places_units:int,places_unit:string,places_budget:int,traffic_budget:int}
     */
    public static function autoCycle(array $s, ?int $hoardings = null): array
    {
        $h   = max(0, $hoardings ?? self::cachedHoardings());
        $use = 1 - self::AUTO_RESERVE;
        $plan = min($s['tomtom_plan_daily_limit'], self::FREE_LIMITS['tomtom']['limit']);

        if ($s['places_provider'] === 'geoapify') {
            $cost = max(1, (int) ceil($s['geoapify_places'] / 20));
            $placesBudget = (int) floor(min($s['geoapify_daily_limit'], self::FREE_LIMITS['geoapify']['limit']) * $use); // credits
            $trafficBudget = (int) floor(min($s['tomtom_traffic_daily_limit'], $plan) * $use);
            $unit = 'credits';
        } else {
            $cost = 1;
            $placesBudget = (int) floor(min($s['tomtom_daily_limit'], intdiv($plan, 2)) * $use); // TomTom Places + Traffic share the plan
            $trafficBudget = (int) floor(min($s['tomtom_traffic_daily_limit'], intdiv($plan, 2)) * $use);
            $unit = 'requests';
        }

        $minDays = max(1, (int) ($s['auto_min_days'] ?? 1));
        $days = fn (int $hoardingsPerDayBudget) => $hoardingsPerDayBudget > 0
            ? min(365, max($minDays, (int) ceil($h * 1.1 / $hoardingsPerDayBudget)))
            : 365;
        $placesDays  = $days(intdiv($placesBudget, $cost));
        $trafficDays = $days($trafficBudget);

        return [
            'hoardings'       => $h,
            'places_days'     => $placesDays,
            'traffic_days'    => $trafficDays,
            'places_per_day'  => (int) ceil($h / $placesDays),
            'traffic_per_day' => (int) ceil($h / $trafficDays),
            'places_units'    => (int) ceil($h / $placesDays) * $cost,
            'places_unit'     => $unit,
            'places_budget'   => $placesBudget,
            'traffic_budget'  => $trafficBudget,
            // false: even a 365-day cycle needs more than the daily limit allows (raise the limit)
            'places_fits'     => (int) ceil($h / $placesDays) * $cost <= $placesBudget,
            'traffic_fits'    => (int) ceil($h / $trafficDays) <= $trafficBudget,
        ];
    }

    /**
     * Planner: what N hoardings refreshed every D days need per day, against
     * the free plans. Worst case: every Geoapify answer is full.
     */
    public static function plan(int $hoardings, int $days, int $places, string $placesFrom): array
    {
        $perDay = (int) ceil($hoardings / max(1, $days));
        $cost = (int) ceil($places / 20);
        $geoFree = self::FREE_LIMITS['geoapify']['limit'];
        $ttFree = self::FREE_LIMITS['tomtom']['limit'];
        $credits = $placesFrom === 'geoapify' ? $perDay * $cost : 0;
        $tomtom = $perDay * ($placesFrom === 'tomtom' ? 2 : 1);
        // Most hoardings a day that keep both inside the free plans.
        $maxPerDay = min($placesFrom === 'geoapify' ? intdiv($geoFree, $cost) : PHP_INT_MAX, $placesFrom === 'tomtom' ? intdiv($ttFree, 2) : $ttFree);

        return [
            'per_day'          => $perDay,
            'geoapify_credits' => $credits,
            'tomtom_requests'  => $tomtom,
            'geoapify_ok'      => $credits <= $geoFree,
            'tomtom_ok'        => $tomtom <= $ttFree,
            'min_days'         => max(1, (int) ceil($hoardings / max(1, $maxPerDay))),
        ];
    }

    private static function int(string $key, int $default, int $min = 0): int
    {
        $v = Setting::get($key);

        return is_numeric($v) && (int) $v >= $min ? (int) $v : max($min, $default);
    }
}

<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use App\Http\Services\Insights\GeoapifyPlacesService;
use App\Http\Services\Insights\HoardingTrafficService;
use App\Http\Services\Insights\MediaInsightsService;
use App\Http\Services\Insights\TomTomPlacesService;
use App\Models\ApiUsageLog;
use App\Support\ApiControl;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * API Request Manager (Super Admin): Geoapify Places, TomTom Places and TomTom
 * Traffic — today's requests against each limit, on/off switches and daily
 * limits, small "run now" batches, a one-media refresh, a 14-day chart and the
 * request log. Every request still goes through the same services, so the
 * limits and switches below hold for the scheduler, the website and this page.
 */
class InsightsUsageController extends Controller
{
    /** Most hoardings one "run now" press may send, so the page answers within a minute. */
    private const RUN_MAX = 25;

    public function __construct(
        private MediaInsightsService $insights,
        private HoardingTrafficService $traffic,
    ) {}

    public function index(Request $request, GeoapifyPlacesService $geoapify, TomTomPlacesService $tomTom)
    {
        $settings = ApiControl::settings();
        $today = now()->startOfDay();
        $byStatus = ApiUsageLog::where('requested_at', '>=', $today)
            ->select('provider', 'status', DB::raw('COUNT(*) as n'))->groupBy('provider', 'status')->get()
            ->groupBy('provider')->map(fn ($g) => $g->pluck('n', 'status'));
        $last = ApiUsageLog::select('provider', DB::raw('MAX(requested_at) as at'))->groupBy('provider')->pluck('at', 'provider');

        $card = function (string $key, array $q, int $due) use ($byStatus, $last) {
            $s = $byStatus[$key] ?? collect();
            return $q + [
                'key'      => $key,
                'name'     => ApiControl::SERVICES[$key],
                'due'      => $due,
                'success'  => (int) ($s[ApiUsageLog::SUCCESS] ?? 0),
                'failed'   => (int) ($s[ApiUsageLog::FAILED] ?? 0) + (int) ($s[ApiUsageLog::QUOTA_EXCEEDED] ?? 0),
                'saved'    => (int) ($s[ApiUsageLog::CACHE_HIT] ?? 0),
                'blocked'  => (int) ($s[ApiUsageLog::QUOTA_BLOCKED] ?? 0) + (int) ($s[ApiUsageLog::DISABLED] ?? 0),
                'last_at'  => isset($last[$key]) ? Carbon::parse($last[$key]) : null,
            ];
        };

        $active = $this->insights->provider()->name();
        $placesDue = $this->insights->dueCount();
        $cards = [
            $card('geoapify', $this->insights->quota($geoapify), $active === 'geoapify' ? $placesDue : 0),
            $card('tomtom', $this->insights->quota($tomTom), $active === 'tomtom' ? $placesDue : 0),
            $card('tomtom_traffic', [
                'used'       => $this->traffic->usedToday(),
                'limit'      => $this->traffic->dailyLimit(),
                'remaining'  => $this->traffic->remainingToday(),
                'configured' => $this->traffic->isConfigured(),
                'enabled'    => ApiControl::enabled('tomtom_traffic'),
                'paused'     => false,
                'period'     => 'day',
                'max_cost'   => 1,
            ], $this->traffic->dueCount()),
        ];

        // Requests per service per day, last 14 days.
        $from = now()->subDays(13)->startOfDay();
        $rows = ApiUsageLog::where('requested_at', '>=', $from)->where('credits_consumed', '>', 0)
            ->select(DB::raw('DATE(requested_at) as d'), 'provider', DB::raw('SUM(credits_consumed) as n'))
            ->groupBy('d', 'provider')->get();
        $days = collect(range(13, 0))->map(fn ($i) => now()->subDays($i)->toDateString());
        $chart = ['labels' => $days->map(fn ($d) => Carbon::parse($d)->format('d M'))->all()];
        foreach (array_keys(ApiControl::SERVICES) as $k) {
            $chart[$k] = $days->map(fn ($d) => (int) optional($rows->first(fn ($r) => $r->d === $d && $r->provider === $k))->n)->all();
        }

        // Request log, with filters.
        $f = $request->only(['service', 'outcome', 'source', 'from', 'to', 'media']);
        $mediaIds = !empty($f['media']) ? $this->findMediaIds($f['media']) : null;
        $perPage = in_array((int) $request->per_page, [10, 25, 50, 100], true) ? (int) $request->per_page : 25;
        $logs = ApiUsageLog::query()
            ->whereIn('provider', array_keys(ApiControl::SERVICES))
            ->when(!empty($f['service']), fn ($q) => $q->where('provider', $f['service']))
            ->when(!empty($f['outcome']), fn ($q) => $q->where('status', $f['outcome']))
            ->when(!empty($f['source']), fn ($q) => $q->where('source', $f['source']))
            ->when(!empty($f['from']), fn ($q) => $q->where('requested_at', '>=', Carbon::parse($f['from'])->startOfDay()))
            ->when(!empty($f['to']), fn ($q) => $q->where('requested_at', '<=', Carbon::parse($f['to'])->endOfDay()))
            ->when($mediaIds !== null, fn ($q) => $q->whereIn('media_id', $mediaIds ?: [0]))
            ->orderByDesc('requested_at')->orderByDesc('id')
            ->paginate($perPage)->withQueryString();
        $codes = DB::table('media_management')->whereIn('id', $logs->pluck('media_id')->filter()->unique())
            ->pluck(DB::raw('COALESCE(NULLIF(hoarding_code, ""), media_code)'), 'id');

        return view('superadm.insights.usage', [
            'settings' => $settings,
            'cards'    => $cards,
            'shared'   => ApiControl::tomTomShared(),
            'active'   => $active,
            'chart'    => $chart,
            'logs'     => $logs,
            'codes'    => $codes,
            'filters'  => $f,
            'perPage'  => $perPage,
            'sources'  => ApiUsageLog::whereIn('provider', array_keys(ApiControl::SERVICES))->distinct()->orderBy('source')->pluck('source')->filter()->values(),
            'outcomes' => [
                ApiUsageLog::SUCCESS => 'Success', ApiUsageLog::CACHE_HIT => 'Saved data used', ApiUsageLog::FAILED => 'Failed',
                ApiUsageLog::QUOTA_BLOCKED => 'Stopped by our limit', ApiUsageLog::QUOTA_EXCEEDED => 'Refused by provider (429)',
                ApiUsageLog::DISABLED => 'Turned off', ApiUsageLog::NOT_CONFIGURED => 'Key not set', ApiUsageLog::MISSING_LOCATION => 'No location',
            ],
            'coverage' => [
                'media'    => DB::table('media_management')->where('is_deleted', 0)->where('is_active', 1)->count(),
                'places'   => DB::table('media_insights')->whereNotNull('places_fetched_at')->count(),
                'traffic'  => DB::table('hoarding_traffic_data')->whereNotNull('traffic_updated_at')->count(),
            ],
            'runMax'   => self::RUN_MAX,
            'located'  => $located = ApiControl::activeHoardings(),
            'plan'     => [
                'now'     => ApiControl::plan($located, $settings['traffic_refresh_days'], $settings['geoapify_places'], $settings['places_provider']),
                'room'    => ['places' => ApiControl::scheduleRoom('places'), 'traffic' => ApiControl::scheduleRoom('traffic')],
                'free'    => ApiControl::FREE_LIMITS,
                'choices' => ApiControl::PLACES_CHOICES,
            ],
            'updated'  => \App\Models\Setting::where('group', ApiControl::GROUP)->max('updated_at'),
        ]);
    }

    public function save(Request $request)
    {
        $max = 1000000;
        $data = $request->validate([
            'places_provider'            => 'required|in:geoapify,tomtom',
            'geoapify_daily_limit'       => "required|integer|min:0|max:$max",
            'tomtom_daily_limit'         => "required|integer|min:0|max:$max",
            'tomtom_traffic_daily_limit' => "required|integer|min:0|max:$max",
            'tomtom_plan_daily_limit'    => "required|integer|min:0|max:$max",
            'places_cache_days'          => 'required|integer|min:1|max:365',
            'traffic_refresh_days'       => 'required|integer|min:1|max:365',
            'auto_min_days'              => 'nullable|integer|min:1|max:90',
            'geoapify_places'            => 'required|integer|in:' . implode(',', ApiControl::PLACES_CHOICES),
        ], [], [
            'geoapify_daily_limit' => 'Geoapify daily limit', 'tomtom_daily_limit' => 'TomTom Places daily limit',
            'tomtom_traffic_daily_limit' => 'TomTom Traffic daily limit', 'tomtom_plan_daily_limit' => 'TomTom plan per day',
            'places_cache_days' => 'reuse places for', 'traffic_refresh_days' => 'refresh traffic every',
            'auto_min_days' => 'refresh at most every',
        ]);
        $data['auto_min_days'] = (int) ($data['auto_min_days'] ?? ApiControl::AUTO_MIN_DAYS);
        foreach (['geoapify_enabled', 'tomtom_enabled', 'tomtom_traffic_enabled', 'places_auto', 'traffic_auto', 'fetch_on_view', 'spread_evenly', 'auto_cycle'] as $k) {
            $data[$k] = $request->boolean($k);
        }

        ApiControl::save($data);
        Log::info('API Request Manager settings changed', ['admin' => session('user_id'), 'settings' => $data]);

        return redirect()->route('media.insights.usage')->with('success', 'API settings saved. They apply to the next request.');
    }

    /** Fetch nearby places for hoardings that are due, now (small batch, active provider). */
    public function runPlaces(Request $request)
    {
        $n = (int) $request->validate(['count' => 'required|integer|min:1|max:' . self::RUN_MAX])['count'];
        $quota = $this->insights->quota();
        if (!$quota['enabled']) {
            return back()->with('error', ApiControl::SERVICES[$quota['provider']] . ' is turned off. Turn it on first.');
        }
        set_time_limit(120);

        $ids = $this->insights->dueForRefresh(min($n, intdiv($quota['remaining'], max(1, $quota['max_cost']))));
        if (!$ids) {
            return back()->with('success', $quota['remaining'] < $quota['max_cost'] ? 'Today\'s limit is reached; nothing was sent.' : 'No hoarding is due for nearby places.');
        }
        $tally = [];
        foreach ($ids as $id) {
            $r = $this->insights->refresh($id, session('user_id'), 'admin_batch');
            $tally[$r['status']] = ($tally[$r['status']] ?? 0) + 1;
            if (in_array($r['status'], [ApiUsageLog::QUOTA_BLOCKED, ApiUsageLog::QUOTA_EXCEEDED, ApiUsageLog::DISABLED], true)) {
                break;
            }
        }

        return back()->with('success', 'Nearby places: ' . $this->summary($tally) . '.');
    }

    /** Fetch TomTom traffic for hoardings that are due, now (small batch). */
    public function runTraffic(Request $request)
    {
        $n = (int) $request->validate(['count' => 'required|integer|min:1|max:' . self::RUN_MAX])['count'];
        if (!ApiControl::enabled('tomtom_traffic')) {
            return back()->with('error', 'TomTom Traffic is turned off. Turn it on first.');
        }
        set_time_limit(120);

        $ids = $this->traffic->dueQuery()->orderBy('m.id')->limit(min($n, $this->traffic->remainingToday()))->pluck('m.id');
        if ($ids->isEmpty()) {
            return back()->with('success', $this->traffic->remainingToday() <= 0 ? 'Today\'s limit is reached; nothing was sent.' : 'No hoarding is due for traffic.');
        }
        $tally = [];
        foreach ($ids as $i => $id) {
            if ($i) {
                usleep($this->traffic->requestDelayMs() * 1000);
            }
            $r = $this->traffic->fetchAndStore((int) $id, 'admin_batch', session('user_id'));
            $tally[$r['status']] = ($tally[$r['status']] ?? 0) + 1;
            if (in_array($r['status'], ['disabled', 'limit'], true) || $r['http_status'] === 429) {
                break;
            }
        }

        return back()->with('success', 'Traffic: ' . $this->summary($tally) . '.');
    }

    /** Refresh one hoarding (by code or id): nearby places and traffic. */
    public function runOne(Request $request)
    {
        $data = $request->validate(['media' => 'required|string|max:50', 'what' => 'required|in:places,traffic,both']);
        $ids = $this->findMediaIds($data['media']);
        if (count($ids) !== 1) {
            return back()->withInput()->with('error', $ids ? 'More than one media matches "' . $data['media'] . '". Use the full code.' : 'No media found for "' . $data['media'] . '".');
        }
        $id = $ids[0];
        $out = [];
        if ($data['what'] !== 'traffic') {
            $out[] = 'Places: ' . $this->insights->refresh($id, session('user_id'), 'admin')['message'];
        }
        if ($data['what'] !== 'places') {
            $out[] = 'Traffic: ' . $this->traffic->fetchAndStore($id, 'admin', session('user_id'))['message'];
        }

        return back()->with('success', $data['media'] . ' — ' . implode(' ', $out));
    }

    /** Lift the few-minute pause that follows a 429 from Geoapify or TomTom Places. */
    public function clearPause(Request $request, GeoapifyPlacesService $geoapify, TomTomPlacesService $tomTom)
    {
        $this->insights->clearPause($request->input('service') === 'tomtom' ? $tomTom : $geoapify);

        return back()->with('success', 'Pause lifted. The next request goes to the provider.');
    }

    private function findMediaIds(string $term): array
    {
        $term = trim($term);

        return DB::table('media_management')->where('is_deleted', 0)
            ->where(fn ($q) => $q->where('hoarding_code', $term)->orWhere('media_code', $term)->when(ctype_digit($term), fn ($w) => $w->orWhere('id', (int) $term)))
            ->limit(5)->pluck('id')->map(fn ($i) => (int) $i)->all();
    }

    private function summary(array $tally): string
    {
        $label = [ApiUsageLog::SUCCESS => 'updated', ApiUsageLog::CACHE_HIT => 'used saved data', ApiUsageLog::FAILED => 'failed',
            ApiUsageLog::QUOTA_BLOCKED => 'stopped by the limit', ApiUsageLog::QUOTA_EXCEEDED => 'refused by the provider', ApiUsageLog::DISABLED => 'turned off',
            ApiUsageLog::MISSING_LOCATION => 'no location', 'limit' => 'stopped by the limit', 'disabled' => 'turned off', 'invalid_location' => 'no location'];

        return collect($tally)->map(fn ($n, $s) => $n . ' ' . ($label[$s] ?? str_replace('_', ' ', $s)))->implode(', ');
    }
}

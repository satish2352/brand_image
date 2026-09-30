<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use App\Http\Services\Insights\GeoapifyPlacesService;
use App\Http\Services\Insights\MediaInsightsService;
use App\Http\Services\Insights\SerpApiService;
use App\Models\ApiUsageLog;
use Illuminate\Support\Facades\DB;

/**
 * Admin panel: nearby-places API usage (Geoapify and SerpApi) against the
 * configured budgets, coverage of the hoarding inventory, and the request log.
 * Read-only — counters are never reset or edited here.
 */
class InsightsUsageController extends Controller
{
    public function __construct(private MediaInsightsService $insights) {}

    public function index(GeoapifyPlacesService $geoapify, SerpApiService $serpApi)
    {
        $active = $this->insights->provider()->name();

        $providers = [
            'geoapify' => $this->insights->quota($geoapify),
            'serpapi'  => $this->insights->quota($serpApi) + ['account' => $serpApi->accountUsage()],
        ];

        $today = ApiUsageLog::where('requested_at', '>=', now()->startOfDay())
            ->select('provider', 'status', DB::raw('COUNT(*) as requests'), DB::raw('SUM(credits_consumed) as credits'))
            ->groupBy('provider', 'status')
            ->orderBy('provider')->orderBy('status')
            ->get();

        $coverage = [
            'media'      => DB::table('media_management')->where('is_deleted', 0)->where('is_active', 1)->count(),
            'enriched'   => DB::table('media_insights')->whereNotNull('places_fetched_at')->count(),
            'due'        => $this->insights->dueCount(),
            'locations'  => DB::table('place_results')->count(),
        ];

        $logs = ApiUsageLog::orderByDesc('requested_at')->orderByDesc('id')->paginate(25);

        return view('superadm.insights.usage', compact('active', 'providers', 'today', 'coverage', 'logs'));
    }
}

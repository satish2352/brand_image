<?php

namespace App\Http\Controllers\Superadm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Service\Superadm\DashboardService;
use App\Models\{
    Area,
    MediaManagement,
    Category,
    FacingDirection,
    Illumination,
    ContactUs,
    Order,
    City,
    Vendor
};
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardController extends Controller
{
    function __construct()
    {
        // $this->service=new DashboardService();
    }

    public function index(Request $req)
    {
        try {

            // Master data counts — cached 5 min (rarely change)
            $masterCounts = Cache::remember('dashboard_master_counts', 300, function () {
                return [
                    'allArea'            => Area::where('is_deleted', 0)->count(),
                    'allcity'            => City::where('is_deleted', 0)->count(),
                    'allvendor'          => Vendor::where('is_deleted', 0)->count(),
                    'allMediaManagement' => MediaManagement::where('is_deleted', 0)->count(),
                    'allCategory'        => Category::where('is_deleted', 0)->count(),
                    'allFacingDirection' => FacingDirection::where('is_deleted', 0)->count(),
                    'allIllumination'    => Illumination::where('is_deleted', 0)->count(),
                    'categoryMediaCounts' => Category::leftJoin(
                        'media_management as m',
                        fn($j) => $j->on('m.category_id', '=', 'category.id')->where('m.is_deleted', 0)
                    )
                        ->where('category.is_deleted', 0)
                        ->select('category.id', 'category.category_name', DB::raw('COUNT(m.id) as media_count'))
                        ->groupBy('category.id', 'category.category_name')
                        ->orderBy('category.category_name')
                        ->get(),
                ];
            });

            $allArea             = $masterCounts['allArea'];
            $allcity             = $masterCounts['allcity'];
            $allvendor           = $masterCounts['allvendor'];
            $allMediaManagement  = $masterCounts['allMediaManagement'];
            $allCategory         = $masterCounts['allCategory'];
            $allFacingDirection  = $masterCounts['allFacingDirection'];
            $allIllumination     = $masterCounts['allIllumination'];
            $categoryMediaCounts = $masterCounts['categoryMediaCounts'];

            // Live stats — cached 2 min (change more often)
            $liveStats = Cache::remember('dashboard_live_stats', 120, function () {
                $now = Carbon::now();
                return [
                    'latestContactCount' => ContactUs::where('created_at', '>=', $now->copy()->subDays(15))->count(),
                    'latestBookingCount' => Order::where('created_at', '>=', $now->copy()->subDays(15))->count(),
                    'monthlyRevenue'     => Order::where('payment_status', 'PAID')
                        ->whereYear('created_at', $now->year)
                        ->whereMonth('created_at', $now->month)
                        ->sum('grand_total'),
                    'yearlyRevenue'      => Order::where('payment_status', 'PAID')
                        ->whereYear('created_at', $now->year)
                        ->sum('grand_total'),
                    'ongoingCampaignCount' => DB::table('campaign as c')
                        ->join('cart_items as ci', 'ci.campaign_id', '=', 'c.id')
                        ->where('ci.cart_type', 'CAMPAIGN')
                        ->whereDate('ci.to_date', '>=', Carbon::today())
                        ->distinct('c.id')
                        ->count('c.id'),
                ];
            });

            $latestContactCount  = $liveStats['latestContactCount'];
            $latestBookingCount  = $liveStats['latestBookingCount'];
            $monthlyRevenue      = $liveStats['monthlyRevenue'];
            $yearlyRevenue       = $liveStats['yearlyRevenue'];
            $ongoingCampaignCount = $liveStats['ongoingCampaignCount'];


            return view('dashboard.dashboard', compact(
                'allArea',
                'allcity',
                'allvendor',
                'allMediaManagement',
                'allCategory',
                'allFacingDirection',
                'allIllumination',
                'latestContactCount',
                'latestBookingCount',
                'monthlyRevenue',
                'yearlyRevenue',
                'categoryMediaCounts',
                'ongoingCampaignCount'

            ));
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('DashboardController@index error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Show friendly error message
            return back()->with('error', 'Something went wrong while loading the dashboard. Please try again later.');
        }
    }

    public function markNotificationsRead()
    {
        $adminId = session('user_id') ?? session('id');

        if (!$adminId) {
            return response()->json(['status' => 'not_logged_in'], 401);
        }

        \App\Models\Notification::where('user_id', $adminId)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return response()->json(['status' => 'success']);
    }
}

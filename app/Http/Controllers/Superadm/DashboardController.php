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
};
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

            $allSessions = $req->session()->all();
            $allArea = Area::where('is_deleted', 0)->count();
            $allMediaManagement = MediaManagement::where('is_deleted', 0)->count();
            $allCategory = Category::where('is_deleted', 0)->count();
            $allFacingDirection = FacingDirection::where('is_deleted', 0)->count();
            $allIllumination = Illumination::where('is_deleted', 0)->count();

            $latestContactCount = ContactUs::where(
                'created_at',
                '>=',
                Carbon::now()->subDays(15)
            )->count();

            $latestBookingCount = Order::where(
                'created_at',
                '>=',
                Carbon::now()->subDays(15)
            )->count();

            // THIS MONTH revenue (paid only)
            $monthlyRevenue = Order::where('payment_status', 'PAID')
                ->whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', Carbon::now()->month)
                ->sum('grand_total');

            // THIS YEAR revenue (paid only)
            $yearlyRevenue = Order::where('payment_status', 'PAID')
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('grand_total');

            $categoryMediaCounts = Category::leftJoin(
                'media_management as m',
                function ($join) {
                    $join->on('m.category_id', '=', 'category.id')
                        ->where('m.is_deleted', 0);
                }
            )
                ->where('category.is_deleted', 0)
                ->select(
                    'category.id',
                    'category.category_name',
                    DB::raw('COUNT(m.id) as media_count')
                )
                ->groupBy('category.id', 'category.category_name')
                ->orderBy('category.category_name')
                ->get();

            $today = Carbon::today();

            /*
                 Ongoing Campaign Count
                ------------------------
                 Condition:
                   to_date >= today
                */
            $ongoingCampaignCount = DB::table('campaign as c')
                ->join('cart_items as ci', 'ci.campaign_id', '=', 'c.id')
                ->where('ci.cart_type', 'CAMPAIGN')
                // ->where('ci.status', 'ACTIVE')
                ->whereDate('ci.to_date', '>=', $today)
                ->distinct('c.id')
                ->count('c.id');


            return view('dashboard.dashboard', compact(
                'allArea',
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

        $admin = \App\Models\User::find($adminId);

        // Mark unread notifications as read
        $admin->unreadNotifications->each(function ($notification) {
            $notification->update(['read_at' => now()]);
        });

        return response()->json(['status' => 'success']);
    }
}

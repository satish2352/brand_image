<?php

namespace App\Http\Controllers\Superadm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Service\Superadm\DashboardService;
use App\Models\{
    Area,
    MediaManagement,
    Category,
    Employees,
    FacingDirection,
    Illumination,
    EmployeeType,
    FinancialYear,
    EmployeePlantAssignment,
    ContactUs,
    Order,
    OrderItem
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
            if ($req->session()->get('role_id') == 0) {
                $allSessions = $req->session()->all();
                // dd($allSessions);

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
            } else {
                $projects = Employees::leftJoin('projects', function ($join) {
                    $join->on(DB::raw("FIND_IN_SET(projects.id, employees.projects_id)"), ">", DB::raw("0"));
                })
                    ->where('employees.is_deleted', 0)
                    ->where('employees.id', session('user_id'))
                    ->select(
                        'employees.*',
                        'projects.id as project_id',
                        'projects.project_name',
                        'projects.project_description',
                        'projects.project_url',
                    )
                    ->get();

                $result = $projects->groupBy('id')->map(function ($rows) {
                    $emp = $rows->first();
                    return [
                        'id' => $emp->id,
                        'employee_name' => $emp->employee_name,
                        'employee_email' => $emp->employee_email,
                        'plant_id' => $emp->plant_id,
                        'employee_code' => $emp->employee_code,
                        'projects' => $rows->map(function ($r) {
                            return [
                                'id' => $r->project_id,
                                'name' => $r->project_name,
                                'description' => $r->project_description,
                                'url' => $r->project_url,
                            ];
                        })->values()->toArray(),
                    ];
                })->first();

                return view('dashboard.emp-dashboard', compact('projects'));
            }
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
}

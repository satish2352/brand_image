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
    EmployeePlantAssignment
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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



                return view('dashboard.dashboard', compact(
                    'allArea',
                    'allMediaManagement',
                    'allCategory',
                    'allFacingDirection',
                    'allIllumination',

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

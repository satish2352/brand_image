<?php

namespace App\Http\Controllers\Superadm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Models\Employees;
use App\Models\EmployeePlantAssignment;
use Illuminate\Support\Facades\Session;
use App\Models\FinancialYear;
use App\Models\PlantMaster;
use App\Models\Roles;

class EmployeeLoginController extends Controller
{
    public function loginEmployee()
    {
        $financialYears = FinancialYear::where('is_deleted', 0)
            ->where('is_active', 1)
            ->orderBy('year', 'desc')
            ->get();

        return view('superadm.emp-login', compact('financialYears'));
    }

    public function validateEmpLogin(Request $req)
    {
        $req->validate([
            'superemail' => 'required|string',
            'superpassword' => 'required',
            'plant_id' => 'required',
            'financial_year_id' => 'required',
        ], [
            'superemail.required' => 'Enter User Name',
            'superpassword.required' => 'Enter Password',
            'plant_id.required' => 'Please Select a Plant',
            'financial_year_id.required' => 'Please Select Financial Year',
        ]);

        $uname = $req->input('superemail');
        $pass = $req->input('superpassword');
        $plant = $req->input('plant_id');

        $employee = Employees::where('employee_user_name', $uname)
                             ->where('is_deleted', 0)
                             ->first();

        $plantData = \DB::table('plant_masters')
                ->where('id', $plant)
                ->where('is_deleted', 0)
                ->first();

        if (!$employee) return redirect()->back()->with('error', 'Credentials Incorrect');
        if ($employee->is_active == 0) return redirect()->back()->with('error', 'Your Account Has Been Deactivated. Please Contact The Admin For Assistance.');
        if (!Hash::check($pass, $employee->employee_password)) return redirect()->back()->with('error', 'Credentials Incorrect');

        $hasPlant = EmployeePlantAssignment::where('employee_id', $employee->id)
                                           ->where('plant_id', $plant)
                                           ->where('is_active', 1)
                                           ->exists();

        if (!$hasPlant) return redirect()->back()->with('error', 'Selected Plant Not Assigned');

        // Store session
        Session::put('emp_user_id', $employee->id);
        Session::put('emp_role_id', $employee->role_id);
        Session::put('emp_role_name', $employee->role->role ?? null);
        Session::put('emp_plant_id', $plant);
        Session::put('emp_plant_code', $plantData->plant_code);
        Session::put('emp_code', $employee->employee_code);
        Session::put('designation', $employee->designation->designation ?? null);
        Session::put('emp_financial_year_id', $req->financial_year_id);
        Session::put('role', 'employee');
        Session::put('email_id', $employee->employee_email);
        Session::put('com_portal_url', env('ASSET_URL'));

        return redirect()->route('dashboard-emp');
    }

    public function logOut(Request $req)
    {
        $req->session()->flush();
        return redirect()->route('emp.login');
    }
}

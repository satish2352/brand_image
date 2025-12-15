<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Services\Superadm\EmployeePlantAssignmentService;
use App\Models\Employees;
use App\Models\PlantMasters;
use App\Models\Departments;
use App\Models\EmployeePlantAssignment;
use App\Models\Projects;
use Illuminate\Support\Facades\Validator;
use Exception;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeePlantAssignmentsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;

class EmployeePlantAssignmentController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new EmployeePlantAssignmentService();
    }

    // List all assignments
    public function index()
    {
        try {
            $assignments = $this->service->list();
            return view('superadm.employee_assignments.list', compact('assignments'));  
        } catch (Exception $e) {
            return back()->with('error', 'Error fetching assignments: ' . $e->getMessage());
        }
    }

    // Show create form
    public function create()
    {
        try {
            $employees = Employees::where('is_active',1)->where('is_deleted',0)->where('employee_name', '!=', '0')->whereNotNull('employee_name')->get();
            $plants = PlantMasters::where('is_active',1)->where('is_deleted',0)->get();
            $departments = Departments::where('is_active',1)->where('is_deleted',0)->get();
            $projects = Projects::where('is_active',1)->where('is_deleted',0)->get();

            return view('superadm.employee_assignments.create', compact('employees','plants','departments','projects'));
        } catch (Exception $e) {
            return back()->with('error', 'Error loading form: ' . $e->getMessage());
        }
    }

    // Save new assignment
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id'   => 'required',
            'plant_id'      => 'required',
            'department_id' => 'required|array|min:1',
            'projects_id'   => 'required|array|min:1',
        ], [
            'employee_id.required'   => 'Please Select An Employee',
            'plant_id.required'      => 'Please Select a Plant',
            'department_id.required' => 'Please Select At Least One Department',
            'projects_id.required'   => 'Please Select At Least One Project',
        ]);

        $validator->validate();

        try {
            // Check for duplicate employee + plant
            $exists = $this->service->exists($request->employee_id, $request->plant_id);
            if($exists){
                $employeeName = Employees::find($request->employee_id)->employee_name;
                return back()->withInput()->with('error', "$employeeName Is Already Assigned To The Selected Plant.");
            }

            // Save assignment
            $this->service->save($request);

            $employeeName = Employees::find($request->employee_id)->employee_name;
            $plantName = PlantMasters::find($request->plant_id)->plant_name;

            return redirect()->route('employee.assignments.list')
                             ->with('success', "$employeeName Has Been Assigned To $plantName Plant Successfully.");

        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Error adding assignment: ' . $e->getMessage());
        }
    }

    // public function sendApi(Request $request)
    // {
    //     $request->validate([
    //         'id' => 'required'
    //     ]);

    //     try {
    //         $assignment = EmployeePlantAssignment::with(['employee.designation', 'plant'])
    //                         ->findOrFail($request->id);

    //         $employee = $assignment->employee;
    //         $plant = $assignment->plant;

    //         // Decode department IDs (stored as JSON like ["54","53"])
    //         $departmentIds = is_array($assignment->department_id)
    //             ? $assignment->department_id
    //             : json_decode($assignment->department_id ?? '[]', true);

    //         // Fetch both department codes and short names
    //         $departments = Departments::whereIn('id', $departmentIds)->get();

    //         $departmentCodes = $departments->pluck('department_code')->implode(',');
    //         $departmentShortNames = $departments->pluck('department_short_name')->implode(',');

    //         // Get projects
    //         $projectIds = $assignment->projects_id ?? [];
    //         $projects = Projects::whereIn('id', $projectIds)->get();

    //         $responses = [];

    //         foreach ($projects as $proj) {
    //             $payload = [
    //                 'plant'              => $plant->plant_code,
    //                 'dept'               => $departmentCodes,        // e.g. "54,53"
    //                 'dept_short_names'   => $departmentShortNames,   // e.g. "IT,HR/Admin"
    //                 'email_id'           => $employee->employee_email,
    //                 'role'               => $employee->role->role ?? '',
    //                 'emp_name'           => $employee->employee_name,
    //                 'emp_code'           => $employee->employee_code,
    //                 'emp_type'           => $employee->employee_type,
    //                 'designation'        => $employee->designation->designation ?? '',
    //                 'username'           => $employee->employee_user_name,
    //                 'password'           => decrypt($employee->plain_password ?? ''),
    //                 'status'             => $assignment->is_active,
    //             ];

    //             // Extract project name dynamically from project_url
    //             $projectName = '';
    //             if (preg_match('#https?://[^/]+/([^/]+)/#', $proj->project_url, $matches)) {
    //                 $projectName = $matches[1]; // e.g. 'alfkaizen'
    //             }

    //             // Build API URL dynamically
    //             $apiUrl = "https://alfitworld.com/{$projectName}/CommonController/api_add_employee";

    //             // Send POST request
    //             $response = Http::post($apiUrl, $payload);

    //             $responses[] = [
    //                 'project_id' => $proj->id,
    //                 'payload'    => $payload,
    //                 'status'     => $response->successful() ? 'success' : 'failed',
    //                 'response'   => $response->body(),
    //                 'function'   => 'sendApi'
    //             ];
    //         }

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'API call sent successfully for ' . $employee->employee_name,
    //             'data' => $responses
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $e->getMessage()
    //         ]);
    //     }
    // }

    public function sendApi(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'departments' => 'required|array'
        ]);

        try {
            $assignment = EmployeePlantAssignment::with(['employee.designation', 'plant'])
                            ->findOrFail($request->id);

            $employee = $assignment->employee;
            $plant = $assignment->plant;

            $projectIds = $assignment->projects_id ?? [];
            $projects = Projects::whereIn('id', $projectIds)->get();

            $responses = [];

            foreach ($projects as $proj) {
                // 🔹 Fetch departments selected for this specific project
                $selectedDeptIds = $request->departments[$proj->id] ?? [];

                if (empty($selectedDeptIds)) {
                    // if for any project no any department not selected then, skip this
                    $responses[] = [
                        'project_id' => $proj->id,
                        'status' => 'skipped',
                        'message' => 'No departments selected for this project',
                    ];
                    continue;
                }

                $departments = Departments::whereIn('id', $selectedDeptIds)->get();
                $departmentCodes = $departments->pluck('department_code')->implode(',');
                $departmentShortNames = $departments->pluck('department_short_name')->implode(',');

                $payload = [
                    'plant'              => $plant->plant_code,
                    'dept'               => $departmentCodes,
                    'dept_short_names'   => $departmentShortNames,
                    'email_id'           => $employee->employee_email,
                    'role'               => $employee->role->role ?? '',
                    'emp_name'           => $employee->employee_name,
                    'emp_code'           => $employee->employee_code,
                    'emp_type'           => $employee->employee_type,
                    'designation'        => $employee->designation->designation ?? '',
                    'username'           => $employee->employee_user_name,
                    'password'           => decrypt($employee->plain_password ?? ''),
                    'status'             => $assignment->is_active,
                    'com_portal_url'     => env('ASSET_URL'),
                ];

                // Extract project name dynamically
                $projectName = '';
                if (preg_match('#https?://[^/]+/([^/]+)/#', $proj->project_url, $matches)) {
                    $projectName = $matches[1];
                }

                $apiUrl = "https://alfitworld.com/{$projectName}/CommonController/api_add_employee";

                // 🔹 Send to that project’s API
                $response = Http::post($apiUrl, $payload);

                $responses[] = [
                    'project_id' => $proj->id,
                    'project_name' => $proj->project_name,
                    'departments_sent' => $departments->pluck('department_name')->toArray(),
                    'payload' => $payload,
                    'status' => $response->successful() ? 'success' : 'failed',
                    'response' => $response->body(),
                ];
            }

                // After all API calls, mark as sent
                $assignment->update(['send_api' => 1]);

            return response()->json([
                'status' => true,
                'message' => 'API sent successfully for ' . $employee->employee_name,
                'data' => $responses,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function getProjects(Request $request)
    {
        $assignment = EmployeePlantAssignment::find($request->id);

        if (!$assignment) {
            return response()->json(['status' => false, 'message' => 'Assignment not found']);
        }

        // Decode projects_id
        $projectIds = is_array($assignment->projects_id)
            ? $assignment->projects_id
            : json_decode($assignment->projects_id, true);

        $projects = !empty($projectIds)
            ? Projects::whereIn('id', $projectIds)->get(['id', 'project_name'])
            : [];

        // Decode department_id
        $departmentIds = is_array($assignment->department_id)
            ? $assignment->department_id
            : json_decode($assignment->department_id, true);

        // only selected departments shown
        $departments = !empty($departmentIds)
            ? Departments::whereIn('id', $departmentIds)->get(['id', 'department_name'])
            : [];

        return response()->json([
            'status' => true,
            'projects' => $projects,
            'departments' => $departments,
        ]);
    }

    public function checkSendApi()
    {
        $data = EmployeePlantAssignment::select('id', 'send_api')->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }


    // public function sendApi(Request $request)
    // {
    //     $request->validate([
    //         'id' => 'required'
    //     ]);

    //     try {
    //         $assignment = EmployeePlantAssignment::with(['employee', 'plant'])
    //                         ->findOrFail($request->id);

    //         $employee = $assignment->employee;
    //         $plant = $assignment->plant;

    //         // Get department codes
    //         $departmentCodes = Departments::whereIn('id', $assignment->department_id ?? [])
    //                     ->pluck('department_code')
    //                     ->implode(',');

    //         // Get projects
    //         $projectIds = $assignment->projects_id ?? [];
    //         $projects = Projects::whereIn('id', $projectIds)->get();

    //         $responses = [];

    //         foreach ($projects as $proj) {
    //             $payload = [
    //                 'plant'          => $plant->plant_code,
    //                 'dept'     => $departmentCodes,
    //                 'email_id'      => $employee->employee_email,
    //                 'role'                => $employee->role->role ?? '',
    //                 'emp_name'       => $employee->employee_name,
    //                 'emp_code'       => $employee->employee_code,
    //                 'emp_type'           => $employee->employee_type,
    //                 'username'  => $employee->employee_user_name,
    //                 'password'  => decrypt($employee->plain_password ?? ''),
    //                 'status'         => $assignment->is_active,
    //                 // 'password'   => $employee->employee_password, // hashed
    //                 // 'project_id'          => $proj->id, // optional
    //             ];

    //                 // Extract project name dynamically from project_url
    //                 $projectName = '';
    //                 if (preg_match('#https?://[^/]+/([^/]+)/#', $proj->project_url, $matches)) {
    //                     $projectName = $matches[1]; // This will be like 'alfkaizen' 
    //                 }
    //                 // Log the project name
    //                 // \Log::info('Project Name: ' . $projectName);

    //                 // Send POST request and capture response
    //                 // $response = Http::post('https://alfitworld.com/alfkaizen/CommonController/api_add_employee', $payload);

    //                 // Build the API URL dynamically
    //                 $apiUrl = "https://alfitworld.com/{$projectName}/CommonController/api_add_employee";

    //                 // Send POST request
    //                 $response = Http::post($apiUrl, $payload);

    //             $responses[] = [
    //                 'project_id' => $proj->id,
    //                 'payload'    => $payload,
    //                 'status'     => $response->successful() ? 'success' : 'failed',
    //                 'response'   => $response->body(),
    //                 'function'   => 'sendApi'
    //             ];
    //         }

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'API call sent successfully for ' . $employee->employee_name,
    //             'data' => $responses
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $e->getMessage()
    //         ]);
    //     }
    // }


    // Show edit form
    public function edit($encodedId)
    {
        try {
            $id = base64_decode($encodedId);
            $assignment = $this->service->getById($id);

            $employees = Employees::where('is_active',1)->where('is_deleted',0)->where('employee_name', '!=', '0')->whereNotNull('employee_name')->get();
            $plants = PlantMasters::where('is_active',1)->where('is_deleted',0)->get();
            $departments = Departments::where('is_active',1)->where('is_deleted',0)->get();
            $projects = Projects::where('is_active',1)->where('is_deleted',0)->get();

            return view('superadm.employee_assignments.edit', compact(
                'assignment','employees','plants','departments','projects','encodedId'
            ));
        } catch(Exception $e) {
            return back()->with('error', 'Error loading edit form: ' . $e->getMessage());
        }
    }

    // Update assignment
    public function update(Request $request, $encodedId)
    {
        $id = base64_decode($encodedId);

        $validator = Validator::make($request->all(), [
            'employee_id'   => 'required',
            'plant_id'      => 'required',
            'department_id' => 'required|array|min:1',
            'projects_id'   => 'required|array|min:1',
            'is_active'     => 'required|in:0,1',
        ], [
            'employee_id.required'   => 'Please Select An Employee',
            'plant_id.required'      => 'Please Select a Plant',
            'department_id.required' => 'Please Select At Least One Department',
            'projects_id.required'   => 'Please Select At Least One Project',
            'is_active.required'     => 'Please Select Status',
        ]);

        $validator->validate();

        try {
            // Check for duplicate, excluding current record
            $exists = $this->service->exists($request->employee_id, $request->plant_id, $id);
            if($exists){
                $employeeName = Employees::find($request->employee_id)->employee_name;
                return back()->withInput()->with('error', "$employeeName Is Already Assigned To The Selected Plant.");
            }

            // Update send_api col 0
            $request->merge(['send_api' => 0]);

            // Update assignment
            $this->service->update($request, $id);

            $employeeName = Employees::find($request->employee_id)->employee_name;
            $plantName = PlantMasters::find($request->plant_id)->plant_name;

            return redirect()->route('employee.assignments.list')
                             ->with('success', "$employeeName Assigned Has Been Updated To $plantName Plant Successfully.");

        } catch(Exception $e) {
            return back()->withInput()->with('error', 'Error updating assignment: ' . $e->getMessage());
        }
    }

    // Delete assignment
    // public function delete(Request $request)
    // {
    //     try {
    //         $id = base64_decode($request->id);
    //         $assignment = $this->service->getById($id);
    //         $employeeName = $assignment->employee->employee_name;
    //         $plantName = $assignment->plant->plant_name;

    //         $this->service->delete($request);

    //         return response()->json([
    //             'status'=>true,
    //             'message'=> "$employeeName Assignment For $plantName Plant Has Been Deleted Successfully."
    //         ]);
    //     } catch(Exception $e) {
    //         return response()->json(['status'=>false,'message'=>'Error deleting assignment: '.$e->getMessage()]);
    //     }
    // }

    public function delete(Request $request)
    {
        try {
            $id = base64_decode($request->id);
            $assignment = $this->service->getById($id);

            if (!$assignment) {
                return response()->json(['status' => false, 'message' => 'Assignment not found.']);
            }

            $employee = $assignment->employee;
            $plant = $assignment->plant;

            $employeeName = $employee->employee_name;
            $plantName = $plant->plant_name;

            // Get related projects
            $projects = Projects::whereIn('id', $assignment->projects_id ?? [])->get();

            $responses = [];
            $failedProjects = [];
            $successfulProjects = [];

            foreach ($projects as $proj) {
                $payload = [
                    'plant'             => $plant->plant_code,
                    'emp_code'          => $employee->employee_code,
                    'com_portal_url'    => env('ASSET_URL'),
                    'status'            => 0,
                    'is_deleted'        => $request->is_deleted ?? 1,
                ];

                // Extract project name from URL
                $projectName = '';
                if (preg_match('#https?://[^/]+/([^/]+)/#', $proj->project_url, $matches)) {
                    $projectName = $matches[1];
                }

                $apiUrl = "https://alfitworld.com/{$projectName}/CommonController/api_delete_employee";

                // Call API
                $response = Http::withoutRedirecting()->post($apiUrl, $payload);
                $body = $response->body();

                if ($response->status() == 302 || str_contains($body, 'User unable to delete')) {
                    // API failed for this project
                    $failedProjects[] = ucfirst($projectName);
                    $status = 'failed';
                } else {
                    // Success for this project
                    $successfulProjects[] = ucfirst($projectName);
                    $status = 'success';
                }

                $responses[] = [
                    'project_id' => $proj->id,
                    'url'        => $apiUrl,
                    'payload'    => $payload,
                    'status'     => $status,
                    'response'   => $body,
                ];
            }

            // Build dynamic message
            $messageParts = [];

            if (!empty($successfulProjects)) {
                $messageParts[] = implode(', ', $successfulProjects) . ' project employee record deleted successfully.';
            }

            if (!empty($failedProjects)) {
                $messageParts[] = implode(', ', $failedProjects) . ' project employee record cannot be deleted since related records are linked to them.';
            }

            $finalMessage = implode(' ', $messageParts);

            // If any failed project, skip local deletion
            if (!empty($failedProjects)) {
                return response()->json([
                    'status' => false,
                    'message' => $finalMessage,
                    'api_responses' => $responses,
                ]);
            }

            // ✅ Delete locally if all succeeded
            $this->service->delete($request);

            return response()->json([
                'status' => true,
                'message' => "$employeeName assignment for $plantName plant has been deleted successfully. $finalMessage",
                'api_responses' => $responses,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error deleting assignment: ' . $e->getMessage(),
            ]);
        }
    }


    // before check which project under record delete and which record project under record not delete

    // public function delete(Request $request)
    // {
    //     try {
    //         $id = base64_decode($request->id);
    //         $assignment = $this->service->getById($id);

    //         if (!$assignment) {
    //             return response()->json(['status' => false, 'message' => 'Assignment not found.']);
    //         }

    //         $employee = $assignment->employee;
    //         $plant = $assignment->plant;

    //         $employeeName = $employee->employee_name;
    //         $plantName = $plant->plant_name;

    //         // Get related projects
    //         $projects = Projects::whereIn('id', $assignment->projects_id ?? [])->get();

    //         $responses = [];
    //         $apiFailed = false;
    //         $errorMessage = null;

    //         foreach ($projects as $proj) {
    //             $payload = [
    //                 'plant'      => $plant->plant_code,
    //                 'emp_code'   => $employee->employee_code,
    //                 'status'     => 0,
    //                 'is_deleted' => $request->is_deleted ?? 1,
    //             ];

    //             // Extract project name from URL
    //             $projectName = '';
    //             if (preg_match('#https?://[^/]+/([^/]+)/#', $proj->project_url, $matches)) {
    //                 $projectName = $matches[1];
    //             }

    //             // Target API endpoint
    //             $apiUrl = "https://alfitworld.com/{$projectName}/CommonController/api_delete_employee";

    //             // Call API
    //             $response = Http::withoutRedirecting()->post($apiUrl, $payload);

    //             // Detect "User unable to delete"
    //             $body = $response->body();

    //             if ($response->status() == 302 || str_contains($body, 'User unable to delete')) {
    //                 $apiFailed = true;
    //                 $errorMessage = 'This employee cannot be deleted since related records are linked to them.';
    //             }

    //             $responses[] = [
    //                 'project_id' => $proj->id,
    //                 'url'        => $apiUrl,
    //                 'payload'    => $payload,
    //                 'status'     => $response->successful() ? 'success' : 'failed',
    //                 'response'   => $body,
    //             ];
    //         }

    //         // Stop here if API says cannot delete
    //         if ($apiFailed) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $errorMessage ?? 'External system prevented deletion.',
    //                 'api_responses' => $responses,
    //             ]);
    //         }

    //         // Only delete locally if all remote deletions succeeded
    //         $this->service->delete($request);

    //         return response()->json([
    //             'status' => true,
    //             'message' => "$employeeName assignment for $plantName plant has been deleted successfully.",
    //             'api_responses' => $responses,
    //         ]);

    //     } catch (Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Error deleting assignment: ' . $e->getMessage(),
    //         ]);
    //     }
    // }


    // Update status (Active / Inactive)
    // public function updateStatus(Request $request)
    // {
    //     try {
    //         $id = base64_decode($request->id);
    //         $assignment = $this->service->getById($id);
    //         $employeeName = $assignment->employee->employee_name;
    //         $plantName = $assignment->plant->plant_name;

    //         $this->service->updateStatus($request);

    //         $statusText = $request->is_active == 1 ? 'Activated' : 'Deactivated';
    //         return response()->json([
    //             'status'=>true,
    //             'message'=> "$employeeName Assignment For $plantName Plant has been $statusText Successfully."
    //         ]);
    //     } catch(Exception $e) {
    //         return response()->json(['status'=>false,'message'=>'Error updating status: '.$e->getMessage()]);
    //     }
    // }

    public function updateStatus(Request $request)
    {
        try {
            $id = base64_decode($request->id);
            $assignment = $this->service->getById($id);
            $employeeName = $assignment->employee->employee_name;
            $plantName = $assignment->plant->plant_name;

            // First update status in DB
            $this->service->updateStatus($request);

            $employee = $assignment->employee;
            $plant = $assignment->plant;

            // $departmentCodes = Departments::whereIn('id', $assignment->department_id ?? [])
            //             ->pluck('department_code')
            //             ->implode(',');

            // Decode department IDs (stored as JSON like ["54","53"])
            $departmentIds = is_array($assignment->department_id)
                ? $assignment->department_id
                : json_decode($assignment->department_id ?? '[]', true);

            // Fetch both department codes and short names
            $departments = Departments::whereIn('id', $departmentIds)->get();

            $departmentCodes = $departments->pluck('department_code')->implode(',');
            $departmentShortNames = $departments->pluck('department_short_name')->implode(',');

            $projectIds = $assignment->projects_id ?? [];
            $projects = Projects::whereIn('id', $projectIds)->get();

            $responses = [];  // Store each API result

            foreach ($projects as $proj) {

                $payload = [
                    'plant'          => $plant->plant_code,
                    // 'dept'           => $departmentCodes,
                    // 'dept_short_names'   => $departmentShortNames,  
                    'email_id'       => $employee->employee_email,
                    'role'           => $employee->role->role ?? '',
                    'emp_name'       => $employee->employee_name,
                    'emp_code'       => $employee->employee_code,
                    'emp_type'       => $employee->employee_type,
                    'username'       => $employee->employee_user_name,
                    'password'       => decrypt($employee->plain_password ?? ''),
                    'status'         => $request->is_active, 
                    'com_portal_url'     => env('ASSET_URL'),
                ];

                // Extract project name
                $projectName = '';
                if (preg_match('#https?://[^/]+/([^/]+)/#', $proj->project_url, $matches)) {
                    $projectName = $matches[1];
                }

                $apiUrl = "https://alfitworld.com/{$projectName}/CommonController/api_update_employee_status";

                // API CALL
                $response = Http::post($apiUrl, $payload);

                // Store response details
                $responses[] = [
                    'project_id' => $proj->id,
                    'url'        => $apiUrl,
                    'payload'    => $payload,
                    'status'     => $response->successful() ? 'success' : 'failed',
                    'response'   => $response->body(),
                ];
            }

            $statusText = $request->is_active == 1 ? 'Activated' : 'Deactivated';

            return response()->json([
                'status' => true,
                'message' => "$employeeName Assiged For $plantName Plant has been $statusText Successfully.",
                'api_responses' => $responses  // Here you get all API responses
            ]);

        } catch(Exception $e) {
            return response()->json([
                'status'=>false,
                'message'=>'Error updating status: '.$e->getMessage()
            ]);
        }
    }


public function export(Request $request)
{
    $type = $request->query('type', 'excel'); // default excel

    // Get assignments (consider adding search filters later if needed)
    $assignments = EmployeePlantAssignment::where('is_deleted', 0)->get();

    if ($assignments->isEmpty()) {
        return redirect()->back()->with('error', 'No data available to export.');
    }

    if ($type === 'excel') {
        return Excel::download(new EmployeePlantAssignmentsExport, 'EmployeeAssignments.xlsx');
    }

    $pdf = Pdf::loadView('superadm.employee_assignments.assignments_pdf', compact('assignments'))
            ->setPaper('A4', 'landscape');
    return $pdf->download('EmployeeAssignments.pdf');

    return redirect()->back()->with('error', 'Invalid export type.');
}


}

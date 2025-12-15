<?php
namespace App\Http\Services\Superadm;

use Illuminate\Http\Request;
use App\Http\Repository\Superadm\ProjectsRepository;
use Exception;
use Log;

class ProjectsService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new ProjectsRepository();
    }

    public function list()
    {
        try {
            return $this->repo->list();
        } catch (Exception $e) {
            Log::error("Project Service list error: " . $e->getMessage());
            return false;
        }
    }

    // public function listajaxlist($req)
	// {  
	// 	 try {
    //         return $this->repo->listajaxlist($req['plant_id']);
    //     } catch (Exception $e) {
    //         Log::error("Project Service list error: " . $e->getMessage());
    //         return false;
    //     }
	// }

    public function listajaxlist($req)
    {  
        try {
            $plantId = $req['plant_id'] ?? null; // get plant_id from request

            if (!$plantId) {
                return collect(); // return empty collection if no plant_id
            }

            return \App\Models\Projects::where('is_deleted', 0)
                ->where('is_active', 1)
                // ->whereJsonContains('plant_id', (int)$plantId) // ✅ match JSON array
                ->whereJsonContains('plant_id', (string)$plantId)
                ->get(['id', 'project_name']);
                
        } catch (Exception $e) {
            Log::error("Project Service list error: " . $e->getMessage());
            return false;
        }
    }


    public function save($req)
    {
        try {
            $data = [   
                        // 'plant_id' => $req->input('plant_id'), 
                        'plant_id' => json_encode($req->input('plant_id')),
                        'project_name' => $req->input('project_name'), 
                        'project_url' => $req->input('project_url')
                    ];
                     if ($req->filled('project_description')) {
            $data['project_description'] = $req->input('project_description');
        }
            return $this->repo->save($data);
        } catch (Exception $e) {
            Log::error("Project Service save error: " . $e->getMessage());
            return false;
        }
    }

    public function edit($id)
    {
        try {
            return $this->repo->edit($id);
        } catch (Exception $e) {
            Log::error("Project Service edit error: " . $e->getMessage());
            return false;
        }
    }

    public function update($req)
    {

        try {
            $id = $req->id;
            $data = [
                // 'plant_id' => $req->input('plant_id'), 
                'plant_id' => json_encode($req->input('plant_id')),
                'project_name' => $req->input('project_name'), 
                'project_url' => $req->input('project_url'),
                'is_active' => $req->is_active
            ];
            if ($req->filled('project_description')) {
                $data['project_description'] = $req->input('project_description');
            }
            return $this->repo->update($data, $id);
        } catch (Exception $e) {
            Log::error("Project Service update error: " . $e->getMessage());
            return false;
        }
    }

    // public function delete($req)
    // {
    //     try {
    //         $id = base64_decode($req->id);
    //         $data = ['is_deleted' => 1];

    //         return $this->repo->delete($data, $id);
    //     } catch (Exception $e) {
    //         Log::error("Project Service delete error: " . $e->getMessage());
    //         return false;
    //     }
    // }

    public function delete($req)
{
    try {
        $id = base64_decode($req->id);

        // Get project details to show name in message
        $project = $this->repo->edit($id); // assuming edit() returns project data
        $projectName = $project->project_name ?? 'This project';

        // Check if any employee uses this project
        $employeeCount = \DB::table('employee_plant_assignments')
            // ->where('projects_id', $id) // ✅ updated here
            ->whereRaw('JSON_CONTAINS(projects_id, ?)', [json_encode((string)$id)])
            ->where('is_deleted', 0)
            ->count();

        if ($employeeCount > 0) {
            throw new \Exception("Cannot delete the project '{$projectName}' because it is assigned to one or more employees.");
        }

        // If no employees use it, soft delete
        $data = ['is_deleted' => 1];
        return $this->repo->delete($data, $id);

    } catch (\Exception $e) {
        \Log::error("Project Service delete error: " . $e->getMessage());
        throw $e; // rethrow to controller
    }
}


    public function updateStatus($req)
    {
        try {
            $id = base64_decode($req->id);
            $data = ['is_active' => $req->is_active];

            return $this->repo->updateStatus($data, $id);
        } catch (Exception $e) {
            Log::error("Project Service updateStatus error: " . $e->getMessage());
            return false;
        }
    }
}

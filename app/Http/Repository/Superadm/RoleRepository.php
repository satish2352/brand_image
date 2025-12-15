<?php

namespace App\Http\Repository\Superadm;
use Illuminate\Http\Request;
use App\Models\Roles;
use DB;
use Exception;
use Illuminate\Support\Facades\Log;

class RoleRepository
{
    public function list()
    {
        try {
            return Roles::where('is_deleted', 0)
                ->orderBy('id', 'desc')
                ->get();
        } catch (Exception $e) {
            Log::error("Error fetching role list: " . $e->getMessage());
            return collect(); 
        }
    }

    public function save($data)
    {
        try {
            return Roles::create($data);
        } catch (Exception $e) {
            Log::error("Error saving role: " . $e->getMessage());
            return false;
        }
    }

    public function edit($id)
    {
        try {
            return Roles::where('id', $id)->first();
        } catch (Exception $e) {
            Log::error("Error editing role ID {$id}: " . $e->getMessage());
            return null;
        }
    }

    public function update($data, $id)
    {
        try {
            return Roles::where('id', $id)->update($data);
        } catch (Exception $e) {
            Log::error("Error updating role ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    public function delete($data, $id)
    {
        try {
            return Roles::where('id', $id)->update($data);
        } catch (Exception $e) {
            Log::error("Error deleting role ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    public function updateStatus($data, $id)
    {
        try {
            return Roles::where('id', $id)->update($data);
        } catch (Exception $e) {
            Log::error("Error updating status for role ID {$id}: " . $e->getMessage());
            return false;
        }
    }
}

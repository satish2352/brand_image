<?php

namespace App\Http\Services\Superadm\Master;

use App\Http\Repository\Superadm\Master\CategoryRepository;
use Exception;
use Illuminate\Support\Facades\Log;

class CategoryService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new CategoryRepository();
    }

    public function list()
    {
        return $this->repo->list();
    }

    public function store($request)
    {
        try {
            return $this->repo->create([
                'category_name' => $request->category_name,
                'is_active' => 1,
                'is_deleted' => 0,
            ]);
        } catch (Exception $e) {
            Log::error("Category store error: " . $e->getMessage());
            throw $e;
        }
    }

    public function edit($id)
    {
        return $this->repo->findById($id);
    }

    public function update($request)
    {
        try {
            return $this->repo->updateById($request->id, [
                'category_name' => $request->category_name,
                'is_active' => $request->is_active,
            ]);
        } catch (Exception $e) {
            Log::error("Category update error: " . $e->getMessage());
            throw $e;
        }
    }

    public function delete($encodedId)
    {
        $id = base64_decode($encodedId);
        return $this->repo->softDelete($id);
    }

    public function updateStatus($encodedId, $status)
    {
        $id = base64_decode($encodedId);
        return $this->repo->updateStatus($id, $status);
    }
}

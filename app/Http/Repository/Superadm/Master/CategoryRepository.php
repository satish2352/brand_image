<?php

namespace App\Http\Repository\Superadm\Master;

use App\Models\Category;
use Exception;
use Illuminate\Support\Facades\Log;

class CategoryRepository
{
    public function list()
    {
        try {
            return Category::where('is_deleted', 0)
                ->orderBy('id', 'desc')
                ->get();
        } catch (Exception $e) {
            Log::error("Category list error: " . $e->getMessage());
            return collect();
        }
    }

    public function create(array $data)
    {
        return Category::create($data);
    }

    public function findById($id)
    {
        return Category::where('id', $id)->where('is_deleted', 0)->first();
    }

    public function updateById($id, array $data)
    {
        return Category::where('id', $id)->update($data);
    }

    public function softDelete($id)
    {
        return Category::where('id', $id)->update(['is_deleted' => 1]);
    }

    public function updateStatus($id, $status)
    {
        return Category::where('id', $id)->update(['is_active' => $status]);
    }
}

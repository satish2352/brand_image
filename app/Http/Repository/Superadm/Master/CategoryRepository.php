<?php

namespace App\Http\Repository\Superadm\Master;

use App\Models\Category;
use App\Support\MasterCache;
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
        $result = Category::create($data);
        MasterCache::forgetCategories();

        return $result;
    }

    public function findById($id)
    {
        return Category::where('id', $id)->where('is_deleted', 0)->first();
    }

    public function updateById($id, array $data)
    {
        $result = Category::where('id', $id)->update($data);
        MasterCache::forgetCategories();

        return $result;
    }

    public function softDelete($id)
    {
        $result = Category::where('id', $id)->update(['is_deleted' => 1]);
        MasterCache::forgetCategories();

        return $result;
    }

    public function updateStatus($id, $status)
    {
        $result = Category::where('id', $id)->update(['is_active' => $status]);
        MasterCache::forgetCategories();

        return $result;
    }
}

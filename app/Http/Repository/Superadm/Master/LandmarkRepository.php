<?php

namespace App\Http\Repository\Superadm\Master;

use App\Models\Landmark;
use App\Support\MasterCache;

class LandmarkRepository
{
    public function getAll()
    {
        return Landmark::where('is_deleted', 0)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function existsByName($name, $ignoreId = null)
    {
        $query = Landmark::where('landmark_name', $name)
            ->where('is_deleted', 0);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    public function store(array $data)
    {
        $result = Landmark::create($data);
        MasterCache::forgetLandmarks();

        return $result;
    }

    public function findDeletedByName($name)
    {
        return Landmark::where('landmark_name', $name)
            ->where('is_deleted', 1)
            ->first();
    }

    public function find($id)
    {
        return Landmark::where('id', $id)
            ->where('is_deleted', 0)
            ->firstOrFail();
    }

    public function update($id, array $data)
    {
        $result = Landmark::where('id', $id)->update($data);
        MasterCache::forgetLandmarks();

        return $result;
    }

    public function toggleStatus($id)
    {
        $item = Landmark::findOrFail($id);
        $result = $item->update(['is_active' => !$item->is_active]);
        MasterCache::forgetLandmarks();

        return $result;
    }

    public function softDelete($id)
    {
        $result = Landmark::where('id', $id)->update([
            'is_deleted' => 1,
            'is_active'  => 0
        ]);
        MasterCache::forgetLandmarks();

        return $result;
    }
}

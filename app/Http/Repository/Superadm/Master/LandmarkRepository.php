<?php

namespace App\Http\Repository\Superadm\Master;

use App\Models\Landmark;

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
        return Landmark::create($data);
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
        return Landmark::where('id', $id)->update($data);
    }

    public function toggleStatus($id)
    {
        $item = Landmark::findOrFail($id);
        return $item->update(['is_active' => !$item->is_active]);
    }

    public function softDelete($id)
    {
        return Landmark::where('id', $id)->update([
            'is_deleted' => 1,
            'is_active'  => 0
        ]);
    }
}

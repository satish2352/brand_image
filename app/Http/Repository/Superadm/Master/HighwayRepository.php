<?php

namespace App\Http\Repository\Superadm\Master;

use App\Models\Highway;

class HighwayRepository
{
    public function getAll()
    {
        return Highway::where('is_deleted', 0)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function existsByName($name, $ignoreId = null)
    {
        $query = Highway::where('highway_name', $name)
            ->where('is_deleted', 0);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    public function store(array $data)
    {
        return Highway::create($data);
    }

    public function findDeletedByName($name)
    {
        return Highway::where('highway_name', $name)
            ->where('is_deleted', 1)
            ->first();
    }

    public function find($id)
    {
        return Highway::where('id', $id)
            ->where('is_deleted', 0)
            ->firstOrFail();
    }

    public function update($id, array $data)
    {
        return Highway::where('id', $id)->update($data);
    }

    public function toggleStatus($id)
    {
        $item = Highway::findOrFail($id);
        return $item->update(['is_active' => !$item->is_active]);
    }

    public function softDelete($id)
    {
        return Highway::where('id', $id)->update([
            'is_deleted' => 1,
            'is_active'  => 0
        ]);
    }
}

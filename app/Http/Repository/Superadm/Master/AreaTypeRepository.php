<?php

namespace App\Http\Repository\Superadm\Master;

use App\Models\AreaType;

class AreaTypeRepository
{
    public function getAll()
    {
        return AreaType::where('is_deleted', 0)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function existsByName($name, $ignoreId = null)
    {
        $query = AreaType::where('areatype_name', $name)
            ->where('is_deleted', 0);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    public function store(array $data)
    {
        return AreaType::create($data);
    }
    public function findDeletedByName($name)
    {
        return AreaType::where('areatype_name', $name)
            ->where('is_deleted', 1)
            ->first();
    }
    public function find($id)
    {
        return AreaType::where('id', $id)
            ->where('is_deleted', 0)
            ->firstOrFail();
    }

    public function update($id, array $data)
    {
        return AreaType::where('id', $id)->update($data);
    }

    public function toggleStatus($id)
    {
        $item = AreaType::findOrFail($id);
        return $item->update(['is_active' => !$item->is_active]);
    }

    public function softDelete($id)
    {
        return AreaType::where('id', $id)->update([
            'is_deleted' => 1,
            'is_active'  => 0
        ]);
    }
}

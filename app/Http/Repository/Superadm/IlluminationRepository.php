<?php

namespace App\Http\Repository\Superadm;

use App\Models\Illumination;

class IlluminationRepository
{
    public function getAll()
    {
        return Illumination::where('is_deleted', 0)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function existsByName($name, $ignoreId = null)
    {
        $query = Illumination::where('illumination_name', $name)
            ->where('is_deleted', 0);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    public function store(array $data)
    {
        return Illumination::create($data);
    }

    public function find($id)
    {
        return Illumination::where('id', $id)
            ->where('is_deleted', 0)
            ->firstOrFail();
    }

    public function update($id, array $data)
    {
        return Illumination::where('id', $id)->update($data);
    }

    public function toggleStatus($id)
    {
        $item = Illumination::findOrFail($id);
        return $item->update(['is_active' => !$item->is_active]);
    }

    public function softDelete($id)
    {
        return Illumination::where('id', $id)->update([
            'is_deleted' => 1,
            'is_active'  => 0
        ]);
    }
}

<?php

namespace App\Http\Repository\Superadm;

use App\Models\HomeSlider;

class HomeSliderRepository
{
    public function list()
    {
        return HomeSlider::where('is_deleted', 0)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function store(array $data)
    {
        return HomeSlider::create($data);
    }

    public function toggleStatus($id)
    {
        $slider = HomeSlider::findOrFail($id);
        $slider->update(['is_active' => !$slider->is_active]);
    }

    public function softDelete($id)
    {
        return HomeSlider::where('id', $id)
            ->update(['is_deleted' => 1, 'is_active' => 0]);
    }

    public function find($id)
    {
        return HomeSlider::where('id', $id)
            ->where('is_deleted', 0)
            ->firstOrFail();
    }

    public function update($id, array $data)
    {
        return HomeSlider::where('id', $id)->update($data);
    }

}

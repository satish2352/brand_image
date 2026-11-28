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

    // Every write below drops the home page's slider cache. Eviction lives here
    // rather than in a model event because softDelete() and update() go through
    // the query builder, which never fires Eloquent events.

    public function store(array $data)
    {
        $slider = HomeSlider::create($data);
        HomeSlider::forgetCache();

        return $slider;
    }

    public function toggleStatus($id)
    {
        $slider = HomeSlider::findOrFail($id);
        $slider->update(['is_active' => !$slider->is_active]);
        HomeSlider::forgetCache();
    }

    public function softDelete($id)
    {
        $rows = HomeSlider::where('id', $id)
            ->update(['is_deleted' => 1, 'is_active' => 0]);
        HomeSlider::forgetCache();

        return $rows;
    }

    public function find($id)
    {
        return HomeSlider::where('id', $id)
            ->where('is_deleted', 0)
            ->firstOrFail();
    }

    public function update($id, array $data)
    {
        $rows = HomeSlider::where('id', $id)->update($data);
        HomeSlider::forgetCache();

        return $rows;
    }
}

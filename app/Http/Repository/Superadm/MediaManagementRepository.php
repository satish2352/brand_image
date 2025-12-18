<?php

namespace App\Http\Repository\Superadm;

use App\Models\MediaManagement;
use Illuminate\Support\Facades\DB;

class MediaManagementRepository
{
    public function getAll()
    {
        return DB::table('media_management as m')
            ->leftJoin('tbl_location as state', 'state.location_id', '=', 'm.state_id')
            ->leftJoin('tbl_location as district', 'district.location_id', '=', 'm.district_id')
            ->leftJoin('tbl_location as city', 'city.location_id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
            ->select([
                'm.id',
                'm.media_code',
                'm.media_title',
                'm.price',
                'm.is_active',
                'm.created_at',

                'state.name as state_name',
                'district.name as district_name',
                'city.name as city_name',
                'a.common_stdiciar_name as area_name',
                'c.category_name',
            ])
            ->where('m.is_deleted', 0)
            ->orderBy('m.id', 'desc')
            ->get();
    }

    public function store(array $data)
    {
        return MediaManagement::create($data);
    }

    public function update($id, array $data)
    {
        return MediaManagement::where('id', $id)->update($data);
    }

    public function find($id)
    {
        return MediaManagement::findOrFail($id);
    }

    public function toggleStatus($id)
    {
        $media = $this->find($id);
        $media->update(['is_active' => !$media->is_active]);
    }

    public function softDelete($id)
    {
        return MediaManagement::where('id', $id)->update([
            'is_deleted' => 1,
            'is_active'  => 0
        ]);
    }
}

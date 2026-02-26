<?php

namespace App\Http\Repository\Superadm\Master;

use App\Models\Area;
use Illuminate\Support\Facades\DB;

class AreaRepository
{
    /**
     * Get All Areas (Joined with State, District, City)
     */
    public function getAllAreas()
    {
        return DB::table('areas as a')
            ->join('states as s', 's.id', '=', 'a.state_id')
            ->join('districts as d', 'd.id', '=', 'a.district_id')
            ->join('cities as c', 'c.id', '=', 'a.city_id')
            ->where('a.is_deleted', 0)
            ->select(
                'a.id',
                'a.area_name',
                'a.common_stdiciar_name',
                // 'a.latitude',
                // 'a.longitude',
                'a.is_active',
                's.state_name',
                'd.district_name',
                'c.city_name'
            )
            ->orderByDesc('a.id')
            ->get();
    }

    /**
     * Check duplicate before store
     */
    public function areaExists($stateId, $districtId, $cityId, $areaName)
    {
        return Area::where([
            'state_id'    => $stateId,
            'district_id' => $districtId,
            'city_id'     => $cityId,
            'area_name'   => $areaName,
            'is_deleted'  => 0
        ])->exists();
    }

    /**
     * Store new area
     */
    public function store(array $data)
    {
        return Area::create([
            'state_id'              => $data['state_id'],
            'district_id'           => $data['district_id'],
            'city_id'               => $data['city_id'],
            'area_name'             => $data['area_name'],
            'common_stdiciar_name'  => $data['common_stdiciar_name'],
        ]);
    }

    /**
     * Get area by id
     */
    public function getById($id)
    {
        return DB::table('areas as a')
            ->join('states as s', 's.id', '=', 'a.state_id')
            ->join('districts as d', 'd.id', '=', 'a.district_id')
            ->join('cities as c', 'c.id', '=', 'a.city_id')
            ->where('a.id', $id)
            ->where('a.is_deleted', 0)
            ->select(
                'a.*',
                's.state_name',
                'd.district_name',
                'c.city_name'
            )
            ->first();
    }


    /**
     * Check duplicate before updating (skip same record)
     */
    public function areaExistsForUpdate($stateId, $districtId, $cityId, $areaName, $id)
    {
        return Area::where([
            'state_id'    => $stateId,
            'district_id' => $districtId,
            'city_id'     => $cityId,
            'area_name'   => $areaName,
            'is_deleted'  => 0
        ])->where('id', '!=', $id)->exists();
    }

    /**
     * Update area
     */
    public function update($id, array $data)
    {
        return Area::where('id', $id)->update([
            'state_id'              => $data['state_id'],
            'district_id'           => $data['district_id'],
            'city_id'               => $data['city_id'],
            'area_name'             => $data['area_name'],
            'common_stdiciar_name'  => $data['common_stdiciar_name'],
            // 'latitude'              => $data['latitude'],
            // 'longitude'             => $data['longitude'],
        ]);
    }

    /**
     * Toggle Status
     */
    public function toggleStatus($id)
    {
        $area = Area::findOrFail($id);
        return $area->update([
            'is_active' => !$area->is_active
        ]);
    }

    /**
     * Soft delete
     */
    public function softDelete($id)
    {
        return Area::where('id', $id)->update([
            'is_deleted' => 1,
            'is_active'  => 0
        ]);
    }

    public function isAreaUsedInMedia($areaId)
    {
        return Area::where('id', $areaId)
            ->whereHas('media', function ($q) {
                $q->where('is_deleted', 0);
            })
            ->exists();
    }
}

<?php

namespace App\Http\Repository\Superadm;

use App\Models\Area;
use Illuminate\Support\Facades\DB;

class AreaRepository
{
    public function getAllAreas()
    {
        return DB::table('areas as a')
            ->join('tbl_location as s', 's.location_id', '=', 'a.state_id')
            ->join('tbl_location as d', 'd.location_id', '=', 'a.district_id')
            ->join('tbl_location as c', 'c.location_id', '=', 'a.city_id')
            ->where('a.is_deleted', 0)
            ->select(
                'a.id',
                'a.common_stdiciar_name as area_name',
                'a.is_active',
                's.name as state_name',
                'd.name as district_name',
                'c.name as city_name'
            )
            ->orderBy('a.id', 'desc')
            ->get();
    }


    /* =========================
       CHECK DUPLICATE
    ========================== */
    public function areaExists($stateId, $districtId, $cityId, $areaName)
    {
        return Area::where([
            'state_id'    => $stateId,
            'district_id' => $districtId,
            'city_id'     => $cityId,
            'area_name'   => $areaName,
            'is_deleted'  => 0,
        ])->exists();
    }

    /* =========================
       STORE
    ========================== */
    public function store(array $data)
    {
        return Area::create([
            'state_id'    => $data['state_id'],
            'district_id' => $data['district_id'],
            'city_id'     => $data['city_id'],
            'area_name'   => $data['area_name'],
            'common_stdiciar_name'   => $data['common_stdiciar_name'],

        ]);
    }


    /* ===== GET BY ID ===== */
    public function getById($id)
    {
        return Area::where('id', $id)
            ->where('is_deleted', 0)
            ->firstOrFail();
    }

    /* ===== DUPLICATE CHECK (EXCEPT CURRENT) ===== */
    public function areaExistsForUpdate($stateId, $districtId, $cityId, $areaName, $id)
    {
        return Area::where([
            'state_id'    => $stateId,
            'district_id' => $districtId,
            'city_id'     => $cityId,
            'area_name'   => $areaName,
            'is_deleted'  => 0,
        ])->where('id', '!=', $id)->exists();
    }

    /* ===== UPDATE ===== */
    public function update($id, array $data)
    {
        return Area::where('id', $id)->update([
            'state_id'               => $data['state_id'],
            'district_id'            => $data['district_id'],
            'city_id'                => $data['city_id'],
            'area_name'              => $data['area_name'],
            'common_stdiciar_name'   => $data['common_stdiciar_name'],
        ]);
    }
    /* ===== TOGGLE STATUS ===== */
    public function toggleStatus($id)
    {
        $area = Area::findOrFail($id);

        return $area->update([
            'is_active' => !$area->is_active
        ]);
    }

    /* ===== SOFT DELETE ===== */
    public function softDelete($id)
    {
        return Area::where('id', $id)->update([
            'is_deleted' => 1,
            'is_active'  => 0
        ]);
    }
}

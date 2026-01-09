<?php

namespace App\Http\Repository\Website;

use Illuminate\Support\Facades\DB;

class LocationRepository
{

    public function getDistrictsByState($stateId)
    {
        return DB::table('tbl_location')
            ->where('location_type', 2)   // District
            ->where('parent_id', $stateId)
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();
    }

    // public function getCitiesByDistrict($districtId)
    // {
    //     return DB::table('tbl_location')
    //         ->where('location_type', 4)   // City
    //         ->where('parent_id', $districtId)
    //         ->where('is_active', 1)
    //         ->orderBy('name')
    //         ->get();
    // }

    public function getCitiesByDistrict($districtId)
    {
        return DB::table('tbl_location as city')
            ->join('tbl_location as taluka', 'taluka.location_id', '=', 'city.parent_id')
            ->where('city.location_type', 4)    // City/Village
            ->where('taluka.parent_id', $districtId) // Taluka belongs to this District
            ->where('city.is_active', 1)
            ->orderBy('city.name')
            ->select('city.location_id', 'city.name')
            ->get();
    }


    public function getAreasByCity($cityId)
    {
        return DB::table('areas')
            ->where('city_id', $cityId)
            ->where('is_active', 1)
            ->where('is_deleted', 0)
            ->orderBy('area_name')
            ->get();
    }
}

<?php

namespace App\Http\Repository\Superadm;

use App\Models\City;
use Illuminate\Support\Facades\DB;

class CityRepository
{
    /**
     * Get All Cities (Joined with State, District)
     */
    public function getAllCities()
    {
        return DB::table('cities as c')
            ->join('states as s', 's.id', '=', 'c.state_id')
            ->join('districts as d', 'd.id', '=', 'c.district_id')
            ->where('c.is_deleted', 0)
            ->select(
                'c.id',
                'c.city_name',
                'c.latitude',
                'c.longitude',
                'c.is_active',
                's.state_name',
                'd.district_name'
            )
            ->orderByDesc('c.id')
            ->get();
    }

    /**
     * Duplicate Check
     */
    public function cityExists($stateId, $districtId, $cityName)
    {
        return City::where('state_id', $stateId)
            ->where('district_id', $districtId)
            ->whereRaw('LOWER(city_name) = ?', [strtolower($cityName)])
            ->where('is_deleted', 0)
            ->where('is_active', 1)
            ->exists();
    }


    /**
     * Store City
     */
    public function store(array $data)
    {
        return City::create($data);
    }

    /**
     * Get City By Id
     */

    public function getById($id)
    {
        return DB::table('cities as c')
            ->join('states as s', 's.id', '=', 'c.state_id')
            ->join('districts as d', 'd.id', '=', 'c.district_id')

            ->where('c.id', $id)
            ->where('c.is_deleted', 0)
            ->select(
                'c.*',
                's.state_name',
                'd.district_name',

            )
            ->first();
    }

    /**
     * Update
     */
    public function update($id, array $data)
    {
        return City::where('id', $id)->update($data);
    }

    /**
     * Toggle Status
     */
    public function toggleStatus($id)
    {
        $city = City::findOrFail($id);
        return $city->update([
            'is_active' => !$city->is_active
        ]);
    }

    /**
     * Soft delete
     */
    public function softDelete($id)
    {
        return City::where('id', $id)->update([
            'is_deleted' => 1,
            'is_active'  => 0
        ]);
    }
}

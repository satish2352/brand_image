<?php

namespace App\Http\Repository\Superadm\Master;

use App\Models\City;
use App\Support\LocationCache;
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
        $city = City::create($data);
        LocationCache::forgetCities($city->district_id);

        return $city;
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
        // Keep the previous district so a moved city drops out of its old bucket too.
        $oldDistrictId = City::where('id', $id)->value('district_id');

        $updated = City::where('id', $id)->update($data);

        LocationCache::forgetCities($oldDistrictId, $data['district_id'] ?? null);

        return $updated;
    }

    /**
     * Toggle Status
     */
    public function toggleStatus($id)
    {
        $city = City::findOrFail($id);
        $updated = $city->update([
            'is_active' => !$city->is_active
        ]);

        LocationCache::forgetCities($city->district_id);

        return $updated;
    }

    /**
     * Soft delete
     */
    public function deleteCity($id)
    {
        $districtId = City::where('id', $id)->value('district_id');

        $deleted = City::where('id', $id)->update([
            'is_deleted' => 1,
            'is_active'  => 0
        ]);

        LocationCache::forgetCities($districtId);

        return $deleted;
    }

    public function isCityUsedInMedia($cityId)
    {
        return City::where('id', $cityId)
            ->whereHas('media', function ($q) {
                $q->where('is_deleted', 0);
            })
            ->exists();
    }
}

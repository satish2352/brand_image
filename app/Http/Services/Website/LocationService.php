<?php

namespace App\Http\Services\Website;

use App\Http\Repository\Website\LocationRepository;

class LocationService
{
    protected LocationRepository $repo;

    public function __construct(LocationRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getDistrictsByState($stateId)
    {
        return $this->repo->getDistrictsByState($stateId);
    }

    public function getCitiesByDistrict($districtId)
    {
        return $this->repo->getCitiesByDistrict($districtId);
    }

    public function getAreasByCity($cityId)
    {
        return $this->repo->getAreasByCity($cityId);
    }
}

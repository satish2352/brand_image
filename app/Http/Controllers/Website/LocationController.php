<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Services\Website\LocationService;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    protected LocationService $service;

    public function __construct(LocationService $service)
    {
        $this->service = $service;
    }

    public function getDistricts(Request $request)
    {
        return response()->json(
            $this->service->getDistrictsByState($request->state_id)
        );
    }

    public function getCities(Request $request)
    {
        return response()->json(
            $this->service->getCitiesByDistrict($request->district_id)
        );
    }

    public function getAreas(Request $request)
    {
        return response()->json(
            $this->service->getAreasByCity($request->city_id)
        );
    }
}

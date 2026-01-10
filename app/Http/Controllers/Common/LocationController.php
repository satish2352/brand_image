<?php

namespace App\Http\Controllers\Common;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LocationController extends Controller
{
    public function getStates()
    {
        return DB::table('states')
            ->where(['is_active' => 1, 'is_deleted' => 0])
            ->orderBy('state_name')
            ->get(['id', 'state_name']);
    }

    public function getDistricts(Request $request)
    {
        return DB::table('districts')
            ->where(['state_id' => $request->state_id, 'is_active' => 1, 'is_deleted' => 0])
            ->orderBy('district_name')
            ->get(['id', 'district_name']);
    }

    public function getCities(Request $request)
    {
        return DB::table('cities')
            ->where(['district_id' => $request->district_id, 'is_active' => 1, 'is_deleted' => 0])
            ->orderBy('city_name')
            ->get(['id', 'city_name']);
    }

    public function getAreas(Request $request)
    {
        return DB::table('areas')
            ->where(['city_id' => $request->city_id, 'is_active' => 1, 'is_deleted' => 0])
            ->orderBy('area_name')
            ->get(['id', 'area_name']);
    }
}

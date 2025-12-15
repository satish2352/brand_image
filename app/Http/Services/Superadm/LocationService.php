<?php
namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\LocationRepository;
use Exception;

class LocationService
{
    protected $repo;
    public function __construct()
    {
        $this->repo = new LocationRepository();
    }

    public function list()
    {
        return $this->repo->list();
    }

    public function save($req)
    {
        $district = \DB::table('districts')->where('id', $req->district_id)->where('is_deleted', 0)->first();
        $city = \DB::table('cities')->where('id', $req->city_id)->where('is_deleted', 0)->first();

        if (!$district || $district->state_id != $req->state_id) {
            throw new Exception("Selected district is invalid for chosen state.");
        }
        if (!$city || $city->district_id != $req->district_id) {
            throw new Exception("Selected city is invalid for chosen district.");
        }

        $type = $req->type_id;

        // Duplicate check
        $exists = \DB::table('locations')
            ->where('is_deleted', 0)
            ->where('state_id', $req->state_id)
            ->where('district_id', $req->district_id)
            ->where('city_id', $req->city_id)
            ->where('radius', $req->radius)
            ->where('type_id', $type)
            ->exists();

        if ($exists) {
            throw new Exception("This radius & type already exists for this location.");
        }

        $data = [
            'state_id' => $req->state_id,
            'district_id' => $req->district_id,
            'city_id' => $req->city_id,
            'radius' => $req->radius,
            'type_id' => $type,
        ];

        return $this->repo->save($data);
    }


    public function edit($id)
    {
        return $this->repo->edit($id);
    }

    public function update($req)
    {
        $district = \DB::table('districts')->where('id', $req->district_id)->where('is_deleted', 0)->first();
        $city = \DB::table('cities')->where('id', $req->city_id)->where('is_deleted', 0)->first();

        if (!$district || $district->state_id != $req->state_id) {
            throw new Exception("Selected district is invalid for chosen state.");
        }
        if (!$city || $city->district_id != $req->district_id) {
            throw new Exception("Selected city is invalid for chosen district.");
        }

        $type = $req->type_id;

        $exists = \DB::table('locations')
            ->where('is_deleted', 0)
            ->where('id', '!=', $req->id)
            ->where('state_id', $req->state_id)
            ->where('district_id', $req->district_id)
            ->where('city_id', $req->city_id)
            ->where('radius', $req->radius)
            ->where('type_id', $type)
            ->exists();

        if ($exists) {
            throw new Exception("This radius & type already exists for this location.");
        }

        $data = [
            'state_id' => $req->state_id,
            'district_id' => $req->district_id,
            'city_id' => $req->city_id,
            'radius' => $req->radius,
            'type_id' => $req->type_id,
        ];

        return $this->repo->update($data, $req->id);
    }


    public function delete($req)
    {
        $id = base64_decode($req->id);
        return $this->repo->delete($id);
    }

    public function find($id)
    {
        try {
            return $this->repo->edit($id);
        } catch (Exception $e) {
            \Log::error("LocationService find error: " . $e->getMessage());
            return null;
        }
    }
}

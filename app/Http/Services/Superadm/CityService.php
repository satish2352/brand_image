<?php

namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\CityRepository;
use Exception;

class CityService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new CityRepository();
    }

    public function list()
    {
        return $this->repo->list();
    }

    public function save($req)
    {
        // optional: ensure district belongs to provided state
        $district = \DB::table('districts')->where('id', $req->district_id)->where('is_deleted',0)->first();
        if (!$district || $district->state_id != $req->state_id) {
            throw new \Exception("Selected district is invalid for chosen state.");
        }
        return $this->repo->save([
            'city' => $req->city,
            'state_id' => $req->state_id,
            'district_id' => $req->district_id,
        ]);
    }

    public function edit($id)
    {
        return $this->repo->edit($id);
    }

    public function update($req)
    {
        $district = \DB::table('districts')->where('id', $req->district_id)->where('is_deleted',0)->first();
        if (!$district || $district->state_id != $req->state_id) {
            throw new \Exception("Selected district is invalid for chosen state.");
        }
        return $this->repo->update([
            'city' => $req->city,
            'state_id' => $req->state_id,
            'district_id' => $req->district_id,
        ], $req->id);
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
            \Log::error("CityService find error: " . $e->getMessage());
            return null;
        }
    }

}

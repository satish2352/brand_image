<?php

namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\RadiusRepository;
use Exception;

class RadiusService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new RadiusRepository();
    }

    public function list()
    {
        return $this->repo->list();
    }

    public function save($req)
    {
        // Duplicate check
        $exists = \DB::table('radius_master')
            ->where('radius', $req->radius)
            ->where('is_deleted', 0)
            ->exists();

        if ($exists) {
            throw new Exception("This radius already exists.");
        }

        // Validate numeric range inside radius
        if (preg_match('/(\d+)\s*km\s*-\s*(\d+)\s*km/i', $req->radius, $m)) {
            if ((int)$m[1] > (int)$m[2]) {
                throw new Exception("Start range cannot be greater than end range.");
            }
        }

        $data = [
            'radius' => $req->radius,
        ];

        return $this->repo->save($data);
    }

    public function edit($id)
    {
        return $this->repo->edit($id);
    }

    public function update($req)
    {
        $exists = \DB::table('radius_master')
            ->where('radius', $req->radius)
            ->where('id', '!=', $req->id)
            ->where('is_deleted', 0)
            ->exists();

        if ($exists) {
            throw new Exception("This radius already exists.");
        }

        if (preg_match('/(\d+)\s*km\s*-\s*(\d+)\s*km/i', $req->radius, $m)) {
            if ((int)$m[1] > (int)$m[2]) {
                throw new Exception("Start range cannot be greater than end range.");
            }
        }

        $data = [
            'radius' => $req->radius,
        ];

        return $this->repo->update($data, $req->id);
    }

    public function delete($req)
    {
        $id = base64_decode($req->id);
        return $this->repo->delete($id);
    }
}

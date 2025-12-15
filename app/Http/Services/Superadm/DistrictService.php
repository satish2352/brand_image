<?php
namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\DistrictRepository;
use Exception;
use DB;

class DistrictService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new DistrictRepository();
    }

    public function list()
    {
        return $this->repo->list();
    }

    public function save($req)
    {
        $data = [
            'state_id' => $req->state_id,
            'district' => $req->district,
        ];
        return $this->repo->save($data);
    }

    public function edit($id)
    {
        return $this->repo->edit($id);
    }

    public function update($req)
    {
        $data = [
            'state_id' => $req->state_id,
            'district' => $req->district,
        ];
        return $this->repo->update($data, $req->id);
    }

    public function delete($req)
    {
        $id = base64_decode($req->id);

        // prevent delete if cities exist in this district
        $count = DB::table('cities')->where('district_id', $id)->where('is_deleted',0)->count();
        if ($count > 0) {
            throw new Exception("Cannot delete this district because it has cities assigned.");
        }

        return $this->repo->delete($id);
    }

    public function getByState($stateId)
    {
        return $this->repo->getByState($stateId);
    }

    public function find($id)
    {
        try {
            return $this->repo->edit($id);
        } catch (Exception $e) {
            \Log::error("DistrictService find error: " . $e->getMessage());
            return null;
        }
    }

}

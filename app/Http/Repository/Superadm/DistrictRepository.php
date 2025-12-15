<?php
namespace App\Http\Repository\Superadm;

use App\Models\District;

class DistrictRepository
{
    public function list()
    {
        return District::where('is_deleted', 0)->with('state')->orderBy('id', 'desc')->get();
    }

    public function save($data)
    {
        return District::create($data);
    }

    public function edit($id)
    {
        return District::find($id);
    }

    public function update($data, $id)
    {
        return District::where('id', $id)->update($data);
    }

    public function delete($id)
    {
        return District::where('id', $id)->update(['is_deleted' => 1]);
    }

    public function getByState($stateId)
    {
        return District::where('state_id', $stateId)->where('is_deleted',0)->where('is_active',1)->orderBy('district')->get();
    }
}

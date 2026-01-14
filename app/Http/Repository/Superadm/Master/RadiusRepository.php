<?php

namespace App\Http\Repository\Superadm\Master;

use App\Models\RadiusMaster;

class RadiusRepository
{
    public function list()
    {
        return RadiusMaster::where('is_deleted', 0)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function save($data)
    {
        return RadiusMaster::create($data);
    }

    public function edit($id)
    {
        return RadiusMaster::find($id);
    }

    public function update($data, $id)
    {
        return RadiusMaster::where('id', $id)->update($data);
    }

    public function delete($id)
    {
        return RadiusMaster::where('id', $id)->update(['is_deleted' => 1]);
    }
}

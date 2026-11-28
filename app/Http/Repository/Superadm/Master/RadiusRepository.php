<?php

namespace App\Http\Repository\Superadm\Master;

use App\Models\RadiusMaster;
use App\Support\MasterCache;

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
        $result = RadiusMaster::create($data);
        MasterCache::forgetRadius();

        return $result;
    }

    public function edit($id)
    {
        return RadiusMaster::find($id);
    }

    public function update($data, $id)
    {
        $result = RadiusMaster::where('id', $id)->update($data);
        MasterCache::forgetRadius();

        return $result;
    }

    public function delete($id)
    {
        $result = RadiusMaster::where('id', $id)->update(['is_deleted' => 1]);
        MasterCache::forgetRadius();

        return $result;
    }
}

<?php
namespace App\Http\Repository\Superadm;

use App\Models\Location;

class LocationRepository
{
    public function list()
    {
        return Location::where('is_deleted', 0)
            ->with(['state','district','city'])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function save($data)
    {
        return Location::create($data);
    }

    public function edit($id)
    {
        return Location::find($id);
    }

    public function update($data, $id)
    {
        return Location::where('id', $id)->update($data);
    }

    public function delete($id)
    {
        return Location::where('id', $id)->update(['is_deleted' => 1]);
    }
}

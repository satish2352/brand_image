<?php
namespace App\Http\Repository\Superadm;

use App\Models\City;

class CityRepository
{
    public function list()
    {
        return City::where('is_deleted', 0)
            ->with(['state','district'])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function save($data)
    {
        return City::create($data);
    }

    public function edit($id)
    {
        return City::find($id);
    }

    public function update($data, $id)
    {
        return City::where('id', $id)->update($data);
    }

    public function delete($id)
    {
        return City::where('id', $id)->update(['is_deleted' => 1]);
    }
}

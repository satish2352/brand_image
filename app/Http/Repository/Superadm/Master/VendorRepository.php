<?php

namespace App\Http\Repository\Superadm\Master;

use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class VendorRepository
{
    public function getAll()
    {
        return DB::table('vendors as v')
            ->leftJoin('states as s', 's.id', '=', 'v.state_id')
            ->leftJoin('districts as d', 'd.id', '=', 'v.district_id')
            ->leftJoin('cities as c', 'c.id', '=', 'v.city_id')
            ->where('v.is_deleted', 0)
            ->select(
                'v.*',
                's.state_name as state_name',
                'd.district_name as district_name',
                'c.city_name as city_name'
            )
            ->orderBy('v.id', 'desc')
            ->get();
    }

    public function existsByCode($vendorCode, $ignoreId = null)
    {
        $q = Vendor::where('vendor_code', $vendorCode)
            ->where('is_deleted', 0);

        if ($ignoreId) {
            $q->where('id', '!=', $ignoreId);
        }

        return $q->exists();
    }

    public function store(array $data)
    {
        return Vendor::create($data);
    }

    public function find($id)
    {
        return Vendor::where('id', $id)
            ->where('is_deleted', 0)
            ->firstOrFail();
    }

    public function update($id, array $data)
    {
        return Vendor::where('id', $id)->update($data);
    }

    public function toggleStatus($id)
    {
        $vendor = Vendor::findOrFail($id);
        return $vendor->update(['is_active' => !$vendor->is_active]);
    }

    public function softDelete($id)
    {
        return Vendor::where('id', $id)->update([
            'is_deleted' => 1,
            'is_active'  => 0
        ]);
    }
}

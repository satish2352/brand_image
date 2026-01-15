<?php

namespace App\Http\Repository\Superadm;

use App\Models\MediaManagement;
use Illuminate\Support\Facades\DB;

class MediaManagementRepository
{
    // public function getAll($search = null)
    // {
    //     $perPage = config('fileConstants.PAGINATION', 10);

    //     $query = DB::table('media_management as m')
    //         ->leftJoin('states as s', 's.id', '=', 'm.state_id')
    //         ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
    //         ->leftJoin('cities as cty', 'cty.id', '=', 'm.city_id')
    //         ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
    //         ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
    //         ->leftJoin('vendors as v', 'v.id', '=', 'm.vendor_id')
    //         ->select([
    //             'm.id',
    //             'm.media_code',
    //             'm.media_title',
    //             'm.price',
    //             'm.is_active',
    //             'v.vendor_name',
    //             'c.category_name',
    //             's.state_name',
    //             'd.district_name',
    //             'cty.city_name',
    //             'a.area_name',
    //         ])
    //         ->where('m.is_deleted', 0);

    //     if (!empty($search)) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('m.media_title', 'like', "%$search%")
    //                 ->orWhere('v.vendor_name', 'like', "%$search%")
    //                 ->orWhere('c.category_name', 'like', "%$search%");
    //         });
    //     }

    //     return $query->orderBy('m.id', 'desc')->paginate($perPage);
    // }
    public function getAll($filters = [])
    {
        $perPage = config('fileConstants.PAGINATION', 10);

        $query = DB::table('media_management as m')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('cities as cty', 'cty.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
            ->leftJoin('vendors as v', 'v.id', '=', 'm.vendor_id')
            ->select([
                'm.id',
                'm.media_code',
                'm.media_title',
                'm.price',
                'm.is_active',
                'v.vendor_name',
                'c.category_name',
                's.state_name',
                'd.district_name',
                'cty.city_name',
                'a.area_name'
            ])
            ->where('m.is_deleted', 0);

        // 🔍 FILTERS
        if (!empty($filters['vendor_id'])) {
            $query->where('m.vendor_id', $filters['vendor_id']);
        }
        if (!empty($filters['category_id'])) {
            $query->where('m.category_id', $filters['category_id']);
        }
        if (!empty($filters['month'])) {
            $query->whereMonth('m.created_at', $filters['month']);
        }
        if (!empty($filters['year'])) {
            $query->whereYear('m.created_at', $filters['year']);
        }
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->whereBetween(DB::raw('DATE(m.created_at)'), [
                $filters['from_date'],
                $filters['to_date']
            ]);
        }

        return $query->orderBy('m.id', 'desc')->paginate($perPage);
    }

    public function store(array $data)
    {
        return MediaManagement::create($data);
    }
    public function update($id, array $data)
    {
        return MediaManagement::where('id', $id)->update($data);
    }
    public function find($id)
    {
        return MediaManagement::findOrFail($id);
    }
    public function toggleStatus($id)
    {
        $media = $this->find($id);
        $media->update(['is_active' => !$media->is_active]);
    }
    public function softDelete($id)
    {
        return MediaManagement::where('id', $id)->update([
            'is_deleted' => 1,
            'is_active'  => 0
        ]);
    }
    public function getDetailsById($id)
    {
        return DB::table('media_management as mm')
            ->leftJoin('states as st', 'st.id', '=', 'mm.state_id')
            ->leftJoin('districts as dt', 'dt.id', '=', 'mm.district_id')
            ->leftJoin('cities as ct', 'ct.id', '=', 'mm.city_id')
            ->leftJoin('areas as ar', 'ar.id', '=', 'mm.area_id')
            ->leftJoin('category as cat', 'cat.id', '=', 'mm.category_id')
            ->leftJoin('facing_direction as fd', 'fd.id', '=', 'mm.facing_id')
            ->leftJoin('illumination as il', 'il.id', '=', 'mm.illumination_id')
            ->leftJoin('radius_master as rm', 'rm.id', '=', 'mm.radius_id')
            ->leftJoin('media_images as mi', function ($join) {
                $join->on('mi.media_id', '=', 'mm.id')
                    ->where('mi.is_deleted', 0);
            })
            ->where('mm.id', $id)
            ->where('mm.is_deleted', 0)
            ->select(
                'mm.*',
                'mm.area_type',
                'cat.category_name',
                'st.state_name',
                'dt.district_name',
                'ct.city_name',
                'ar.area_name',
                'fd.facing_name',
                'il.illumination_name',
                'rm.radius',
                'mi.id as image_id',
                'mi.images as image_name'
            )
            ->get();
    }
}

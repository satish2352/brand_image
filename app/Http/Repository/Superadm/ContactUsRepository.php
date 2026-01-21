<?php

namespace App\Http\Repository\Superadm;

use App\Models\ContactUs;

class ContactUsRepository
{
    public function list()
    {
        return ContactUs::leftJoin('media_management as m', 'contact_us.media_id', '=', 'm.id')
            ->leftJoin('category as c', 'm.category_id', '=', 'c.id')
            ->where('contact_us.is_delete', 0)
            ->orderBy('contact_us.id', 'desc')
            ->select(
                'contact_us.*',
                'm.media_title as media_name',
                'c.category_name'
            )
            ->get();
    }

    public function delete($id)
    {
        return ContactUs::where('id', $id)
            ->update(['is_delete' => 1]);
    }

    public function findById($id)
    {
        return ContactUs::leftJoin('media_management as m', 'contact_us.media_id', '=', 'm.id')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('cities as cty', 'cty.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('category as cat', 'cat.id', '=', 'm.category_id')
            ->leftJoin('vendors as v', 'v.id', '=', 'm.vendor_id')
            ->leftJoin('facing_direction as fd', 'fd.id', '=', 'm.facing_id')
            ->leftJoin('illumination as il', 'il.id', '=', 'm.illumination_id')
            ->where('contact_us.id', $id)
            ->where('contact_us.is_delete', 0)
            ->select([
                'contact_us.*',
                'contact_us.media_id',

                // CATEGORY
                'cat.category_name',

                // MEDIA
                'm.media_title as media_name',
                'm.media_title',
                'm.media_code',
                'm.width',
                'm.height',
                'm.price',
                'm.address',

                // LOCATION
                's.state_name',
                'd.district_name',
                'cty.city_name',
                'a.area_name',

                // VENDOR
                'v.vendor_name',

                // MEDIA EXTRA
                'fd.facing_name',
                'il.illumination_name',
            ])
            ->first();
    }


}

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
}

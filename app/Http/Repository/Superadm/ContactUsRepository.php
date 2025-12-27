<?php

namespace App\Http\Repository\Superadm;

use App\Models\ContactUs;

class ContactUsRepository
{
    public function list()
    {
        return ContactUs::where('is_delete', 0)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function delete($id)
    {
        return ContactUs::where('id', $id)
            ->update(['is_delete' => 1]);
    }
}

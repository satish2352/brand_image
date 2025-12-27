<?php

namespace App\Http\Repository\Website;

use App\Models\ContactUs;

class ContactRepository
{
    public function store(array $data)
    {
        return ContactUs::create($data);
    }
}

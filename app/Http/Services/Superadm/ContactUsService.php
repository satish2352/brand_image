<?php

namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\ContactUsRepository;

class ContactUsService
{
    protected $repo;

    public function __construct(ContactUsRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list()
    {
        return $this->repo->list();
    }

    public function delete($id)
    {
        return $this->repo->delete($id);
    }
}

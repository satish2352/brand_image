<?php

namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\WebsiteUserRepository;

class WebsiteUserService
{
    protected $repo;

    public function __construct(WebsiteUserRepository $repo)
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
    public function toggleStatus($id)
    {
        return $this->repo->toggleStatus($id);
    }

    public function getById($id)
    {
        return $this->repo->getById($id);
    }
}

<?php

namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\CampaingRepository;

class CampaingService
{
    protected $repo;

    public function __construct(CampaingRepository $repo)
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
}

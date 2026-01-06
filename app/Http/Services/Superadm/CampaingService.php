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
        $data_output =  $this->repo->list();

        return $data_output;
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

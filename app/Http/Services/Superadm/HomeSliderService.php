<?php

namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\HomeSliderRepository;

class HomeSliderService
{
    protected $repo;

    public function __construct(HomeSliderRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list()
    {
        return $this->repo->list();
    }

    public function store(array $data)
    {
        return $this->repo->store($data);
    }

    public function toggleStatus($id)
    {
        $this->repo->toggleStatus($id);
    }

    public function delete($id)
    {
        $this->repo->softDelete($id);
    }

    public function find($id)
    {
        return $this->repo->find($id);
    }

    public function update($id, array $data)
    {
        return $this->repo->update($id, $data);
    }

}

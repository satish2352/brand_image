<?php

namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\FinancialYearRepository;
use Exception;
use Log;

class FinancialYearService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new FinancialYearRepository();
    }

    public function list()
    {
        return $this->repo->list();
    }

    public function save($req)
    {
        $data = [
            'year' => $req->input('year'),
        ];
        return $this->repo->save($data);
    }

    public function edit($id)
    {
        return $this->repo->edit($id);
    }

    public function update($req)
    {
        $id = $req->id;
        $data = [
            'year' => $req->input('year'),
            'is_active' => $req->is_active
        ];
        return $this->repo->update($data, $id);
    }

    public function delete($req)
    {
        $id = base64_decode($req->id);
        $data = ['is_deleted' => 1];
        return $this->repo->delete($data, $id);
    }

    public function updateStatus($req)
    {
        $id = base64_decode($req->id);
        $data = ['is_active' => $req->is_active];
        return $this->repo->updateStatus($data, $id);
    }
}

<?php

// app/Http/Services/Superadm/VendorService.php
namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\VendorRepository;
use Illuminate\Support\Facades\DB;
use Exception;

class VendorService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new VendorRepository();
    }

    public function list()
    {
        $data_output = $this->repo->getAll();

        return  $data_output;
    }

    public function store(array $data)
    {
        DB::beginTransaction();
        try {
            if ($this->repo->existsByCode($data['vendor_code'])) {
                throw new Exception('Vendor code already exists');
            }

            $this->repo->store($data);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function find($id)
    {
        return $this->repo->find($id);
    }

    public function update($id, array $data)
    {
        DB::beginTransaction();
        try {
            if ($this->repo->existsByCode($data['vendor_code'], $id)) {
                throw new Exception('Vendor code already exists');
            }

            $this->repo->update($id, $data);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function toggleStatus($id)
    {
        $this->repo->toggleStatus($id);
    }

    public function delete($id)
    {
        $this->repo->softDelete($id);
    }
}

<?php

// app/Http/Services/Superadm/VendorService.php
namespace App\Http\Services\Superadm\Master;

use App\Http\Repository\Superadm\Master\VendorRepository;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\Vendor;

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
    private function vendorCodeError()
    {
        return 'Vendor Code already exists. Please enter a different Vendor Name because Vendor Code depends on Vendor Name.';
    }



    public function store(array $data)
    {
        DB::beginTransaction();
        try {

            // Active vendor check
            if ($this->repo->existsByCode($data['vendor_code'])) {
                throw new Exception($this->vendorCodeError());
            }

            // Soft-deleted vendor check
            $deletedVendor = Vendor::where('vendor_code', $data['vendor_code'])
                ->where('is_deleted', 1)
                ->first();

            if ($deletedVendor) {
                $deletedVendor->update(array_merge($data, [
                    'is_deleted' => 0,
                    'is_active'  => 1
                ]));
            } else {
                $this->repo->store($data);
            }

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
                throw new Exception($this->vendorCodeError());
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

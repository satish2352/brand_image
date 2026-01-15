<?php

namespace App\Http\Services\Superadm\Master;

use App\Http\Repository\Superadm\Master\IlluminationRepository;
use Illuminate\Support\Facades\DB;
use Exception;

class IlluminationService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new IlluminationRepository();
    }

    public function list()
    {
        return $this->repo->getAll();
    }

    public function store(array $data)
    {
        DB::beginTransaction();
        try {

            if ($this->repo->existsByName($data['illumination_name'])) {
                throw new Exception(
                    'This illumination is already created'
                );
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

            if ($this->repo->existsByName(
                $data['illumination_name'],
                $id
            )) {
                throw new Exception(
                    'This illumination is already created'
                );
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

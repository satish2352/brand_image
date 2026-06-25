<?php

namespace App\Http\Services\Superadm\Master;

use App\Http\Repository\Superadm\Master\LandmarkRepository;
use Illuminate\Support\Facades\DB;
use Exception;

class LandmarkService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new LandmarkRepository();
    }

    public function list()
    {
        return $this->repo->getAll();
    }

    public function store(array $data)
    {
        DB::beginTransaction();

        try {
            // restore deleted record with the same name
            $deleted = $this->repo->findDeletedByName($data['landmark_name']);

            if ($deleted) {
                $deleted->update([
                    'is_deleted' => 0,
                    'is_active'  => 1
                ]);

                DB::commit();
                return;
            }

            // active duplicate check
            if ($this->repo->existsByName($data['landmark_name'])) {
                throw new Exception('This landmark is already created');
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
            if ($this->repo->existsByName($data['landmark_name'], $id)) {
                throw new Exception('This landmark is already created');
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

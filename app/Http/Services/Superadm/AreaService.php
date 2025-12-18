<?php

namespace App\Http\Services\Superadm;

use Illuminate\Support\Facades\DB;
use App\Http\Repository\Superadm\AreaRepository;
use Exception;

class AreaService
{

    protected $areaRepo;

    public function __construct()
    {
        $this->areaRepo = new AreaRepository();
    }


    public function getAllAreas()
    {
        return $this->areaRepo->getAllAreas();
    }

    public function storeArea(array $data)
    {
        DB::beginTransaction();

        try {

            // ❌ Duplicate protection
            if ($this->areaRepo->areaExists(
                $data['state_id'],
                $data['district_id'],
                $data['city_id'],
                $data['area_name']
            )) {
                throw new Exception('Area already exists for selected city');
            }

            $area = $this->areaRepo->store($data);

            DB::commit();
            return $area;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /* ===== EDIT ===== */
    public function getAreaById($id)
    {
        return $this->areaRepo->getById($id);
    }

    /* ===== UPDATE ===== */
    public function updateArea($id, array $data)
    {
        DB::beginTransaction();

        try {
            if ($this->areaRepo->areaExistsForUpdate(
                $data['state_id'],
                $data['district_id'],
                $data['city_id'],
                $data['area_name'],
                $id
            )) {
                throw new Exception('Area already exists for selected city');
            }

            $this->areaRepo->update($id, $data);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    /* ===== TOGGLE STATUS ===== */
    public function toggleStatus($id)
    {
        DB::beginTransaction();

        try {
            $this->areaRepo->toggleStatus($id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /* ===== DELETE (SOFT) ===== */
    public function deleteArea($id)
    {
        DB::beginTransaction();

        try {
            $this->areaRepo->softDelete($id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

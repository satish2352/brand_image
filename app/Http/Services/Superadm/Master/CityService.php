<?php

namespace App\Http\Services\Superadm\Master;

use Illuminate\Support\Facades\DB;
use App\Http\Repository\Superadm\Master\CityRepository;
use Exception;

class CityService
{
    protected $cityRepo;

    public function __construct()
    {
        $this->cityRepo = new CityRepository();
    }

    public function getAllCities()
    {
        return $this->cityRepo->getAllCities();
    }

    public function storeCity(array $data)
    {
        DB::beginTransaction();

        try {
            if ($this->cityRepo->cityExists(
                $data['state_id'],
                $data['district_id'],
                $data['city_name']
            )) {
                throw new Exception('City already exists');
            }

            $city = $this->cityRepo->store($data);

            DB::commit();
            return $city;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getCityById($id)
    {
        return $this->cityRepo->getById($id);
    }

    public function updateCity($id, array $data)
    {
        DB::beginTransaction();

        try {
            $this->cityRepo->update($id, $data);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function toggleStatus($id)
    {
        return $this->cityRepo->toggleStatus($id);
    }
    public function deleteCity($id)
    {
        DB::beginTransaction();

        try {

            // Stop delete if city is used in media_management
            if ($this->cityRepo->isCityUsedInMedia($id)) {
                throw new Exception(
                    'This City is used in Media Management. Please delete related media first.'
                );
            }

            $this->cityRepo->deleteCity($id);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

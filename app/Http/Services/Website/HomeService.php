<?php

namespace App\Http\Services\Website;

use Illuminate\Support\Facades\DB;
use App\Http\Repository\Website\HomeRepository;
use Exception;

class HomeService
{

    protected $areaRepo;

    public function __construct()
    {
        $this->areaRepo = new HomeRepository();
    }


    public function getAllMediaCartsData()
    {
        return $this->areaRepo->getAllMediaCartsData();
    }
}

<?php

namespace App\Http\Services\Website;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Http\Repository\Website\HomeRepository;
use Illuminate\Support\Facades\DB;

class HomeService
{
    protected HomeRepository $repo;

    public function __construct(HomeRepository $repo)
    {
        $this->repo = $repo;
    }

    public function searchMedia(array $filters)
    {
        return $this->repo->searchMedia($filters);
    }
    // public function searchMedia(array $filters)
    // {
    //     // 1️⃣ Get media list (DATE FILTER ONLY)
    //     $mediaList = $this->repo->searchMedia($filters);

    //     // 2️⃣ Get booked media IDs (STATUS ONLY)
    //     $bookedMediaIds = DB::table('media_booked_date')
    //         ->where('is_active', 1)
    //         ->where('is_deleted', 0)
    //         ->pluck('media_id')
    //         ->toArray();

    //     // 3️⃣ Attach booked flag to each media
    //     foreach ($mediaList as $media) {
    //         $media->is_booked = in_array($media->id, $bookedMediaIds);
    //     }

    //     return $mediaList;
    // }

    public function getMediaDetails($mediaId)
    {
        try {
            return $this->repo->getMediaDetails($mediaId);
        } catch (Exception $e) {

            Log::error('HomeService getMediaDetails Error', [
                'media_id' => $mediaId,
                'message'  => $e->getMessage()
            ]);

            throw $e; // rethrow to controller
        }
    }
}

// namespace App\Http\Services\Website;

// use Illuminate\Support\Facades\DB;
// use App\Http\Repository\Website\HomeRepository;
// use Exception;

// class HomeService
// {

//     protected $areaRepo;

//     public function __construct()
//     {
//         $this->areaRepo = new HomeRepository();
//     }


//     public function getAllMediaCartsData()
//     {
//         return $this->areaRepo->getAllMediaCartsData();
//     }

//     public function searchMedia(array $filters)
//     {
//         return $this->areaRepo->searchMedia($filters);
//     }
// }

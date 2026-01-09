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
        // dd($filters);
        $data_output =  $this->repo->searchMedia($filters);
        // dd($data_output);
        return  $data_output;
    }
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

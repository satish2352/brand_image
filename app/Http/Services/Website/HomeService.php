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

        $data_output =  $this->repo->searchMedia($filters);

        return  $data_output;
    }
    public function getMediaDetails($mediaId)
    {
        try {
            $data_output = $this->repo->getMediaDetails($mediaId);

            return $data_output;
        } catch (Exception $e) {

            Log::error('HomeService getMediaDetails Error', [
                'media_id' => $mediaId,
                'message'  => $e->getMessage()
            ]);

            throw $e; // rethrow to controller
        }
    }

    public function getLatestOtherMediaByCategory()
    {
        return $this->repo->getLatestOtherMediaByCategory();
    }
}

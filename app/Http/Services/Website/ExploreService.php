<?php

namespace App\Http\Services\Website;

use App\Http\Repository\Website\ExploreRepository;

class ExploreService
{
    protected ExploreRepository $repo;

    public function __construct()
    {
        $this->repo = new ExploreRepository();
    }

    public function searchMedia(array $filters, int $perPage = 50, int $page = 1)
    {
        return $this->repo->searchMedia($filters, $perPage, $page);
    }

    public function getMapMarkers(array $filters)
    {
        return $this->repo->getMapMarkers($filters);
    }

    /**
     * Translate a public "sort" key into a safe (column, direction) pair.
     */
    public function resolveSort(?string $sort): array
    {
        return match ($sort) {
            'price_low'  => ['m.price', 'ASC'],
            'price_high' => ['m.price', 'DESC'],
            'oldest'     => ['m.id', 'ASC'],
            default      => ['m.id', 'DESC'], // newest
        };
    }
}

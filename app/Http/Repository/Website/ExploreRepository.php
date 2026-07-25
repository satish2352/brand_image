<?php

namespace App\Http\Repository\Website;

use Illuminate\Support\Facades\DB;

/**
 * Repository for the new multi-select Explore page (Feature 4).
 *
 * Filter logic:
 *   - WITHIN one filter group  -> OR   (whereIn)
 *   - ACROSS different groups   -> AND  (chained where)
 *
 * This is intentionally SEPARATE from HomeRepository so the existing
 * /search page is never affected.
 */
class ExploreRepository
{
    /**
     * Paginated cards.
     */
    public function searchMedia(array $filters, int $perPage = 50, int $page = 1)
    {
        return $this->buildSearchQuery($filters)
            ->orderBy($filters['_sort_col'] ?? 'm.id', $filters['_sort_dir'] ?? 'DESC')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * All matching rows (no pagination) for map markers — same filters,
     * so markers always match the result set.
     */
    public function getMapMarkers(array $filters)
    {
        return $this->buildSearchQuery($filters)
            ->orderBy('m.id', 'DESC')
            ->get();
    }

    private function buildSearchQuery(array $filters)
    {
        $query = DB::table('media_management as m')
            ->leftJoin('cities as city', 'city.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->leftJoin('category as ct', 'ct.id', '=', 'm.category_id')
            ->leftJoin('areatype as at', 'at.id', '=', 'm.areatype_id')
            ->leftJoin('highway as hw', 'hw.id', '=', 'm.highway_id')
            ->leftJoin(DB::raw('
                (SELECT media_id, MIN(images) AS first_image
                 FROM media_images
                 WHERE is_deleted = 0 AND is_active = 1
                 GROUP BY media_id
                ) mi
            '), 'mi.media_id', '=', 'm.id')
            ->where('m.is_deleted', 0)
            ->where('m.is_active', 1)
            ->select([
                'm.id',
                'm.hoarding_code',
                'm.media_title',
                'm.price',
                'm.category_id',
                'm.latitude',
                'm.longitude',
                'm.width',
                'm.height',
                'm.facing',
                'ct.category_name',
                'a.area_name',
                's.state_name',
                'd.district_name',
                'city.city_name',
                'at.areatype_name as area_type_name',
                'hw.highway_name',
                'a.common_stdiciar_name as common_area_name',
                'm.panorama_image',
                'mi.first_image',
                DB::raw('(SELECT GROUP_CONCAT(l.landmark_name SEPARATOR ", ") FROM media_landmark ml JOIN landmark l ON l.id = ml.landmark_id WHERE ml.media_id = m.id AND l.is_deleted = 0) as landmark_names'),
                DB::raw('ROUND(m.price / DAY(LAST_DAY(CURDATE())), 2) as per_day_price'),
            ]);

        /* ---------- LOCATION GROUPS (OR within / AND across) ---------- */
        $this->applyInFilter($query, 'm.state_id', $filters['state_id'] ?? null);
        $this->applyInFilter($query, 'm.district_id', $filters['district_id'] ?? null);
        $this->applyInFilter($query, 'm.city_id', $filters['city_id'] ?? null);
        $this->applyInFilter($query, 'm.area_id', $filters['area_id'] ?? null);
        $this->applyInFilter($query, 'm.category_id', $filters['category_id'] ?? null);
        $this->applyInFilter($query, 'm.areatype_id', $filters['areatype_id'] ?? null);

        /* ---------- HIGHWAY (OR within group) ---------- */
        $this->applyInFilter($query, 'm.highway_id', $filters['highway_id'] ?? null);

        /* ---------- LANDMARK (OR within group, via pivot) ---------- */
        $landmarkIds = $this->toIntArray($filters['landmark_ids'] ?? null);
        if (!empty($landmarkIds)) {
            $query->whereExists(function ($q) use ($landmarkIds) {
                $q->select(DB::raw(1))
                    ->from('media_landmark as ml')
                    ->whereColumn('ml.media_id', 'm.id')
                    ->whereIn('ml.landmark_id', $landmarkIds);
            });
        }

        /* ---------- FREE-TEXT SEARCH (asset id / location / area) ---------- */
        // Accepts one term OR a comma / newline / semicolon separated list
        // (e.g. multiple hoarding codes pasted in). A row matches when it
        // matches ANY of the terms.
        if (!empty($filters['q'])) {
            $terms = array_values(array_filter(
                array_map('trim', preg_split('/[,\n;]+/', (string) $filters['q'])),
                fn($t) => $t !== ''
            ));

            if (!empty($terms)) {
                $query->where(function ($outer) use ($terms) {
                    foreach ($terms as $term) {
                        $like = '%' . $term . '%';
                        $outer->orWhere(function ($w) use ($like) {
                            $w->where('m.hoarding_code', 'like', $like)
                                ->orWhere('m.media_title', 'like', $like)
                                ->orWhere('a.area_name', 'like', $like)
                                ->orWhere('city.city_name', 'like', $like)
                                ->orWhere('d.district_name', 'like', $like)
                                ->orWhere('s.state_name', 'like', $like)
                                ->orWhere('hw.highway_name', 'like', $like);
                        });
                    }
                });
            }
        }

        /* ---------- PRICE RANGE ---------- */
        if (isset($filters['min_price']) && $filters['min_price'] !== '' && $filters['min_price'] !== null) {
            $query->whereRaw('CAST(m.price AS UNSIGNED) >= ?', [(int) $filters['min_price']]);
        }
        if (isset($filters['max_price']) && $filters['max_price'] !== '' && $filters['max_price'] !== null) {
            $query->whereRaw('CAST(m.price AS UNSIGNED) <= ?', [(int) $filters['max_price']]);
        }

        /* ---------- SIZE (sq.ft) RANGE ---------- */
        // area_auto is VARCHAR, so cast to a number to avoid lexicographic
        // comparison (e.g. '60000' wrongly > '120000').
        if (!empty($filters['min_area'])) {
            $query->whereRaw('CAST(m.area_auto AS DECIMAL(15,2)) >= ?', [(float) $filters['min_area']]);
        }
        if (!empty($filters['max_area'])) {
            $query->whereRaw('CAST(m.area_auto AS DECIMAL(15,2)) <= ?', [(float) $filters['max_area']]);
        }

        /* ---------- AVAILABILITY (FROM / TO DATE) ---------- */
        // Show only hoardings that are FREE for the whole requested window,
        // i.e. that have NO active booking overlapping [from_date, to_date].
        // A single bound is also supported (open-ended on the other side).
        $fromDate = !empty($filters['from_date']) ? $filters['from_date'] : null;
        $toDate   = !empty($filters['to_date']) ? $filters['to_date'] : null;

        if ($fromDate || $toDate) {
            $from = $fromDate ?: $toDate; // fall back so the window is valid
            $to   = $toDate ?: $fromDate;

            $query->whereNotExists(function ($q) use ($from, $to) {
                $q->select(DB::raw(1))
                    ->from('media_booked_date as mbd')
                    ->whereColumn('mbd.media_id', 'm.id')
                    ->where('mbd.is_active', 1)
                    ->where('mbd.is_deleted', 0)
                    ->where('mbd.from_date', '<=', $to)
                    ->where('mbd.to_date', '>=', $from);
            });
        }

        return $query;
    }

    /**
     * Apply an "OR within group" filter: whereIn for a list, where for a scalar.
     */
    private function applyInFilter($query, string $column, $value): void
    {
        $ids = $this->toIntArray($value);
        if (!empty($ids)) {
            $query->whereIn($column, $ids);
        }
    }

    /**
     * Normalise a filter value (scalar | csv | array) into a clean int array.
     */
    private function toIntArray($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (!is_array($value)) {
            $value = explode(',', (string) $value);
        }
        return array_values(array_filter(array_map('intval', $value), fn($v) => $v > 0));
    }
}

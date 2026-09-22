<?php

namespace App\Http\Repository\Website;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Support\RadiusRange;

class HomeRepository
{
    public function searchMedia(array $filters)
    {
        return $this->buildSearchQuery($filters)
            ->orderBy('m.id', 'DESC')
            ->paginate(config('fileConstants.SEARCH-PAGINATION'));
    }

    /**
     * Returns ALL matching rows (no pagination) for plotting on the map.
     * Uses the exact same filters as searchMedia so the markers always
     * match the result count.
     */
    public function getMapMarkers(array $filters)
    {
        return $this->buildSearchQuery($filters)
            ->orderBy('m.id', 'DESC')
            ->get();
    }


    /**
     * The hoardings on a shared link, in the order the team shortlisted them.
     *
     * Not paginated: a shortlist is a handful of rows the client is meant to
     * see all of at once, and the page plots the same set on its map.
     *
     * $filters is the client's own narrowing — Area, Area Type, Highway,
     * Landmarks, dates, size, radius, budget — coming off the filter card on
     * the shared page. It runs through the very same builder /search uses,
     * with media_ids pinned on top, so no filter can widen the set past the
     * ids stored on the link.
     */
    public function getMediaByIds(array $ids, array $filters = [])
    {
        if (empty($ids)) {
            return collect();
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));

        // Set last, never merged from the caller's array: the shortlist is the
        // hard boundary of this page and nothing posted may replace it.
        $filters['media_ids'] = $ids;

        // FIELD() keeps the team's own ordering, which whereIn does not.
        return $this->buildSearchQuery($filters)
            ->orderByRaw('FIELD(m.id, ' . implode(',', $ids) . ')')
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
                // Panel-sized media (a Bus Shelter's Front / Back / Side) have no
                // single width x height — area_auto is the total the panels add
                // up to, and the only size figure those records have. Already
                // filtered on by the size slider; selected so the card can
                // print it instead of "0.00 x 0.00 ft".
                'm.area_auto',
                'm.facing',
                // 'm.video_link',
                'ct.category_name',
                'a.area_name',
                's.state_name as state_name',
                'd.district_name as district_name',
                'city.city_name as city_name',
                'at.areatype_name as area_type_name',
                'hw.highway_name',
                'a.common_stdiciar_name as common_area_name',
                'm.panorama_image',
                'mi.first_image',
                DB::raw('(SELECT GROUP_CONCAT(l.landmark_name SEPARATOR ", ") FROM media_landmark ml JOIN landmark l ON l.id = ml.landmark_id WHERE ml.media_id = m.id AND l.is_deleted = 0) as landmark_names'),
                DB::raw('ROUND(m.price / DAY(LAST_DAY(CURDATE())), 2) as per_day_price')


            ]);

        /* ──────────────────────────────
         1 FIND CENTER POINT (CITY)
         ───────────────────────────────*/
        $centerLat = null;
        $centerLng = null;

        if (!empty($filters['city_id'])) {
            $center = DB::table('cities')
                ->where('id', $filters['city_id'])
                ->select('latitude', 'longitude')
                ->first();

            if ($center && $center->latitude && $center->longitude) {
                $centerLat = $center->latitude;
                $centerLng = $center->longitude;

                Log::info(" Using city lat/lng only", [
                    'city_id' => $filters['city_id'],
                    'lat'     => $centerLat,
                    'lng'     => $centerLng
                ]);
            } else {
                Log::warning(" City missing lat/lng — radius disabled", [
                    'city_id' => $filters['city_id']
                ]);
            }
        }




        /* FILTERS */
        if (!empty($filters['category_id'])) {
            $query->where('m.category_id', $filters['category_id']);
        }


        // The Radius slider posts free-form km, so pin both ends to the range
        // the slider actually offers — otherwise a hand-crafted request could
        // ask for a 10,000 km radius and, via the city rule below, search
        // everything. A max of 0 is the off position: no radius filter.
        //
        // radius_id is the single value the slider posted before it grew a
        // second handle; still read as the max so an old link or a saved
        // session filter keeps working.
        $radiusKm = RadiusRange::clamp($filters['max_radius'] ?? $filters['radius_id'] ?? null);
        $radiusFromKm = RadiusRange::clamp($filters['min_radius'] ?? null);

        // A min above the max is a nonsense band that would return nothing at
        // all; treat it as "from there outwards" rather than silently empty.
        if ($radiusFromKm > $radiusKm) {
            $radiusFromKm = 0.0;
        }

        // Tracks whether the distance filter really made it into the query. A
        // radius asked for on a city with no lat/lng silently does nothing, and
        // the city rule below must not drop its own filter on that basis.
        $radiusApplied = false;

        if ($radiusKm > 0 && $centerLat && $centerLng) {

            $query->whereNotNull('m.latitude')
                ->whereNotNull('m.longitude');

            $query->addSelect(DB::raw("
        (6371 * acos(
            cos(radians(?))
            * cos(radians(m.latitude))
            * cos(radians(m.longitude) - radians(?))
            + sin(radians(?))
            * sin(radians(m.latitude))
        )) AS distance
    "))
                ->addBinding([(float)$centerLat, (float)$centerLng, (float)$centerLat], 'select')
                ->having('distance', '<=', $radiusKm)
                ->orderBy('distance', 'asc');

            // The inner edge of the band. Only when asked for: a min of 0 is
            // the whole circle, and adding the clause anyway would drop media
            // sitting exactly on the centre point.
            if ($radiusFromKm > 0) {
                $query->having('distance', '>=', $radiusFromKm);
            }

            $radiusApplied = true;

            Log::info(' Radius Filter Applied', [
                'center_lat' => $centerLat,
                'center_lng' => $centerLng,
                'radius_from_km' => $radiusFromKm,
                'radius_km'  => $radiusKm
            ]);
        }


        // Sector Code, matched as "contains" and case-insensitively: the code
        // is the one thing a buyer is handed on a proposal, and a substring is
        // what makes it useful — "BS" lists every bus shelter, "000034" finds
        // HD000034 whichever prefix it carries.
        //
        // addcslashes so the LIKE wildcards are literal: a pasted % would
        // otherwise match the entire inventory rather than nothing.
        if (!empty($filters['hoarding_code'])) {
            $code = addcslashes(trim((string) $filters['hoarding_code']), '%_\\');
            $query->where('m.hoarding_code', 'like', '%' . $code . '%');
        }

        if (!empty($filters['areatype_id'])) {
            $query->where('m.areatype_id', $filters['areatype_id']);
        }

        if (!empty($filters['state_id'])) {
            $query->where('m.state_id', $filters['state_id']);
        }

        if (!empty($filters['district_id'])) {
            $query->where('m.district_id', $filters['district_id']);
        }
        // Only widen past the city when the distance filter actually replaced it.
        if (!empty($filters['city_id']) && !$radiusApplied) {
            $query->where('m.city_id', $filters['city_id']);
        }
        // if (!empty($filters['city_id'])) {
        //     $query->where('m.city_id', $filters['city_id']);
        // }

        if (!empty($filters['area_id'])) {
            $query->where('m.area_id', $filters['area_id']);
        }

        /* HIGHWAY FILTER (single or multiple → OR logic) */
        if (!empty($filters['highway_id'])) {
            $query->whereIn('m.highway_id', (array) $filters['highway_id']);
        }

        /* LANDMARK FILTER (multiple → OR logic via pivot) */
        if (!empty($filters['landmark_ids'])) {
            $landmarkIds = array_filter((array) $filters['landmark_ids']);
            if (!empty($landmarkIds)) {
                $query->whereExists(function ($q) use ($landmarkIds) {
                    $q->select(DB::raw(1))
                        ->from('media_landmark as ml')
                        ->whereColumn('ml.media_id', 'm.id')
                        ->whereIn('ml.landmark_id', $landmarkIds);
                });
            }
        }

        /* SIZE FILTER */
        if (!empty($filters['size_id'])) {

            // size comes like "32 x 23"
            $parts = explode(' x ', $filters['size_id']);

            if (count($parts) == 2) {

                $width  = (float) $parts[0];
                $height = (float) $parts[1];

                $query->where('m.width', $width)
                    ->where('m.height', $height);
            }
        }
        // if (isset($filters['available_days']) && $filters['available_days'] !== '') {
        if (isset($filters['available_days']) && $filters['available_days'] > 0) {

            $days = (int) $filters['available_days'];
            $today = now()->toDateString();

            $query->addSelect(DB::raw("
CASE
    WHEN NOT EXISTS (
        SELECT 1 FROM media_booked_date mbd
        WHERE mbd.media_id = m.id
        AND mbd.is_active = 1
        AND mbd.is_deleted = 0
    )
    THEN 1

    WHEN {$days} = 0 AND NOT EXISTS (
        SELECT 1 FROM media_booked_date mbd
        WHERE mbd.media_id = m.id
        AND mbd.is_active = 1
        AND mbd.is_deleted = 0
        AND '{$today}' BETWEEN mbd.from_date AND mbd.to_date
    )
    THEN 1

    WHEN {$days} > 0 AND (
    SELECT IFNULL(MAX(mbd.to_date), '{$today}')
    FROM media_booked_date mbd
    WHERE mbd.media_id = m.id
    AND mbd.is_active = 1
    AND mbd.is_deleted = 0
) <= DATE_ADD('{$today}', INTERVAL {$days} DAY)
THEN 1

    ELSE 0
END AS is_available_days
"));


            //  THIS LINE IS MISSING
            $query->having('is_available_days', 1);
        }

        /*  BOOKING STATUS LOGIC */
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {

            $fromDate = $filters['from_date'];
            $toDate   = $filters['to_date'];

            $query->addSelect(DB::raw("
            CASE
                WHEN EXISTS (
                    SELECT 1 FROM media_booked_date mbd
                    WHERE mbd.media_id = m.id
                    AND mbd.is_deleted = 0
                    AND mbd.is_active = 1
                    AND mbd.from_date <= ?
                    AND mbd.to_date >= ?
                )
                THEN 1 ELSE 0
            END AS is_booked
        ", [$toDate, $fromDate]));
        } else {

            $query->addSelect(DB::raw("
            CASE
                WHEN EXISTS (
                    SELECT 1 FROM media_booked_date mbd
                    WHERE mbd.media_id = m.id
                    AND mbd.is_deleted = 0
                    AND mbd.is_active = 1
                    AND mbd.to_date >= CURDATE()
                )
                THEN 1 ELSE 0
            END AS is_booked
        "));
        }
        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $query->whereRaw('CAST(m.price AS UNSIGNED) >= ?', [(int)$filters['min_price']]);
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $query->whereRaw('CAST(m.price AS UNSIGNED) <= ?', [(int)$filters['max_price']]);
        }
        // if (!empty($filters['min_area']) || !empty($filters['max_area'])) {

        //     $min = $filters['min_area'] ?? 0;
        //     $max = $filters['max_area'] ?? 999999999;

        //     $query->whereBetween('m.area_auto', [$min, $max]);
        // }
        // if (isset($filters['min_area']) && $filters['min_area'] !== '') {
        //     $query->whereRaw('(m.width * m.height) >= ?', [$filters['min_area']]);
        // }

        // if (isset($filters['max_area']) && $filters['max_area'] !== '') {
        //     $query->whereRaw('(m.width * m.height) <= ?', [$filters['max_area']]);
        // }

        /* ──────────────────────────────
         SHARED LINK: an explicit shortlist
         The /shared/{token} page reuses this whole builder so its cards carry
         the same first_image, landmark_names and is_booked as the search
         results do; it just narrows the set to the ids on the link.
         ───────────────────────────────*/
        if (!empty($filters['media_ids'])) {
            $query->whereIn('m.id', array_map('intval', (array) $filters['media_ids']));
        }

        // area_auto is stored as VARCHAR, so cast to a number to avoid
        // lexicographic comparison (e.g. '60000' wrongly > '120000').
        if (!empty($filters['min_area'])) {
            $query->whereRaw('CAST(m.area_auto AS DECIMAL(15,2)) >= ?', [(float) $filters['min_area']]);
        }

        if (!empty($filters['max_area'])) {
            $query->whereRaw('CAST(m.area_auto AS DECIMAL(15,2)) <= ?', [(float) $filters['max_area']]);
        }


        Log::info('AREA FILTER', [
            'min_area' => $filters['min_area'] ?? null,
            'max_area' => $filters['max_area'] ?? null
        ]);
        return $query;
    }


    // public function getUniqueSizes()
    // {
    //     return DB::table('media_management')
    //         ->where('is_deleted', 0)
    //         ->where('is_active', 1)
    //         ->whereNotNull('width')
    //         ->whereNotNull('height')
    //         ->select(
    //             DB::raw('MIN(id) as id'),   // key
    //             'width',
    //             'height'
    //         )
    //         // ->groupBy('width', 'height')   //  remove duplicates
    //         ->orderBy('width')
    //         ->get()
    //         ->mapWithKeys(function ($item) {

    //             $size = (float)$item->width . ' x ' . (float)$item->height;

    //             return [
    //                 $item->id => $size   // key => value
    //             ];
    //         });
    // }
    public function getMediaDetails($mediaId)
    {
        $media = DB::table('media_management as m')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('cities as c', 'c.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('category as ct', 'ct.id', '=', 'm.category_id')
            ->leftJoin('illuminations as il', 'il.id', '=', 'm.illumination_id')
            ->leftJoin('areatype as at', 'at.id', '=', 'm.areatype_id')
            ->where('m.id', $mediaId)
            ->where('m.is_deleted', 0)
            ->select([
                'm.*',
                'ct.category_name',
                's.state_name as state_name',
                'd.district_name as district_name',
                'c.city_name as city_name',
                'a.area_name as area_name',
                'a.common_stdiciar_name as common_area_name',
                'il.illumination_name',
                'at.areatype_name as area_type',
                // 'rm.radius',
                DB::raw('ROUND(m.price / DAY(LAST_DAY(CURDATE())),2) as per_day_price')
            ])
            ->first();

        if ($media) {
            $media->images = DB::table('media_images')
                ->where('media_id', $mediaId)
                ->where('is_deleted', 0)
                ->where('is_active', 1)
                ->get();

            // The panels a Bus Shelter carries — Front, Back, Side — each with
            // its own width and height. Empty for every other category, which
            // is measured by one face. FIELD() keeps the display order the Add
            // form uses rather than insertion order.
            $media->panels = DB::table('media_location_sizes')
                ->where('media_id', $mediaId)
                ->whereNotNull('width')
                ->whereNotNull('height')
                ->orderByRaw("FIELD(position, 'front', 'back', 'side')")
                ->get(['position', 'width', 'height', 'quantity']);
        }

        return $media;
    }

    public function getLatestOtherMediaByCategory()
    {
        return DB::table('media_management as m')
            ->leftJoin('cities as city', 'city.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->leftJoin('category as ct', 'ct.id', '=', 'm.category_id')
            ->leftJoin(DB::raw('
                (SELECT media_id, MIN(images) AS first_image
                FROM media_images
                WHERE is_deleted = 0 AND is_active = 1
                GROUP BY media_id
                ) mi
            '), 'mi.media_id', '=', 'm.id')
            ->where('m.is_deleted', 0)
            ->where('m.is_active', 1)
            ->where('m.category_id', '!=', 1) // Billboards exclude
            ->whereIn('m.id', function ($q) {
                $q->select(DB::raw('MAX(id)'))
                    ->from('media_management')
                    ->where('is_deleted', 0)
                    ->where('is_active', 1)
                    ->where('category_id', '!=', 1)
                    ->groupBy('category_id');
            })
            ->select([
                'm.id',
                'm.media_title',
                'm.price',
                'm.category_id',
                DB::raw('IFNULL(m.width, 0)  as width'),
                DB::raw('IFNULL(m.height, 0) as height'),
                DB::raw('IFNULL(m.facing, "") as facing'),
                // DB::raw('IFNULL(m.video_link, "") as video_link'),
                // DB::raw('IFNULL(m.area_type, "") as area_type'),
                'ct.category_name',
                'a.area_name',
                'city.city_name',
                'mi.first_image',
                'm.latitude',
                'm.longitude',
                'm.panorama_image',
            ])
            ->orderBy('m.created_at', 'DESC')
            ->get();
    }
}

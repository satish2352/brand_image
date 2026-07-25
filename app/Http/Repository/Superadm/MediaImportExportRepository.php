<?php

namespace App\Http\Repository\Superadm;

use App\Models\MediaImage;
use App\Models\MediaManagement;
use App\Support\MediaImportSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MediaImportExportRepository
{
    /**
     * Every master value the importer can resolve, pre-keyed on the normalised
     * name so a 5,000 row sheet costs a handful of queries instead of 5,000.
     *
     * Child masters (district / city / area) are keyed by "<parent_id>|<name>"
     * so a row is only accepted when its hierarchy is internally consistent.
     */
    public function masters(): array
    {
        $n = fn ($value) => MediaImportSchema::normalise($value);

        $states = [];
        foreach (DB::table('states')->where('is_deleted', 0)->select('id', 'state_name')->get() as $row) {
            $states[$n($row->state_name)] = $row->id;
        }

        $districts = [];
        foreach (DB::table('districts')->where('is_deleted', 0)->select('id', 'state_id', 'district_name')->get() as $row) {
            $districts[$row->state_id . '|' . $n($row->district_name)] = $row->id;
        }

        $cities = [];
        foreach (DB::table('cities')->where('is_deleted', 0)->select('id', 'district_id', 'city_name')->get() as $row) {
            $cities[$row->district_id . '|' . $n($row->city_name)] = $row->id;
        }

        $areas = [];
        foreach (DB::table('areas')->where('is_deleted', 0)->select('id', 'city_id', 'area_name')->get() as $row) {
            $areas[$row->city_id . '|' . $n($row->area_name)] = $row->id;
        }

        $vendorsByCode = [];
        $vendorsByName = [];
        foreach (DB::table('vendors')->where('is_deleted', 0)->select('id', 'vendor_code', 'vendor_name')->get() as $row) {
            $vendorsByCode[$n($row->vendor_code)] = $row->id;
            $vendorsByName[$n($row->vendor_name)] = $row->id;
        }

        return [
            'states' => $states,
            'districts' => $districts,
            'cities' => $cities,
            'areas' => $areas,
            'vendors_by_code' => $vendorsByCode,
            'vendors_by_name' => $vendorsByName,
            'categories' => $this->simpleMaster('category', 'category_name'),
            'illuminations' => $this->simpleMaster('illuminations', 'illumination_name'),
            'areatypes' => $this->simpleMaster('areatype', 'areatype_name'),
            'highways' => $this->simpleMaster('highway', 'highway_name'),
            'landmarks' => $this->simpleMaster('landmark', 'landmark_name'),
        ];
    }

    /**
     * A single active category as ['name' => ..., 'slug' => ...], or null when
     * the id is unknown. Used to force a category specific sample template.
     */
    public function categoryById(int $id): ?array
    {
        $row = DB::table('category')
            ->where('id', $id)
            ->where('is_deleted', 0)
            ->first();

        if (!$row) {
            return null;
        }

        return [
            'name' => $row->category_name,
            // Mirrors categorySlugs() — the category table may not carry a slug.
            'slug' => \Illuminate\Support\Str::slug($row->slug ?? $row->category_name),
        ];
    }

    /**
     * Category slugs keyed by id — drives the category specific mandatory
     * fields (mall name, airport name, ...) exactly like the Add Media form.
     */
    public function categorySlugs(): array
    {
        $slugs = [];

        foreach (DB::table('category')->where('is_deleted', 0)->get() as $row) {
            $slugs[$row->id] = \Illuminate\Support\Str::slug($row->slug ?? $row->category_name);
        }

        return $slugs;
    }

    /**
     * Codes already in the database, used to reject duplicates at preview time.
     */
    public function existingCodes(): array
    {
        $hoarding = [];
        $media = [];
        $geo = [];

        DB::table('media_management')
            ->where('is_deleted', 0)
            ->select('id', 'hoarding_code', 'media_code', 'vendor_id', 'latitude', 'longitude')
            ->orderBy('id')
            ->chunk(2000, function ($rows) use (&$hoarding, &$media, &$geo) {
                foreach ($rows as $row) {
                    if (!empty($row->hoarding_code)) {
                        $hoarding[strtoupper(trim($row->hoarding_code))] = $row->id;
                    }
                    if (!empty($row->media_code)) {
                        $media[strtoupper(trim($row->media_code))] = $row->id;
                    }
                    $geo[$this->geoKey($row->vendor_id, $row->latitude, $row->longitude)] = $row->id;
                }
            });

        return ['hoarding' => $hoarding, 'media' => $media, 'geo' => $geo];
    }

    /**
     * Same shape as the geo map above so callers can build a comparable key.
     */
    public function geoKey($vendorId, $latitude, $longitude): string
    {
        return (int) $vendorId . '|'
            . number_format((float) $latitude, 6, '.', '')
            . '|'
            . number_format((float) $longitude, 6, '.', '');
    }

    /**
     * Highest HD###### currently issued — the publisher increments from here.
     */
    public function maxHoardingSequence(): int
    {
        $maxCode = DB::table('media_management')
            ->whereNotNull('hoarding_code')
            ->where('hoarding_code', 'like', 'HD%')
            ->orderByRaw('CAST(SUBSTRING(hoarding_code, 3) AS UNSIGNED) DESC')
            ->value('hoarding_code');

        return $maxCode ? (int) substr($maxCode, 2) : 0;
    }

    public function insert(array $payload): MediaManagement
    {
        return MediaManagement::create($payload);
    }

    public function updateRecord(int $id, array $payload): void
    {
        MediaManagement::where('id', $id)->update($payload);
    }

    public function syncLandmarks(int $mediaId, array $landmarkIds): void
    {
        MediaManagement::find($mediaId)?->landmarks()->sync($landmarkIds);
    }

    /**
     * Live gallery images on a record — the importer appends rather than
     * replaces, so it has to respect the ten image ceiling.
     */
    public function countImages(int $mediaId): int
    {
        return (int) MediaImage::where('media_id', $mediaId)
            ->where('is_deleted', 0)
            ->count();
    }

    public function attachImage(int $mediaId, string $fileName): void
    {
        MediaImage::create([
            'media_id' => $mediaId,
            'images' => $fileName,
            'is_active' => 1,
            'is_deleted' => 0,
        ]);
    }

    /**
     * Panorama is a single column, so a new one replaces the old file.
     */
    public function setPanorama(int $mediaId, string $fileName): void
    {
        $media = MediaManagement::find($mediaId);

        if (!$media) {
            return;
        }

        $previous = $media->panorama_image;

        $media->update(['panorama_image' => $fileName]);

        if (!empty($previous) && $previous !== $fileName) {
            removeImage($previous, config('fileConstants.IMAGE_DELETE'));
        }
    }

    /**
     * Export / preview query with every supported filter applied.
     *
     * @param array $filters state_id, district_id, city_id, area_id, category_id,
     *                       vendor_id, illumination_id, areatype_id, highway_id,
     *                       media_type, status, hoarding_code, from_date, to_date
     * @param array $ids     when non-empty, restricts the export to these records
     */
    public function exportQuery(array $filters = [], array $ids = []): Builder
    {
        $query = MediaManagement::query()
            ->from('media_management as m')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('cities as cty', 'cty.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
            ->leftJoin('vendors as v', 'v.id', '=', 'm.vendor_id')
            ->leftJoin('illuminations as il', 'il.id', '=', 'm.illumination_id')
            ->leftJoin('areatype as at', 'at.id', '=', 'm.areatype_id')
            ->leftJoin('highway as hw', 'hw.id', '=', 'm.highway_id')
            ->where('m.is_deleted', 0)
            ->select([
                'm.*',
                's.state_name',
                'd.district_name',
                'cty.city_name',
                'a.area_name',
                'c.category_name',
                'v.vendor_name',
                'v.vendor_code',
                'il.illumination_name',
                'at.areatype_name',
                'hw.highway_name',
                DB::raw('(SELECT GROUP_CONCAT(l.landmark_name SEPARATOR ", ")
                          FROM media_landmark ml
                          JOIN landmark l ON l.id = ml.landmark_id
                          WHERE ml.media_id = m.id AND l.is_deleted = 0) as landmark_names'),
                DB::raw('(SELECT COUNT(*) FROM media_images mi
                          WHERE mi.media_id = m.id AND mi.is_deleted = 0) as total_images'),
                // File names only; MediaExport turns them into public URLs so an
                // exported sheet can be edited and imported straight back.
                DB::raw('(SELECT GROUP_CONCAT(mi.images ORDER BY mi.id SEPARATOR ",")
                          FROM media_images mi
                          WHERE mi.media_id = m.id AND mi.is_deleted = 0) as image_files'),
            ]);

        if (!empty($ids)) {
            $query->whereIn('m.id', $ids);
        }

        foreach ([
            'state_id' => 'm.state_id',
            'district_id' => 'm.district_id',
            'city_id' => 'm.city_id',
            'area_id' => 'm.area_id',
            'category_id' => 'm.category_id',
            'vendor_id' => 'm.vendor_id',
            'illumination_id' => 'm.illumination_id',
            'areatype_id' => 'm.areatype_id',
            'highway_id' => 'm.highway_id',
        ] as $filter => $column) {
            if (!empty($filters[$filter])) {
                $query->where($column, (int) $filters[$filter]);
            }
        }

        if (!empty($filters['media_type'])) {
            $query->where('m.media_type', 'like', '%' . trim($filters['media_type']) . '%');
        }

        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== null) {
            $query->where('m.is_active', (int) $filters['status']);
        }

        if (!empty($filters['hoarding_code'])) {
            $code = trim($filters['hoarding_code']);
            $query->where(function ($q) use ($code) {
                $q->where('m.hoarding_code', 'like', "%{$code}%")
                    ->orWhere('m.media_code', 'like', "%{$code}%")
                    ->orWhere('m.media_title', 'like', "%{$code}%");
            });
        }

        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->whereBetween(DB::raw('DATE(m.created_at)'), [
                $filters['from_date'],
                $filters['to_date'],
            ]);
        }

        if (!empty($filters['min_price'])) {
            $query->where('m.price', '>=', (float) $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('m.price', '<=', (float) $filters['max_price']);
        }

        return $query->orderBy('m.id', 'desc');
    }

    /**
     * Dropdown values for the Import / Export screen.
     */
    public function filterOptions(): array
    {
        return [
            'states' => DB::table('states')->where('is_deleted', 0)->where('is_active', 1)
                ->orderBy('state_name')->get(['id', 'state_name']),
            'districts' => DB::table('districts')->where('is_deleted', 0)->where('is_active', 1)
                ->orderBy('district_name')->get(['id', 'state_id', 'district_name']),
            'cities' => DB::table('cities')->where('is_deleted', 0)->where('is_active', 1)
                ->orderBy('city_name')->get(['id', 'district_id', 'city_name']),
            'categories' => DB::table('category')->where('is_deleted', 0)->where('is_active', 1)
                ->orderBy('category_name')->get(['id', 'category_name']),
            'vendors' => DB::table('vendors')->where('is_deleted', 0)->where('is_active', 1)
                ->orderBy('vendor_name')->get(['id', 'vendor_name', 'vendor_code']),
            'illuminations' => DB::table('illuminations')->where('is_deleted', 0)->where('is_active', 1)
                ->orderBy('illumination_name')->get(['id', 'illumination_name']),
            'areatypes' => DB::table('areatype')->where('is_deleted', 0)->where('is_active', 1)
                ->orderBy('areatype_name')->get(['id', 'areatype_name']),
            'highways' => DB::table('highway')->where('is_deleted', 0)->where('is_active', 1)
                ->orderBy('highway_name')->get(['id', 'highway_name']),
        ];
    }

    /**
     * Flat master listing written into the template's Master Reference sheet.
     */
    public function masterReference(): array
    {
        $rows = [];

        foreach (DB::table('states')->where('is_deleted', 0)->orderBy('state_name')->get() as $row) {
            $rows[] = ['State', $row->state_name, '-'];
        }

        foreach (DB::table('districts as d')
            ->leftJoin('states as s', 's.id', '=', 'd.state_id')
            ->where('d.is_deleted', 0)
            ->orderBy('s.state_name')->orderBy('d.district_name')
            ->get(['d.district_name', 's.state_name']) as $row) {
            $rows[] = ['District', $row->district_name, 'State: ' . ($row->state_name ?? '-')];
        }

        foreach (DB::table('cities as c')
            ->leftJoin('districts as d', 'd.id', '=', 'c.district_id')
            ->where('c.is_deleted', 0)
            ->orderBy('d.district_name')->orderBy('c.city_name')
            ->get(['c.city_name', 'd.district_name']) as $row) {
            $rows[] = ['City', $row->city_name, 'District: ' . ($row->district_name ?? '-')];
        }

        foreach (DB::table('areas as a')
            ->leftJoin('cities as c', 'c.id', '=', 'a.city_id')
            ->where('a.is_deleted', 0)
            ->orderBy('c.city_name')->orderBy('a.area_name')
            ->get(['a.area_name', 'c.city_name']) as $row) {
            $rows[] = ['Area', $row->area_name, 'City: ' . ($row->city_name ?? '-')];
        }

        foreach (DB::table('vendors')->where('is_deleted', 0)->orderBy('vendor_name')->get() as $row) {
            $rows[] = ['Vendor', $row->vendor_code, 'Name: ' . $row->vendor_name];
        }

        foreach ([
            ['Category', 'category', 'category_name'],
            ['Illumination', 'illuminations', 'illumination_name'],
            ['Area Type', 'areatype', 'areatype_name'],
            ['Highway', 'highway', 'highway_name'],
            ['Landmark', 'landmark', 'landmark_name'],
        ] as [$label, $table, $column]) {
            foreach (DB::table($table)->where('is_deleted', 0)->orderBy($column)->get() as $row) {
                $rows[] = [$label, $row->{$column}, '-'];
            }
        }

        return $rows;
    }

    /**
     * Live master values used to fill the example rows in the downloadable
     * template.
     *
     * Handing out invented examples ("VND001", "Hoardings", "Commercial") only
     * produces a preview full of "does not exist in the master" errors, so the
     * template is built from whatever this installation actually holds.
     */
    public function sampleSources(): array
    {
        $locations = DB::table('areas as a')
            ->join('cities as c', 'c.id', '=', 'a.city_id')
            ->join('districts as d', 'd.id', '=', 'c.district_id')
            ->join('states as s', 's.id', '=', 'd.state_id')
            ->where('a.is_deleted', 0)->where('a.is_active', 1)
            ->where('c.is_deleted', 0)->where('c.is_active', 1)
            ->where('d.is_deleted', 0)->where('d.is_active', 1)
            ->where('s.is_deleted', 0)->where('s.is_active', 1)
            ->orderBy('a.id')
            ->limit(2)
            ->get(['s.state_name', 'd.district_name', 'c.city_name', 'a.area_name'])
            ->map(fn ($row) => (array) $row)
            ->all();

        $categories = DB::table('category')
            ->where('is_deleted', 0)->where('is_active', 1)
            ->orderBy('id')
            ->limit(2)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->category_name,
                // Mirrors categorySlugs() — the category table may not carry a slug.
                'slug' => \Illuminate\Support\Str::slug($row->slug ?? $row->category_name),
            ])
            ->all();

        $vendors = DB::table('vendors')
            ->where('is_deleted', 0)->where('is_active', 1)
            ->orderBy('id')
            ->limit(2)
            ->get(['id', 'vendor_code', 'vendor_name'])
            ->map(fn ($row) => (array) $row)
            ->all();

        return [
            'locations' => $locations,
            'categories' => $categories,
            'vendors' => $vendors,
            'illuminations' => $this->sampleValues('illuminations', 'illumination_name'),
            'areatypes' => $this->sampleValues('areatype', 'areatype_name'),
            'highways' => $this->sampleValues('highway', 'highway_name'),
            'landmarks' => $this->sampleValues('landmark', 'landmark_name'),
            'image_urls' => $this->sampleImageUrls(
                DB::table('media_images')->where('is_deleted', 0)
                    ->orderByDesc('id')->limit(6)->pluck('images')->all()
            ),
            'panorama_urls' => $this->sampleImageUrls(
                DB::table('media_management')->where('is_deleted', 0)
                    ->whereNotNull('panorama_image')->where('panorama_image', '<>', '')
                    ->orderByDesc('id')->limit(3)->pluck('panorama_image')->all()
            ),
        ];
    }

    /**
     * Nudge a sample coordinate northwards until it is free for this vendor.
     *
     * The importer rejects a repeated vendor + GPS pair, so a fixed pair baked
     * into the template would stop importing the moment somebody published the
     * sample once.
     *
     * @param array<string,bool> $reserved geo keys already handed to earlier sample rows
     * @return array{0:float, 1:float}
     */
    public function unusedCoordinates(int $vendorId, float $latitude, float $longitude, array $reserved = []): array
    {
        $taken = $reserved;

        $rows = DB::table('media_management')
            ->where('is_deleted', 0)
            ->where('vendor_id', $vendorId)
            ->get(['latitude', 'longitude']);

        foreach ($rows as $row) {
            $taken[$this->geoKey($vendorId, $row->latitude, $row->longitude)] = true;
        }

        // Bounded: 100 steps of ~11m each is far more headroom than any sample needs.
        for ($attempt = 0; $attempt < 100; $attempt++) {
            if (!isset($taken[$this->geoKey($vendorId, $latitude, $longitude)])) {
                break;
            }

            $latitude = round($latitude + 0.0001, 7);
        }

        return [$latitude, $longitude];
    }

    /**
     * Public links for images already in the inventory — candidates only. The
     * service probes them before letting any of them into the template, so a
     * host that blocks direct file access never ships a broken example.
     *
     * @param array<int,string> $fileNames
     * @return array<int,string>
     */
    private function sampleImageUrls(array $fileNames): array
    {
        $base = rtrim((string) config('fileConstants.IMAGE_VIEW'), '/') . '/';

        return array_values(array_map(
            fn ($name) => $base . $name,
            array_filter(array_map('trim', $fileNames), fn ($name) => $name !== '')
        ));
    }

    /**
     * @return array<int,string>
     */
    private function sampleValues(string $table, string $column, int $limit = 2): array
    {
        return DB::table($table)
            ->where('is_deleted', 0)->where('is_active', 1)
            ->orderBy('id')
            ->limit($limit)
            ->pluck($column)
            ->all();
    }

    private function simpleMaster(string $table, string $column): array
    {
        $map = [];

        foreach (DB::table($table)->where('is_deleted', 0)->select('id', $column)->get() as $row) {
            $map[MediaImportSchema::normalise($row->{$column})] = $row->id;
        }

        return $map;
    }
}

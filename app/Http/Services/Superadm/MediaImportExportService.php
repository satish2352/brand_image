<?php

namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\MediaImportExportRepository;
use App\Imports\MediaSheetImport;
use App\Support\MediaImageFetcher;
use App\Support\MediaImportImageBundle;
use App\Support\MediaImportSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class MediaImportExportService
{
    /** Rows shown on the preview screen; the rest are summarised. */
    public const PREVIEW_LIMIT = 100;

    /** Hard ceiling per upload, keeps one import inside a sane request budget. */
    public const MAX_ROWS = 5000;

    /** Parsed batches live here until published or discarded. */
    private const BATCH_DIR = 'media_imports';

    /**
     * Links the template download may test before giving up on filling the
     * image example columns — building a sample must stay quick.
     */
    private const TEMPLATE_PROBE_BUDGET = 6;

    protected $repo;

    protected $images;

    public function __construct(MediaImportExportRepository $repo, MediaImageFetcher $images)
    {
        $this->repo = $repo;
        $this->images = $images;
    }

    public function filterOptions(): array
    {
        return $this->repo->filterOptions();
    }

    public function masterReference(): array
    {
        return $this->repo->masterReference();
    }

    /* =====================================================================
     |  TEMPLATE SAMPLE ROWS
     ===================================================================== */

    /**
     * The active category as ['name' => ..., 'slug' => ...], or null when the id
     * is unknown — drives the category specific sample template.
     */
    public function category(?int $categoryId): ?array
    {
        return $categoryId ? $this->repo->categoryById($categoryId) : null;
    }

    /**
     * The columns a category's template should carry — common columns plus only
     * that category's own fields. Null / unknown category yields every column.
     *
     * @return array<int,array>
     */
    public function templateColumns(?int $categoryId = null): array
    {
        $slug = $categoryId ? ($this->repo->categoryById($categoryId)['slug'] ?? null) : null;

        return MediaImportSchema::columnsForSlug($slug);
    }

    /**
     * Example rows for the downloadable template, built from this
     * installation's own masters so the file imports cleanly as-is.
     *
     * When $categoryId is supplied every example row is filled for that one
     * category (its name and its category driven columns), so the file the admin
     * downloads from a category's button matches the media they are uploading.
     *
     * Falls back to the schema's static illustrations while the masters are
     * still empty — there is nothing real to point at yet.
     */
    public function templateSampleRows(?int $categoryId = null): array
    {
        // A category specific template pins both example rows to the chosen
        // category; without one the template shows whatever the first masters are.
        $forcedCategory = $categoryId ? $this->repo->categoryById($categoryId) : null;

        // Only that category's columns are written, so the sample file shows the
        // fields that category actually uses instead of all of them.
        $columns = MediaImportSchema::columnsForSlug($forcedCategory['slug'] ?? null);

        $sources = $this->repo->sampleSources();

        if (empty($sources['locations']) || empty($sources['categories']) || empty($sources['vendors'])) {
            return MediaImportSchema::sampleRows($columns);
        }

        // Example image links are only written once they have been proven to
        // load — the whole point of the template is that it imports cleanly, and
        // a host that blocks direct file access would otherwise ship two rows of
        // guaranteed errors.
        $probeBudget = self::TEMPLATE_PROBE_BUDGET;
        $gallery = $this->reachableUrls(
            $sources['image_urls'] ?? [], MediaImageFetcher::MAX_GALLERY_KB, 3, $probeBudget
        );
        $panorama = $this->reachableUrls(
            $sources['panorama_urls'] ?? [], MediaImageFetcher::MAX_PANORAMA_KB, 1, $probeBudget
        );

        $rows = [];

        // Geo keys already spent on an earlier sample row, so two rows sharing
        // one vendor cannot be handed the same coordinates.
        $reserved = [];

        for ($i = 0; $i < 2; $i++) {
            $location = $sources['locations'][$i] ?? $sources['locations'][0];
            $category = $forcedCategory ?? ($sources['categories'][$i] ?? $sources['categories'][0]);
            $vendor = $sources['vendors'][$i] ?? $sources['vendors'][0];

            [$latitude, $longitude] = $this->repo->unusedCoordinates(
                (int) $vendor['id'],
                $i === 0 ? 19.9974533 : 18.5679234,
                $i === 0 ? 73.7898023 : 73.9143210,
                $reserved
            );

            $reserved[$this->repo->geoKey((int) $vendor['id'], $latitude, $longitude)] = true;

            $row = [
                'media_title' => $location['area_name'] . ' Site ' . ($i + 1),
                'category' => $category['name'],
                'state' => $location['state_name'],
                'district' => $location['district_name'],
                'city' => $location['city_name'],
                'area' => $location['area_name'],
                'vendor_code' => $vendor['vendor_code'],
                'vendor_name' => $vendor['vendor_name'],
                // Blank so the importer auto generates HD###### for the example.
                'hoarding_code' => '',
                'media_code' => '',
                'address' => 'Near ' . $location['area_name'] . ', ' . $location['city_name'],
                'width' => $i === 0 ? '40' : '20',
                'height' => $i === 0 ? '20' : '10',
                'latitude' => number_format($latitude, 7, '.', ''),
                'longitude' => number_format($longitude, 7, '.', ''),
                'price' => $i === 0 ? '85000' : '45000',
                'illumination' => $this->pick($sources['illuminations'], $i),
                'facing' => 'Facing ' . $location['city_name'] . ' city centre',
                'area_type' => $this->pick($sources['areatypes'], $i),
                'highway' => $this->pick($sources['highways'], $i),
                'landmarks' => implode(', ', $sources['landmarks']),
                'media_type' => $i === 0 ? 'Unipole' : 'Billboard',
                // Row 1 shows the multi image form, row 2 the single image form.
                'image_urls' => $i === 0
                    ? implode(', ', array_slice($gallery, 0, 2))
                    : ($gallery[2] ?? ''),
                'panorama_url' => $i === 0 ? ($panorama[0] ?? '') : '',
                'status' => 'Active',
            ];

            $rows[] = MediaImportSchema::rowInColumnOrder(
                array_merge($row, $this->categorySampleExtras($category['slug'])),
                $columns
            );
        }

        return $rows;
    }

    /**
     * The category driven columns, filled so the example row satisfies
     * categoryRules() for whichever category the template picked up.
     */
    private function categorySampleExtras(string $slug): array
    {
        switch (true) {
            case str_contains($slug, 'mall'):
                return ['mall_name' => 'City Centre Mall', 'media_format' => 'Atrium Branding'];

            case str_contains($slug, 'airport'):
                return [
                    'airport_name' => 'City Airport',
                    'media_type' => 'Digital Screen',
                    'zone_type' => 'Arrival',
                ];

            case str_contains($slug, 'transit'):
            case str_contains($slug, 'transmit'):
                return ['transit_type' => 'Bus', 'branding_type' => 'Full Wrap', 'vehicle_count' => '25'];

            case str_contains($slug, 'office'):
                return ['building_name' => 'Corporate Tower', 'wall_length' => 'Wall Wrap'];

            default:
                return [];
        }
    }

    /**
     * Nth master value, falling back to the first so a single row master still
     * fills both examples.
     */
    private function pick(array $values, int $index): string
    {
        return (string) ($values[$index] ?? $values[0] ?? '');
    }

    /**
     * Keep only the links that actually load, stopping as soon as enough have
     * been found or the probe budget runs out.
     *
     * @param array<int,string> $candidates
     * @return array<int,string>
     */
    private function reachableUrls(array $candidates, int $maxKb, int $need, int &$budget): array
    {
        $good = [];

        foreach ($candidates as $url) {
            if (count($good) >= $need || $budget <= 0) {
                break;
            }

            $budget--;

            if ($this->images->probe($url, $maxKb) === null) {
                $good[] = $url;
            }
        }

        return $good;
    }

    public function exportQuery(array $filters = [], array $ids = []): Builder
    {
        return $this->repo->exportQuery($filters, $ids);
    }

    /* =====================================================================
     |  IMPORT — PARSE & VALIDATE
     ===================================================================== */

    /**
     * Read the uploaded sheet, validate every row and stage the result.
     *
     * Nothing is written to media_management here — the admin still has to
     * confirm on the preview screen.
     *
     * @param string $mode 'insert' (new records only) or
     *                     'upsert' (update rows whose Hoarding Code already exists)
     * @param int|null $categoryId when the admin uploaded from a category's Import
     *                     button, rows whose Category cell is blank use this category
     * @param UploadedFile|null $imagesZip archive holding the pictures the sheet's
     *                     Image columns name; unpacked into this batch's staging
     *                     directory and kept there until publish or discard
     */
    public function parseUpload(
        UploadedFile $file,
        string $mode = 'insert',
        ?int $categoryId = null,
        ?UploadedFile $imagesZip = null
    ): array {
        $this->pruneOldBatches();

        // Allocated up front: the staging directory for the images is named after
        // it, and publish finds the pictures again through the same token.
        $token = (string) Str::uuid();

        $bundle = $imagesZip
            ? MediaImportImageBundle::extract($imagesZip, $this->imageDir($token))
            : null;

        $this->images->useBundle($bundle);

        try {
            return $this->parseSheet($file, $mode, $categoryId, $token, $bundle);
        } catch (\Throwable $e) {
            // A sheet we could not read leaves no batch to publish, so the
            // pictures staged for it are dead weight.
            $bundle?->delete();

            throw $e;
        }
    }

    /**
     * The sheet itself: read, validate row by row and stage the result under
     * $token. Split out of parseUpload so a failure there can clean up the
     * images that were already unpacked for this batch.
     */
    private function parseSheet(
        UploadedFile $file,
        string $mode,
        ?int $categoryId,
        string $token,
        ?MediaImportImageBundle $bundle
    ): array {
        // Category chosen on the Import tab — fills in only where the sheet's own
        // Category cell is left blank, so an explicit value in the file still wins.
        $defaultCategory = $categoryId ? ($this->repo->categoryById($categoryId)['name'] ?? null) : null;

        $sheets = Excel::toArray(new MediaSheetImport(), $file, null, $this->readerType($file));
        $rows = $sheets[0] ?? [];

        if (empty($rows)) {
            throw new RuntimeException('The uploaded file is empty.');
        }

        [$headerIndex, $columnMap] = $this->resolveHeader($rows);

        $missing = $this->missingRequiredHeaders($columnMap);
        if (!empty($missing)) {
            throw new RuntimeException(
                'These mandatory columns are missing from the file: ' . implode(', ', $missing)
                . '. Please download the sample template and use its header row.'
            );
        }

        $dataRows = array_slice($rows, $headerIndex + 1);

        if (count($dataRows) > self::MAX_ROWS) {
            throw new RuntimeException(
                'The file contains ' . count($dataRows) . ' rows. Please split it into files of '
                . self::MAX_ROWS . ' rows or fewer.'
            );
        }

        $masters = $this->repo->masters();
        $categorySlugs = $this->repo->categorySlugs();
        $existing = $this->repo->existingCodes();

        $valid = [];
        $errors = [];
        $seen = ['hoarding' => [], 'media' => [], 'geo' => []];
        $counts = ['insert' => 0, 'update' => 0, 'blank' => 0];

        foreach ($dataRows as $offset => $rawRow) {
            // +1 because the header is row $headerIndex (0 based) in the sheet
            $sheetRowNo = $headerIndex + $offset + 2;

            $row = $this->mapRow($rawRow, $columnMap);

            if ($this->isBlankRow($row)) {
                $counts['blank']++;
                continue;
            }

            if ($defaultCategory && $this->blank($row['category'] ?? '')) {
                $row['category'] = $defaultCategory;
            }

            $result = $this->validateRow($row, $mode, $masters, $categorySlugs, $existing, $seen);

            if (!empty($result['errors'])) {
                $errors[] = [
                    'row' => $sheetRowNo,
                    'hoarding_code' => $row['hoarding_code'] ?? '',
                    'media_title' => $row['media_title'] ?? '',
                    'issues' => implode(' | ', $result['errors']),
                ];
                continue;
            }

            $result['record']['row'] = $sheetRowNo;
            $valid[] = $result['record'];
            $counts[$result['record']['action']]++;

            // Reserve this row's identifiers so a later row in the same file
            // cannot claim them again.
            if (!empty($result['record']['payload']['hoarding_code'])) {
                $seen['hoarding'][strtoupper($result['record']['payload']['hoarding_code'])] = $sheetRowNo;
            }
            if (!empty($result['record']['payload']['media_code'])) {
                $seen['media'][strtoupper($result['record']['payload']['media_code'])] = $sheetRowNo;
            }
            $seen['geo'][$result['record']['geo_key']] = $sheetRowNo;
        }

        $batch = [
            'token' => $token,
            'mode' => $mode,
            'file_name' => $file->getClientOriginalName(),
            'created_at' => now()->toDateTimeString(),
            // What came out of the images ZIP, so the preview screen can point
            // out pictures nobody asked for — almost always a typo in the sheet.
            'images_zip' => $bundle ? [
                'files' => $bundle->fileCount(),
                'unused' => $bundle->unusedCount(),
                'skipped' => array_slice($bundle->skippedFiles(), 0, 20),
                'skipped_total' => count($bundle->skippedFiles()),
            ] : null,
            'summary' => [
                'total_rows' => count($dataRows) - $counts['blank'],
                'blank_rows' => $counts['blank'],
                'ready' => count($valid),
                'insert' => $counts['insert'],
                'update' => $counts['update'],
                'failed' => count($errors),
            ],
            'rows' => $valid,
            'errors' => $errors,
        ];

        Storage::disk('local')->put(
            self::BATCH_DIR . '/' . $token . '.json',
            json_encode($batch)
        );

        return $batch;
    }

    public function getBatch(string $token): ?array
    {
        $path = self::BATCH_DIR . '/' . $this->safeToken($token) . '.json';

        if (!Storage::disk('local')->exists($path)) {
            return null;
        }

        return json_decode(Storage::disk('local')->get($path), true);
    }

    public function discard(string $token): void
    {
        Storage::disk('local')->delete(self::BATCH_DIR . '/' . $this->safeToken($token) . '.json');
        Storage::disk('local')->deleteDirectory($this->imageDir($token));
    }

    /**
     * Where this batch's unpacked images live until it is published or dropped.
     * Private storage — nothing here is web reachable, and only the images a
     * row actually claims are ever copied into the public upload folder.
     */
    private function imageDir(string $token): string
    {
        return self::BATCH_DIR . '/' . $this->safeToken($token) . '_images';
    }

    /* =====================================================================
     |  IMPORT — PUBLISH
     ===================================================================== */

    /**
     * Write a previously previewed batch into media_management.
     *
     * Identifiers are re-checked inside the transaction: another admin may have
     * created a clashing record between preview and confirmation. Clashing rows
     * are skipped and reported rather than failing the whole upload.
     */
    public function publish(string $token): array
    {
        $batch = $this->getBatch($token);

        if (!$batch) {
            throw new RuntimeException('This import session has expired. Please upload the file again.');
        }

        if (empty($batch['rows'])) {
            throw new RuntimeException('There are no valid rows to publish in this file.');
        }

        // Re-open the pictures the preview staged for this batch. Gone (a stale
        // tab, a cleaned up server) is not fatal here: the rows still import and
        // each missing image is reported against its own row.
        $this->images->useBundle(MediaImportImageBundle::open($this->imageDir($token)));

        $inserted = 0;
        $updated = 0;
        $skipped = [];

        // Rows that made it into the database and still owe us their images.
        $pendingImages = [];

        DB::transaction(function () use ($batch, &$inserted, &$updated, &$skipped, &$pendingImages) {
            $sequence = $this->repo->maxHoardingSequence();

            foreach ($batch['rows'] as $record) {
                $payload = $record['payload'];
                $landmarkIds = $record['landmark_ids'] ?? [];

                if ($record['action'] === 'update' && !empty($record['media_id'])) {
                    unset($payload['is_deleted']);
                    $this->repo->updateRecord((int) $record['media_id'], $payload);
                    $this->repo->syncLandmarks((int) $record['media_id'], $landmarkIds);
                    $updated++;
                    $pendingImages[] = $this->imageJob($record, (int) $record['media_id']);
                    continue;
                }

                $conflict = $this->conflictingCode($payload);
                if ($conflict) {
                    $skipped[] = [
                        'row' => $record['row'],
                        'hoarding_code' => $payload['hoarding_code'] ?? '',
                        'media_title' => $payload['media_title'] ?? '',
                        'issues' => $conflict,
                    ];
                    continue;
                }

                if (empty($payload['hoarding_code'])) {
                    $sequence++;
                    $payload['hoarding_code'] = 'HD' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
                }

                $media = $this->repo->insert($payload);

                if (!empty($landmarkIds)) {
                    $media->landmarks()->sync($landmarkIds);
                }

                $inserted++;
                $pendingImages[] = $this->imageJob($record, (int) $media->id);
            }
        });

        // Outside the transaction on purpose: the records are already safe, and
        // a slow or dead image host must not roll the whole import back.
        [$imageCount, $imageWarnings] = $this->attachImages($pendingImages);

        $this->discard($token);

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'images' => $imageCount,
            'image_warnings' => $imageWarnings,
        ];
    }

    /* =====================================================================
     |  IMAGES
     ===================================================================== */

    /**
     * Pair a staged row's image links with the record they now belong to.
     */
    private function imageJob(array $record, int $mediaId): array
    {
        return [
            'media_id' => $mediaId,
            'row' => $record['row'] ?? 0,
            'media_title' => $record['payload']['media_title'] ?? '',
            'image_urls' => $record['image_urls'] ?? [],
            'panorama_url' => $record['panorama_url'] ?? null,
        ];
    }

    /**
     * Download every referenced image and attach it to its record.
     *
     * A link that dies between preview and publish costs that one image, not
     * the row — the failure is reported back to the admin instead.
     *
     * @return array{0:int, 1:array<int,array>} stored count, per row warnings
     */
    private function attachImages(array $jobs): array
    {
        $stored = 0;
        $warnings = [];

        foreach ($jobs as $job) {
            if (empty($job['image_urls']) && empty($job['panorama_url'])) {
                continue;
            }

            $issues = [];

            $room = MediaImageFetcher::MAX_GALLERY_IMAGES - $this->repo->countImages($job['media_id']);

            foreach ($job['image_urls'] as $url) {
                if ($room <= 0) {
                    $issues[] = 'Image "' . $this->shortUrl($url) . '" skipped — this media already holds '
                        . MediaImageFetcher::MAX_GALLERY_IMAGES . ' images';
                    continue;
                }

                try {
                    $this->repo->attachImage(
                        $job['media_id'],
                        $this->images->download($url, MediaImageFetcher::MAX_GALLERY_KB)
                    );
                    $room--;
                    $stored++;
                } catch (\Throwable $e) {
                    $issues[] = 'Image "' . $this->shortUrl($url) . '" ' . $e->getMessage();
                }
            }

            if (!empty($job['panorama_url'])) {
                try {
                    $this->repo->setPanorama(
                        $job['media_id'],
                        $this->images->download($job['panorama_url'], MediaImageFetcher::MAX_PANORAMA_KB)
                    );
                    $stored++;
                } catch (\Throwable $e) {
                    $issues[] = 'Panorama image "' . $this->shortUrl($job['panorama_url']) . '" ' . $e->getMessage();
                }
            }

            if (!empty($issues)) {
                $warnings[] = [
                    'row' => $job['row'],
                    'hoarding_code' => '',
                    'media_title' => $job['media_title'],
                    'issues' => implode(' | ', $issues),
                ];
            }
        }

        return [$stored, $warnings];
    }

    /**
     * Long CDN links make the error log unreadable — keep the ends, drop the middle.
     */
    private function shortUrl(string $url): string
    {
        return strlen($url) <= 60
            ? $url
            : substr($url, 0, 40) . '...' . substr($url, -15);
    }

    /* =====================================================================
     |  ROW VALIDATION
     ===================================================================== */

    /**
     * @return array{errors: array<string>, record: array}
     */
    private function validateRow(
        array $row,
        string $mode,
        array $masters,
        array $categorySlugs,
        array $existing,
        array $seen
    ): array {
        $errors = [];
        $payload = [];

        /* ---------- mandatory presence ---------- */
        foreach (MediaImportSchema::requiredColumns() as $column) {
            $key = $column['key'];

            // Vendor may arrive as either a code or a name.
            if ($key === 'vendor_code') {
                if ($this->blank($row['vendor_code'] ?? '') && $this->blank($row['vendor_name'] ?? '')) {
                    $errors[] = 'Vendor Code (or Vendor Name) is required';
                }
                continue;
            }

            if ($this->blank($row[$key] ?? '')) {
                $errors[] = $column['label'] . ' is required';
            }
        }

        /* ---------- location hierarchy ---------- */
        $stateId = $districtId = $cityId = $areaId = null;

        if (!$this->blank($row['state'] ?? '')) {
            $stateId = $masters['states'][MediaImportSchema::normalise($row['state'])] ?? null;
            if (!$stateId) {
                $errors[] = 'State "' . $row['state'] . '" does not exist in the State master';
            }
        }

        if ($stateId && !$this->blank($row['district'] ?? '')) {
            $districtId = $masters['districts'][$stateId . '|' . MediaImportSchema::normalise($row['district'])] ?? null;
            if (!$districtId) {
                $errors[] = 'District "' . $row['district'] . '" does not exist under state "' . $row['state'] . '"';
            }
        }

        if ($districtId && !$this->blank($row['city'] ?? '')) {
            $cityId = $masters['cities'][$districtId . '|' . MediaImportSchema::normalise($row['city'])] ?? null;
            if (!$cityId) {
                $errors[] = 'City "' . $row['city'] . '" does not exist under district "' . $row['district'] . '"';
            }
        }

        if ($cityId && !$this->blank($row['area'] ?? '')) {
            $areaId = $masters['areas'][$cityId . '|' . MediaImportSchema::normalise($row['area'])] ?? null;
            if (!$areaId) {
                $errors[] = 'Area "' . $row['area'] . '" does not exist under city "' . $row['city'] . '"';
            }
        }

        /* ---------- vendor & category ---------- */
        $vendorId = null;
        if (!$this->blank($row['vendor_code'] ?? '')) {
            $vendorId = $masters['vendors_by_code'][MediaImportSchema::normalise($row['vendor_code'])] ?? null;
            if (!$vendorId) {
                $errors[] = 'Vendor Code "' . $row['vendor_code'] . '" does not exist in the Vendor master';
            }
        } elseif (!$this->blank($row['vendor_name'] ?? '')) {
            $vendorId = $masters['vendors_by_name'][MediaImportSchema::normalise($row['vendor_name'])] ?? null;
            if (!$vendorId) {
                $errors[] = 'Vendor Name "' . $row['vendor_name'] . '" does not exist in the Vendor master';
            }
        }

        $categoryId = null;
        if (!$this->blank($row['category'] ?? '')) {
            $categoryId = $masters['categories'][MediaImportSchema::normalise($row['category'])] ?? null;
            if (!$categoryId) {
                $errors[] = 'Category "' . $row['category'] . '" does not exist in the Category master';
            }
        }

        /* ---------- optional masters ---------- */
        $illuminationId = $this->resolveOptional(
            $row['illumination'] ?? '', $masters['illuminations'], 'Illumination', $errors
        );
        $areatypeId = $this->resolveOptional(
            $row['area_type'] ?? '', $masters['areatypes'], 'Area Type', $errors
        );
        $highwayId = $this->resolveOptional(
            $row['highway'] ?? '', $masters['highways'], 'Highway', $errors
        );

        $landmarkIds = [];
        if (!$this->blank($row['landmarks'] ?? '')) {
            foreach (preg_split('/[,;]+/', $row['landmarks']) as $name) {
                $name = trim($name);
                if ($name === '') {
                    continue;
                }
                $id = $masters['landmarks'][MediaImportSchema::normalise($name)] ?? null;
                if (!$id) {
                    $errors[] = 'Landmark "' . $name . '" does not exist in the Landmark master';
                    continue;
                }
                $landmarkIds[] = $id;
            }
            $landmarkIds = array_values(array_unique($landmarkIds));
        }

        /* ---------- image links ---------- */
        $imageUrls = MediaImageFetcher::splitUrls($row['image_urls'] ?? '');

        if (count($imageUrls) > MediaImageFetcher::MAX_GALLERY_IMAGES) {
            $errors[] = 'Image URLs holds ' . count($imageUrls) . ' links — the maximum is '
                . MediaImageFetcher::MAX_GALLERY_IMAGES . ' per media';
            $imageUrls = array_slice($imageUrls, 0, MediaImageFetcher::MAX_GALLERY_IMAGES);
        }

        $imageUrls = array_values(array_unique($imageUrls));

        foreach ($imageUrls as $url) {
            $problem = $this->images->probe($url, MediaImageFetcher::MAX_GALLERY_KB);
            if ($problem !== null) {
                $errors[] = 'Image "' . $this->shortUrl($url) . '" ' . $problem;
            }
        }

        $panoramaUrl = MediaImageFetcher::splitUrls($row['panorama_url'] ?? '')[0] ?? '';

        if ($panoramaUrl !== '') {
            $problem = $this->images->probe($panoramaUrl, MediaImageFetcher::MAX_PANORAMA_KB);
            if ($problem !== null) {
                $errors[] = 'Panorama image "' . $this->shortUrl($panoramaUrl) . '" ' . $problem;
            }
        }

        /* ---------- numeric fields ---------- */
        $width = $this->numeric($row['width'] ?? '', 'Width (ft)', $errors, 0.01, null);
        $height = $this->numeric($row['height'] ?? '', 'Height (ft)', $errors, 0.01, null);
        $latitude = $this->numeric($row['latitude'] ?? '', 'Latitude', $errors, -90, 90);
        $longitude = $this->numeric($row['longitude'] ?? '', 'Longitude', $errors, -180, 180);
        $price = $this->numeric($row['price'] ?? '', 'Price (Monthly)', $errors, 0, null);

        $vehicleCount = null;
        if (!$this->blank($row['vehicle_count'] ?? '')) {
            if (!is_numeric($row['vehicle_count']) || (int) $row['vehicle_count'] < 0) {
                $errors[] = 'Vehicle Count must be a whole number';
            } else {
                $vehicleCount = (int) $row['vehicle_count'];
            }
        }

        $areaAuto = null;
        if (!$this->blank($row['area_auto'] ?? '')) {
            if (!is_numeric($row['area_auto'])) {
                $errors[] = 'Total Area (Sq Ft) must be a number';
            } else {
                $areaAuto = (float) $row['area_auto'];
            }
        } elseif ($width !== null && $height !== null) {
            $areaAuto = round($width * $height, 2);
        }

        /* ---------- category specific rules (mirrors the Add Media form) ---------- */
        if ($categoryId) {
            $slug = $categorySlugs[$categoryId] ?? '';
            $errors = array_merge($errors, $this->categoryRules($slug, $row));
        }

        /* ---------- duplicates ---------- */
        $hoardingCode = strtoupper(trim((string) ($row['hoarding_code'] ?? '')));
        $mediaCode = trim((string) ($row['media_code'] ?? ''));
        $action = 'insert';
        $mediaId = null;

        if ($hoardingCode !== '') {
            if (isset($seen['hoarding'][$hoardingCode])) {
                $errors[] = 'Hoarding Code "' . $hoardingCode . '" is repeated in this file (also on row '
                    . $seen['hoarding'][$hoardingCode] . ')';
            } elseif (isset($existing['hoarding'][$hoardingCode])) {
                if ($mode === 'upsert') {
                    $action = 'update';
                    $mediaId = $existing['hoarding'][$hoardingCode];
                } else {
                    $errors[] = 'Hoarding Code "' . $hoardingCode
                        . '" already exists. Choose "Update existing records" mode to overwrite it';
                }
            }
        } elseif ($mode === 'upsert') {
            // Nothing to match on — treated as a new record, which is fine.
            $action = 'insert';
        }

        if ($mediaCode !== '') {
            $mediaKey = strtoupper($mediaCode);
            if (isset($seen['media'][$mediaKey])) {
                $errors[] = 'Media Code "' . $mediaCode . '" is repeated in this file (also on row '
                    . $seen['media'][$mediaKey] . ')';
            } elseif (isset($existing['media'][$mediaKey]) && $existing['media'][$mediaKey] !== $mediaId) {
                $errors[] = 'Media Code "' . $mediaCode . '" already belongs to another media record';
            }
        }

        $geoKey = '';
        if ($vendorId && $latitude !== null && $longitude !== null) {
            $geoKey = $this->repo->geoKey($vendorId, $latitude, $longitude);

            if (isset($seen['geo'][$geoKey])) {
                $errors[] = 'Same vendor and GPS coordinates already used on row ' . $seen['geo'][$geoKey]
                    . ' of this file';
            } elseif (isset($existing['geo'][$geoKey]) && $existing['geo'][$geoKey] !== $mediaId) {
                $errors[] = 'A media record with the same vendor and GPS coordinates already exists';
            }
        }

        if (!empty($errors)) {
            return ['errors' => $errors, 'record' => []];
        }

        /* ---------- build the payload ---------- */
        $payload = [
            'state_id' => $stateId,
            'district_id' => $districtId,
            'city_id' => $cityId,
            'area_id' => $areaId,
            'category_id' => $categoryId,
            'vendor_id' => $vendorId,
            'hoarding_code' => $hoardingCode !== '' ? $hoardingCode : null,
            'media_code' => $mediaCode !== '' ? $mediaCode : null,
            'media_title' => $this->text($row['media_title'] ?? ''),
            'address' => $this->text($row['address'] ?? ''),
            'width' => $width,
            'height' => $height,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'price' => $price,
            'illumination_id' => $illuminationId,
            'facing' => $this->text($row['facing'] ?? ''),
            'areatype_id' => $areatypeId,
            'highway_id' => $highwayId,
            'media_type' => $this->text($row['media_type'] ?? ''),
            'media_format' => $this->text($row['media_format'] ?? ''),
            'mall_name' => $this->text($row['mall_name'] ?? ''),
            'airport_name' => $this->text($row['airport_name'] ?? ''),
            'zone_type' => $this->text($row['zone_type'] ?? ''),
            'transit_type' => $this->text($row['transit_type'] ?? ''),
            'branding_type' => $this->text($row['branding_type'] ?? ''),
            'vehicle_count' => $vehicleCount,
            'building_name' => $this->text($row['building_name'] ?? ''),
            'wall_length' => $this->text($row['wall_length'] ?? ''),
            'area_auto' => $areaAuto,
            'is_active' => $this->status($row['status'] ?? ''),
            'is_deleted' => 0,
        ];

        return [
            'errors' => [],
            'record' => [
                'action' => $action,
                'media_id' => $mediaId,
                'geo_key' => $geoKey,
                'landmark_ids' => $landmarkIds,
                // Fetched after the transaction commits, not here — a network
                // call has no business holding a database transaction open.
                'image_urls' => $imageUrls,
                'panorama_url' => $panoramaUrl !== '' ? $panoramaUrl : null,
                'payload' => $payload,
                // Human readable copy so the preview table does not need extra joins.
                'display' => [
                    'hoarding_code' => $hoardingCode ?: 'Auto',
                    'media_title' => $payload['media_title'],
                    'category' => trim((string) ($row['category'] ?? '')),
                    'state' => trim((string) ($row['state'] ?? '')),
                    'district' => trim((string) ($row['district'] ?? '')),
                    'city' => trim((string) ($row['city'] ?? '')),
                    'area' => trim((string) ($row['area'] ?? '')),
                    'vendor' => trim((string) ($row['vendor_code'] ?? $row['vendor_name'] ?? '')),
                    'size' => $width . ' x ' . $height,
                    'gps' => $latitude . ', ' . $longitude,
                    'price' => $price,
                    'status' => $payload['is_active'] ? 'Active' : 'Inactive',
                    'images' => count($imageUrls) + ($panoramaUrl !== '' ? 1 : 0),
                ],
            ],
        ];
    }

    /**
     * Category driven mandatory fields, kept in step with MediaManagementController.
     */
    private function categoryRules(string $slug, array $row): array
    {
        $errors = [];

        $require = function (string $key, string $label) use ($row, &$errors) {
            if ($this->blank($row[$key] ?? '')) {
                $errors[] = $label . ' is required for this category';
            }
        };

        switch (true) {
            case str_contains($slug, 'hoardings'):
                $require('media_title', 'Media Title');
                $require('facing', 'Facing');
                $require('area_type', 'Area Type');
                $require('illumination', 'Illumination');
                $require('address', 'Address');
                break;

            case str_contains($slug, 'mall'):
                $require('mall_name', 'Mall Name');
                $require('media_format', 'Media Format');
                break;

            case str_contains($slug, 'airport'):
                $require('airport_name', 'Airport Name');
                $require('media_type', 'Media Type');
                if ($this->blank($row['zone_type'] ?? '')) {
                    $errors[] = 'Zone Type is required for this category';
                } elseif (!in_array(strtolower(trim($row['zone_type'])), ['arrival', 'departure'], true)) {
                    $errors[] = 'Zone Type must be either Arrival or Departure';
                }
                break;

            case str_contains($slug, 'transit'):
                $require('transit_type', 'Transit Type');
                $require('branding_type', 'Branding Type');
                if ($this->blank($row['vehicle_count'] ?? '')) {
                    $errors[] = 'Vehicle Count is required for this category';
                }
                break;

            case str_contains($slug, 'office'):
                $require('building_name', 'Building Name');
                $require('wall_length', 'Wall Length');
                break;
        }

        return $errors;
    }

    /* =====================================================================
     |  HELPERS
     ===================================================================== */

    /**
     * Locate the header row (the team often leaves a title line above it) and
     * map each sheet column index onto an internal field key.
     *
     * @return array{0:int, 1:array<int,string>}
     */
    private function resolveHeader(array $rows): array
    {
        $lookup = MediaImportSchema::headerLookup();
        $bestIndex = 0;
        $bestMap = [];

        // Only the first few lines are considered — beyond that it is data.
        foreach (array_slice($rows, 0, 10, true) as $index => $row) {
            $map = [];

            foreach ($row as $columnIndex => $heading) {
                $key = $lookup[MediaImportSchema::normalise((string) $heading)] ?? null;
                if ($key !== null && !in_array($key, $map, true)) {
                    $map[$columnIndex] = $key;
                }
            }

            if (count($map) > count($bestMap)) {
                $bestMap = $map;
                $bestIndex = $index;
            }
        }

        if (count($bestMap) < 3) {
            throw new RuntimeException(
                'Could not find a valid header row in this file. Please download the sample template and '
                . 'keep its header row intact.'
            );
        }

        return [$bestIndex, $bestMap];
    }

    private function missingRequiredHeaders(array $columnMap): array
    {
        $present = array_values($columnMap);
        $missing = [];

        foreach (MediaImportSchema::requiredColumns() as $column) {
            if ($column['key'] === 'vendor_code') {
                if (!in_array('vendor_code', $present, true) && !in_array('vendor_name', $present, true)) {
                    $missing[] = 'Vendor Code';
                }
                continue;
            }

            if (!in_array($column['key'], $present, true)) {
                $missing[] = $column['label'];
            }
        }

        return $missing;
    }

    private function mapRow(array $rawRow, array $columnMap): array
    {
        $row = [];

        foreach ($columnMap as $columnIndex => $key) {
            $value = $rawRow[$columnIndex] ?? '';
            $row[$key] = is_string($value) ? trim($value) : $value;
        }

        return $row;
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (!$this->blank($value)) {
                return false;
            }
        }

        return true;
    }

    private function blank($value): bool
    {
        return $value === null || $value === '' || (is_string($value) && trim($value) === '');
    }

    private function text($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Validate a numeric cell, tolerating "1,25,000" and "₹85000" style entries.
     */
    private function numeric($value, string $label, array &$errors, $min = null, $max = null): ?float
    {
        if ($this->blank($value)) {
            return null;
        }

        $clean = str_replace([',', '₹', ' ', "\u{00A0}"], '', (string) $value);

        if (!is_numeric($clean)) {
            $errors[] = $label . ' must be a number (found "' . $value . '")';

            return null;
        }

        $number = (float) $clean;

        if ($min !== null && $number < $min) {
            $errors[] = $label . ' must be ' . ($min == 0 ? '0 or more' : 'greater than ' . $min);

            return null;
        }

        if ($max !== null && $number > $max) {
            $errors[] = $label . ' must not be greater than ' . $max;

            return null;
        }

        return $number;
    }

    private function resolveOptional($value, array $master, string $label, array &$errors): ?int
    {
        if ($this->blank($value)) {
            return null;
        }

        $id = $master[MediaImportSchema::normalise($value)] ?? null;

        if (!$id) {
            $errors[] = $label . ' "' . $value . '" does not exist in the ' . $label . ' master';

            return null;
        }

        return (int) $id;
    }

    private function status($value): int
    {
        if ($this->blank($value)) {
            return 1;
        }

        return in_array(strtolower(trim((string) $value)), ['inactive', 'in-active', 'no', 'n', '0', 'disabled'], true)
            ? 0
            : 1;
    }

    /**
     * Late duplicate guard — another admin may have taken the code while this
     * batch sat on the preview screen.
     */
    private function conflictingCode(array $payload): ?string
    {
        if (!empty($payload['hoarding_code'])) {
            $exists = DB::table('media_management')
                ->where('hoarding_code', $payload['hoarding_code'])
                ->exists();

            if ($exists) {
                return 'Hoarding Code "' . $payload['hoarding_code'] . '" was created by someone else before this import was confirmed';
            }
        }

        if (!empty($payload['media_code'])) {
            $exists = DB::table('media_management')
                ->where('media_code', $payload['media_code'])
                ->where('is_deleted', 0)
                ->exists();

            if ($exists) {
                return 'Media Code "' . $payload['media_code'] . '" was created by someone else before this import was confirmed';
            }
        }

        return null;
    }

    private function readerType(UploadedFile $file): ?string
    {
        return match (strtolower($file->getClientOriginalExtension())) {
            'csv', 'txt' => ExcelFormat::CSV,
            'xls' => ExcelFormat::XLS,
            default => ExcelFormat::XLSX,
        };
    }

    private function safeToken(string $token): string
    {
        return preg_replace('/[^a-zA-Z0-9\-]/', '', $token);
    }

    /**
     * Staged batches are throwaway — drop anything left behind for over a day.
     *
     * Covers the unpacked images too: an admin who closes the preview tab
     * instead of confirming would otherwise leave the archive on disk forever,
     * which on shared hosting means running the account out of quota.
     */
    private function pruneOldBatches(): void
    {
        $disk = Storage::disk('local');

        if (!$disk->exists(self::BATCH_DIR)) {
            return;
        }

        $cutoff = now()->subDay()->getTimestamp();

        foreach ($disk->files(self::BATCH_DIR) as $path) {
            if ($disk->lastModified($path) < $cutoff) {
                $disk->delete($path);
            }
        }

        foreach ($disk->directories(self::BATCH_DIR) as $directory) {
            $files = $disk->files($directory);

            // An empty leftover directory has no timestamp to judge, so it goes.
            $stale = empty($files);

            foreach ($files as $file) {
                $stale = $disk->lastModified($file) < $cutoff;
                break;
            }

            if ($stale) {
                $disk->deleteDirectory($directory);
            }
        }
    }
}

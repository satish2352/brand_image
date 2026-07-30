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
     * Cell values that stand for "nothing here" rather than for real data, so
     * an export can be edited and uploaded straight back. MediaExport writes a
     * dash for every empty field; people type the others by hand.
     */
    private const EMPTY_MARKERS = ['-', '--', '—', 'N/A', 'n/a', 'NA', 'NULL', 'null', 'nil'];

    /**
     * Which sheet column feeds each database column.
     *
     * An update only writes the columns the uploaded file actually carries. A
     * category template holds a subset of the columns — Wall Painting has no
     * Media Title, no Illumination — and without this an update from one would
     * blank every field the sheet happens not to mention.
     */
    /**
     * Stored field values keyed by media id, so re-uploading a file only reads
     * each candidate record once.
     *
     * @var array<int,array<string,mixed>|null>
     */
    private array $recordFieldCache = [];

    private const PAYLOAD_SOURCES = [
        'state_id' => ['state'],
        'district_id' => ['district'],
        'city_id' => ['city'],
        'area_id' => ['area'],
        'category_id' => ['category'],
        'vendor_id' => ['vendor_code', 'vendor_name'],
        'hoarding_code' => ['hoarding_code'],
        'media_code' => ['media_code'],
        'media_title' => ['media_title'],
        'address' => ['address'],
        'width' => ['width'],
        'height' => ['height'],
        'latitude' => ['latitude'],
        'longitude' => ['longitude'],
        'price' => ['price'],
        'illumination_id' => ['illumination'],
        'facing' => ['facing'],
        'areatype_id' => ['area_type'],
        'highway_id' => ['highway'],
        'media_type' => ['media_type'],
        'media_format' => ['media_format'],
        'mall_name' => ['mall_name'],
        'airport_name' => ['airport_name'],
        'zone_type' => ['zone_type'],
        'transit_type' => ['transit_type'],
        'branding_type' => ['branding_type'],
        'vehicle_count' => ['vehicle_count'],
        'building_name' => ['building_name'],
        'wall_length' => ['wall_length'],
        // Calculated from Width x Height when the sheet has no column of its own.
        'area_auto' => ['area_auto', 'width', 'height'],
        'is_active' => ['status'],
    ];

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

        $fileName = $file->getClientOriginalName();

        // Identifies this exact sheet, so a re-upload of an already published
        // file can be pointed out before it doubles the records again.
        $fileHash = @hash_file('sha256', $file->getRealPath()) ?: '';
        $previousPublish = $fileHash !== '' ? $this->repo->lastPublishOf($fileHash) : null;

        if (empty($rows)) {
            throw new RuntimeException(
                'There is nothing to read in "' . $fileName . '" — its first sheet has no rows at all. '
                . 'Please go to Step 1, download the template for the category you are importing, '
                . 'fill in your rows and upload that file.'
            );
        }

        [$headerIndex, $columnMap] = $this->resolveHeader($rows, $fileName);

        $missing = $this->missingRequiredHeaders($columnMap);
        if (!empty($missing)) {
            throw new RuntimeException(
                'These required columns are missing from "' . $fileName . '": '
                . implode(', ', $missing) . '. Every one of them has to be present as a column '
                . 'heading, even when some cells are left empty. The quickest fix is to download '
                . 'the template again from Step 1 and paste your data under its header row.'
            );
        }

        $dataRows = array_slice($rows, $headerIndex + 1);

        if (count($dataRows) > self::MAX_ROWS) {
            throw new RuntimeException(
                'The file contains ' . count($dataRows) . ' rows. Please split it into files of '
                . self::MAX_ROWS . ' rows or fewer.'
            );
        }

        // Map every row once, up front: the blank padding rows Excel leaves
        // behind are dropped here, and what survives is what the whole-file
        // checks below (and the validation loop) actually work on.
        $counts = ['insert' => 0, 'update' => 0, 'blank' => 0];
        $mapped = [];

        foreach ($dataRows as $offset => $rawRow) {
            $row = $this->mapRow($rawRow, $columnMap);

            if ($this->isBlankRow($row)) {
                $counts['blank']++;
                continue;
            }

            // +1 because the header is row $headerIndex (0 based) in the sheet
            $mapped[$headerIndex + $offset + 2] = $row;
        }

        if (empty($mapped)) {
            throw new RuntimeException(
                'The header row in "' . $fileName . '" was read correctly, but there are no data '
                . 'rows underneath it — the file holds column headings only. Please enter your '
                . 'media rows below the header row and upload the file again.'
            );
        }

        // Nothing in the file says what kind of media these rows are, and no
        // category was picked on the Import tab either. Said once here, because
        // as a per-row error it would repeat on every single line.
        if ($defaultCategory === null && !$this->anyCategoryFilled($mapped)) {
            $where = count($mapped) === 1
                ? 'in the only data row of'
                : 'in all ' . count($mapped) . ' data rows of';

            throw new RuntimeException(
                'Please choose a media category before uploading. The Category column is empty '
                . $where . ' "' . $fileName . '", and no category was selected in Step 1, so '
                . 'there is nothing to say whether these are Hoardings, Wall Painting, Airport '
                . 'Branding or something else. Either click the category card in Step 1 and upload '
                . 'again, or fill the Category column in your file with a name from the Category '
                . 'master.'
            );
        }

        $masters = $this->repo->masters();
        $categorySlugs = $this->repo->categorySlugs();
        $existing = $this->repo->existingCodes();
        $this->recordFieldCache = [];

        // The fields this particular file supplies — an update must not touch
        // anything else.
        $sheetKeys = array_values(array_unique(array_values($columnMap)));

        $valid = [];
        $errors = [];
        $warnings = [];
        $seen = ['hoarding' => [], 'media' => [], 'geo' => []];

        foreach ($mapped as $sheetRowNo => $row) {
            if ($defaultCategory && $this->blank($row['category'] ?? '')) {
                $row['category'] = $defaultCategory;
            }

            $result = $this->validateRow(
                $row, $mode, $masters, $categorySlugs, $existing, $seen, $sheetKeys
            );

            if (!empty($result['errors'])) {
                $errors[] = [
                    'row' => $sheetRowNo,
                    'hoarding_code' => $row['hoarding_code'] ?? '',
                    // The record already in the inventory that this row clashes
                    // with, when the failure was a duplicate.
                    'existing_code' => $result['existing_code'] ?? '',
                    'media_title' => $row['media_title'] ?? '',
                    'issues' => implode(' | ', $result['errors']),
                ];
                continue;
            }

            $result['record']['row'] = $sheetRowNo;
            $valid[] = $result['record'];
            $counts[$result['record']['action']]++;

            foreach ($result['record']['notices'] ?? [] as $notice) {
                $warnings[] = [
                    'row' => $sheetRowNo,
                    'media_title' => $row['media_title'] ?? '',
                    'message' => $notice,
                ];
            }

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
            'file_hash' => $fileHash,
            'created_at' => now()->toDateTimeString(),
            // Set when this same sheet has been published before, so the preview
            // can warn that publishing again duplicates what it added last time.
            'already_published' => $previousPublish ? [
                'at' => $previousPublish->published_at,
                'inserted' => (int) $previousPublish->inserted,
                'updated' => (int) $previousPublish->updated,
            ] : null,
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
                'flagged' => count($warnings),
            ],
            'rows' => $valid,
            'errors' => $errors,
            // Rows that import as they are but are worth a second look.
            'warnings' => $warnings,
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

                    // Only when the file had a Landmarks column to replace them
                    // from, or an update would silently clear them.
                    if ($record['sync_landmarks'] ?? true) {
                        $this->repo->syncLandmarks((int) $record['media_id'], $landmarkIds);
                    }
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
        [$imageCount, $imageWarnings, $imagesRemoved] = $this->attachImages($pendingImages);

        // Remembered before the batch is thrown away, so uploading this same
        // sheet again can be flagged instead of quietly duplicating it.
        $this->repo->recordPublish(
            $batch['file_hash'] ?? '',
            $batch['file_name'] ?? null,
            ['inserted' => $inserted, 'updated' => $updated]
        );

        $this->discard($token);

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'images' => $imageCount,
            'images_removed' => $imagesRemoved,
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
            'gallery_keep' => $record['gallery_keep'] ?? [],
            'replace_gallery' => $record['replace_gallery'] ?? false,
        ];
    }

    /**
     * Download every referenced image and attach it to its record.
     *
     * A link that dies between preview and publish costs that one image, not
     * the row — the failure is reported back to the admin instead.
     *
     * @return array{0:int, 1:array<int,array>, 2:int} stored count, per row warnings, removed count
     */
    private function attachImages(array $jobs): array
    {
        $stored = 0;
        $removed = 0;
        $warnings = [];

        foreach ($jobs as $job) {
            if (empty($job['image_urls']) && empty($job['panorama_url']) && empty($job['replace_gallery'])) {
                continue;
            }

            $issues = [];

            // An update whose Image URLs cell was filled in makes that list the
            // record's gallery: pictures it no longer names are deleted, which is
            // what renaming an image in the sheet is meant to do. Done before the
            // new ones arrive, so the freed slots count towards the limit.
            if (!empty($job['replace_gallery'])) {
                $dropped = $this->repo->removeImagesNotNamed($job['media_id'], $job['gallery_keep'] ?? []);
                $removed += count($dropped);
            }

            $room = MediaImageFetcher::MAX_GALLERY_IMAGES - $this->repo->countImages($job['media_id']);
            $held = $this->repo->imageFileNames($job['media_id']);

            foreach ($job['image_urls'] as $url) {
                // An exported sheet lists the record's own pictures. Re-importing
                // it must not stack a second copy of each one.
                if ($this->alreadyHeld($url, $held['gallery'])) {
                    continue;
                }

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

            if (!empty($job['panorama_url']) && !$this->alreadyHeld($job['panorama_url'], [$held['panorama']])) {
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

        return [$stored, $warnings, $removed];
    }

    /**
     * The row's values in database-column terms, for comparing against a record
     * that already exists. Resolved ids and numbers are passed in; the plain text
     * columns are read straight off the row.
     *
     * @param array<string,mixed> $row      the sheet row, keyed on field name
     * @param array<string,mixed> $resolved values already turned into ids / numbers
     * @return array<string,mixed>
     */
    private function comparableRow(array $row, array $resolved): array
    {
        return $resolved + [
            'media_title' => $this->text($row['media_title'] ?? ''),
            'address' => $this->text($row['address'] ?? ''),
            'facing' => $this->text($row['facing'] ?? ''),
            'media_type' => $this->text($row['media_type'] ?? ''),
            'media_format' => $this->text($row['media_format'] ?? ''),
            'mall_name' => $this->text($row['mall_name'] ?? ''),
            'airport_name' => $this->text($row['airport_name'] ?? ''),
            'zone_type' => $this->text($row['zone_type'] ?? ''),
            'transit_type' => $this->text($row['transit_type'] ?? ''),
            'branding_type' => $this->text($row['branding_type'] ?? ''),
            'building_name' => $this->text($row['building_name'] ?? ''),
            'wall_length' => $this->text($row['wall_length'] ?? ''),
        ];
    }

    /**
     * Whether every column the sheet supplies holds the same value as the record.
     *
     * Only the supplied columns are judged: a template that has no Media Title
     * column says nothing about the title, so a difference there cannot be
     * claimed either way.
     *
     * @param array<string,mixed> $rowValues
     * @param array<string,mixed> $recordValues
     * @param array<int,string>   $sheetKeys
     */
    private function sameAsRecord(array $rowValues, array $recordValues, array $sheetKeys): bool
    {
        $compared = 0;

        foreach ($rowValues as $column => $value) {
            if (!array_key_exists($column, $recordValues)) {
                continue;
            }

            // Was this column actually present in the uploaded file?
            $sources = self::PAYLOAD_SOURCES[$column] ?? [$column];
            $supplied = $sheetKeys === [];

            foreach ($sources as $source) {
                if (in_array($source, $sheetKeys, true)) {
                    $supplied = true;
                    break;
                }
            }

            if (!$supplied) {
                continue;
            }

            if (!$this->valuesMatch($value, $recordValues[$column])) {
                return false;
            }

            $compared++;
        }

        // Guard against declaring a match on the strength of nothing at all.
        return $compared > 0;
    }

    /**
     * Compare one cell against one stored column: blanks are equal, numbers are
     * compared as numbers ("400" == "400.00"), text ignores case and padding.
     */
    private function valuesMatch($left, $right): bool
    {
        $leftBlank = $left === null || trim((string) $left) === '';
        $rightBlank = $right === null || trim((string) $right) === '';

        if ($leftBlank || $rightBlank) {
            return $leftBlank && $rightBlank;
        }

        if (is_numeric($left) && is_numeric($right)) {
            return abs((float) $left - (float) $right) < 0.005;
        }

        return strcasecmp(trim((string) $left), trim((string) $right)) === 0;
    }

    /**
     * A record's stored values, fetched once per import.
     *
     * @return array<string,mixed>|null
     */
    private function existingFields(int $mediaId): ?array
    {
        if (!array_key_exists($mediaId, $this->recordFieldCache)) {
            $this->recordFieldCache[$mediaId] = $this->repo->recordFields($mediaId);
        }

        return $this->recordFieldCache[$mediaId];
    }

    /**
     * Narrow a payload to the database columns this file actually feeds.
     *
     * is_deleted is always kept — it is not a sheet column, and publish() drops
     * it from updates itself.
     *
     * @param array<string,mixed> $payload
     * @param array<int,string>   $sheetKeys field keys the header row supplied
     * @return array<string,mixed>
     */
    private function columnsSuppliedBy(array $payload, array $sheetKeys): array
    {
        $kept = [];

        foreach ($payload as $column => $value) {
            $sources = self::PAYLOAD_SOURCES[$column] ?? null;

            if ($sources === null) {
                $kept[$column] = $value;
                continue;
            }

            foreach ($sources as $source) {
                if (in_array($source, $sheetKeys, true)) {
                    $kept[$column] = $value;
                    break;
                }
            }
        }

        return $kept;
    }

    /**
     * Whether a link points at a picture the record already holds.
     *
     * MediaExport builds its links as <public base>/<stored file name>, so the
     * last path segment is exactly the name in the database — enough to spot a
     * record's own pictures coming back in an edited export. A genuinely new
     * upload carries a different name and is downloaded as usual.
     *
     * @param array<int,string> $fileNames
     */
    private function alreadyHeld(string $url, array $fileNames): bool
    {
        $candidate = $this->fileNameOf($url);

        if ($candidate === '') {
            return false;
        }

        foreach ($fileNames as $name) {
            $name = strtolower(trim((string) $name));

            if ($name !== '' && $name === $candidate) {
                return true;
            }
        }

        return false;
    }

    /**
     * The comparable file name inside a link or a bare name, lower cased.
     * "https://host/storage/upload/images/media/a-1.PNG" -> "a-1.png"
     */
    private function fileNameOf(string $url): string
    {
        $url = trim($url);

        return strtolower(basename(parse_url($url, PHP_URL_PATH) ?: $url));
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
        array $seen,
        array $sheetKeys = []
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

        // The hoarding code of the record this row collides with. Re-uploading a
        // file that has already been imported is the common case, and the sheet
        // itself carries no code then (they are generated on publish), so
        // without this the error log could only say "already exists".
        $conflictCode = '';

        if ($hoardingCode !== '') {
            if (isset($seen['hoarding'][$hoardingCode])) {
                $errors[] = 'Hoarding Code "' . $hoardingCode . '" is repeated in this file (also on row '
                    . $seen['hoarding'][$hoardingCode] . ')';
            } elseif (isset($existing['hoarding'][$hoardingCode])) {
                if ($mode === 'upsert') {
                    $action = 'update';
                    $mediaId = $existing['hoarding'][$hoardingCode];
                } else {
                    $conflictCode = $hoardingCode;
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
                $owner = $existing['codes'][$existing['media'][$mediaKey]] ?? '';
                $conflictCode = $conflictCode ?: $owner;
                $errors[] = 'Media Code "' . $mediaCode . '" already belongs to another media record'
                    . ($owner !== '' ? ' (Hoarding Code ' . $owner . ')' : '');
            }
        }

        // One vendor legitimately owns several media at one position: the two
        // faces of a gantry, the panels along a wall, a whole bus fleet parked
        // at one depot. So a repeated vendor + GPS pair is only worth pointing
        // out in case a row was pasted twice — never a reason to reject it.
        // (Neither the Add Media form nor the table has this constraint.)
        $notices = [];
        $geoKey = '';
        $geoMatchId = null;

        if ($vendorId && $latitude !== null && $longitude !== null) {
            $geoKey = $this->repo->geoKey($vendorId, $latitude, $longitude);

            if (isset($seen['geo'][$geoKey])) {
                $notices[] = 'Same vendor and GPS position as row ' . $seen['geo'][$geoKey]
                    . ' of this file — fine for two faces at one site, but check it is not the same '
                    . 'row entered twice.';
            } elseif (isset($existing['geo'][$geoKey]) && $existing['geo'][$geoKey] !== $mediaId) {
                $geoMatchId = $existing['geo'][$geoKey];
                $owner = $existing['codes'][$geoMatchId] ?? '';
                $notices[] = 'Same vendor and GPS position as '
                    . ($owner !== '' ? $owner : 'a record') . ' already in the inventory — fine for '
                    . 'another face at the same site, but check it is not a duplicate.';
            }
        }

        /* ---------- matching a row to a record already in the inventory ----------
           A vendor and a GPS position together name a physical site, which is the
           only handle a sheet without Hoarding Codes gives us. What to do with it
           depends on what the admin asked for:

             "Add new records only"        — everything is new, so a site holding
                                             several faces keeps working. Only a row
                                             identical in every supplied value is
                                             refused, as there is nothing to add.

             "Add new and update existing" — the row is matched to the record at
                                             that site and updates it, which is how
                                             a changed price reaches the inventory
                                             without a Hoarding Code. Ambiguous when
                                             several records share the position, and
                                             then the code has to be given. */
        if ($geoMatchId && $mediaId === null) {
            $owner = $existing['codes'][$geoMatchId] ?? '';
            $sharing = $existing['geo_all'][$geoKey] ?? [];

            $candidate = $this->existingFields((int) $geoMatchId);
            $identical = $candidate !== null && $this->sameAsRecord(
                $this->comparableRow($row, [
                    'category_id' => $categoryId,
                    'width' => $width,
                    'height' => $height,
                    'price' => $price,
                    'illumination_id' => $illuminationId,
                    'areatype_id' => $areatypeId,
                    'highway_id' => $highwayId,
                    'vehicle_count' => $vehicleCount,
                    'media_code' => $mediaCode !== '' ? $mediaCode : null,
                ]),
                $candidate,
                $sheetKeys
            );

            if ($mode === 'upsert' && $hoardingCode === '') {
                if (count($sharing) > 1) {
                    $codes = array_values(array_filter(array_map(
                        fn ($id) => $existing['codes'][$id] ?? '',
                        $sharing
                    )));

                    $errors[] = count($sharing) . ' records already share this vendor and GPS position'
                        . (!empty($codes) ? ' (' . implode(', ', $codes) . ')' : '')
                        . ', so there is no way to tell which one this row means. Put the Hoarding Code '
                        . 'of the record you want to change into the file.';
                } else {
                    $action = 'update';
                    $mediaId = (int) $geoMatchId;
                    $notices = [
                        $identical
                            ? 'Matched to ' . ($owner ?: 'the record') . ' at this vendor and GPS position. '
                                . 'Nothing in the row differs from it, so publishing changes nothing.'
                            : 'Matched to ' . ($owner ?: 'the record') . ' at this vendor and GPS position, '
                                . 'so that record is updated rather than another being added. Switch to '
                                . '"Add new records only" if this really is a separate media at the same site.',
                    ];
                }
            } elseif ($identical) {
                return [
                    'errors' => [
                        'This media is already in the inventory as ' . ($owner ?: 'an existing record')
                        . ' — the vendor, GPS position, size, price and every other detail in this row '
                        . 'are identical to it, so there is nothing to add. Remove the row, or choose '
                        . '"Add new and update existing" to have it matched to that record.',
                    ],
                    'record' => [],
                    'existing_code' => $owner,
                ];
            }
        }

        /* ---------- image links ----------
           Deliberately after the duplicate resolution: once $mediaId is known,
           the pictures that record already holds are dropped from the row. An
           edited export names its own images, and those need neither checking
           over the network nor downloading a second time. */
        $held = $mediaId ? ($existing['pictures'][$mediaId] ?? []) : [];

        // Everything the sheet names, before the held ones are filtered out — an
        // update needs the whole intended gallery to know what is now missing
        // from it and should be deleted.
        $namedImages = MediaImageFetcher::splitUrls($row['image_urls'] ?? '');

        $imageUrls = array_values(array_filter(
            $namedImages,
            fn ($url) => !$this->alreadyHeld($url, $held)
        ));

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

        if ($panoramaUrl !== '' && $this->alreadyHeld($panoramaUrl, $held)) {
            $panoramaUrl = '';
        }

        if ($panoramaUrl !== '') {
            $problem = $this->images->probe($panoramaUrl, MediaImageFetcher::MAX_PANORAMA_KB);
            if ($problem !== null) {
                $errors[] = 'Panorama image "' . $this->shortUrl($panoramaUrl) . '" ' . $problem;
            }
        }

        if (!empty($errors)) {
            return ['errors' => $errors, 'record' => [], 'existing_code' => $conflictCode];
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

        // An update writes only what the file supplies. Anything the sheet has
        // no column for keeps whatever the record already holds.
        if ($action === 'update' && !empty($sheetKeys)) {
            $payload = $this->columnsSuppliedBy($payload, $sheetKeys);

            // A blank code cell must never erase the record's own identifiers.
            // The template carries a Hoarding Code column that is left empty for
            // new media, and a row matched on vendor and GPS is matched precisely
            // because it has no code — writing that blank back would strip the
            // code off the record and hand it to the next import to reissue.
            foreach (['hoarding_code', 'media_code'] as $identifier) {
                if (array_key_exists($identifier, $payload) && $payload[$identifier] === null) {
                    unset($payload[$identifier]);
                }
            }
        }

        return [
            'errors' => [],
            'record' => [
                'action' => $action,
                'media_id' => $mediaId,
                'geo_key' => $geoKey,
                // Things worth a second look that do not stop the row importing.
                'notices' => $notices,
                'landmark_ids' => $landmarkIds,
                // Replacing a record's landmarks is only right when the file
                // actually has a Landmarks column to replace them from.
                'sync_landmarks' => $sheetKeys === [] || in_array('landmarks', $sheetKeys, true),
                // Fetched after the transaction commits, not here — a network
                // call has no business holding a database transaction open.
                'image_urls' => $imageUrls,
                'panorama_url' => $panoramaUrl !== '' ? $panoramaUrl : null,
                // The gallery the sheet asks for, by file name. On an update
                // anything the record holds outside this list is deleted.
                'gallery_keep' => array_values(array_unique(array_map(
                    fn ($url) => $this->fileNameOf($url),
                    $namedImages
                ))),
                // Only a filled Image URLs cell rewrites the gallery. Leaving it
                // empty (or having no such column) keeps the pictures as they are,
                // so a price-only update cannot wipe them.
                'replace_gallery' => $action === 'update' && !empty($namedImages),
                'payload' => $payload,
                // Human readable copy so the preview table does not need extra
                // joins. Read with defaults: an update payload only holds the
                // columns its file supplies, so these keys may be absent.
                'display' => [
                    'hoarding_code' => $hoardingCode ?: 'Auto',
                    'media_title' => $payload['media_title'] ?? $this->text($row['media_title'] ?? ''),
                    'category' => trim((string) ($row['category'] ?? '')),
                    'state' => trim((string) ($row['state'] ?? '')),
                    'district' => trim((string) ($row['district'] ?? '')),
                    'city' => trim((string) ($row['city'] ?? '')),
                    'area' => trim((string) ($row['area'] ?? '')),
                    'vendor' => trim((string) ($row['vendor_code'] ?? $row['vendor_name'] ?? '')),
                    'size' => $width . ' x ' . $height,
                    'gps' => $latitude . ', ' . $longitude,
                    'price' => $price,
                    // No Status column in the file means the record keeps whatever
                    // it already has, so there is nothing to show.
                    'status' => array_key_exists('is_active', $payload)
                        ? ($payload['is_active'] ? 'Active' : 'Inactive')
                        : 'Unchanged',
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
    private function resolveHeader(array $rows, string $fileName = ''): array
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
            throw new RuntimeException($this->headerNotFoundMessage($rows, $fileName, $bestMap, $lookup));
        }

        return [$bestIndex, $bestMap];
    }

    /**
     * Explain a missing header row in terms the uploader can act on: what was
     * actually read at the top of their sheet, what the importer was looking
     * for, and the shortest route to a file that works.
     *
     * @param array<int,string> $recognised column index => field key
     * @param array<string,string> $lookup   accepted heading => field key
     */
    private function headerNotFoundMessage(
        array $rows,
        string $fileName,
        array $recognised,
        array $lookup
    ): string {
        $where = $fileName !== '' ? '"' . $fileName . '"' : 'this file';

        $message = 'The column headings could not be found in ' . $where . '. ';

        $topRow = $this->firstFilledRow($rows);
        $message .= $topRow === null
            ? 'Its first sheet has no filled-in cells in the top rows. '
            : 'The first row with anything in it reads ' . $topRow . '. ';

        if (!empty($recognised)) {
            // A near miss is worth naming: usually one or two headings were
            // renamed and the rest of the row is fine.
            $names = array_map(
                fn ($key) => MediaImportSchema::labelFor($key),
                array_values($recognised)
            );
            $message .= 'Only ' . count($names) . ' of the template headings '
                . (count($names) === 1 ? 'was' : 'were') . ' recognised ('
                . implode(', ', $names) . '), and at least 3 are needed to identify the header row. ';
        }

        return $message
            . 'The importer looks through the first 10 rows of the first sheet for a row holding '
            . 'the template headings — ' . $this->requiredHeadingList() . '. '
            . 'Please open Step 1, use Download Template for the category you are importing, type '
            . 'your rows directly underneath the heading row it already contains, and upload that '
            . 'file. Keep your data on the first sheet and leave the heading row exactly as the '
            . 'template has it.';
    }

    /**
     * The required template headings as a readable list, e.g.
     * "Category, State, ... and Price (Monthly)".
     */
    private function requiredHeadingList(): string
    {
        $labels = MediaImportSchema::labels(MediaImportSchema::requiredColumns());

        if (count($labels) < 2) {
            return (string) ($labels[0] ?? '');
        }

        $last = array_pop($labels);

        return implode(', ', $labels) . ' and ' . $last;
    }

    /**
     * The first row holding any value, quoted and shortened for an error
     * message. Usually enough for the uploader to see at a glance that they
     * sent the wrong file, or left a title line where the headings belong.
     */
    private function firstFilledRow(array $rows): ?string
    {
        foreach (array_slice($rows, 0, 10) as $row) {
            $cells = array_values(array_filter(
                array_map(fn ($cell) => trim((string) $cell), (array) $row),
                fn ($cell) => $cell !== ''
            ));

            if (empty($cells)) {
                continue;
            }

            $shown = array_slice($cells, 0, 6);
            $text = '"' . implode('", "', $shown) . '"';
            $hidden = count($cells) - count($shown);

            return $hidden > 0 ? $text . ' and ' . $hidden . ' more' : $text;
        }

        return null;
    }

    /**
     * Whether any row names a category of its own — decides if a file can be
     * imported without one being picked on the Import tab.
     *
     * @param array<int,array<string,mixed>> $rows
     */
    private function anyCategoryFilled(array $rows): bool
    {
        foreach ($rows as $row) {
            if (!$this->blank($row['category'] ?? '')) {
                return true;
            }
        }

        return false;
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

            if (is_string($value)) {
                $value = trim($value);

                // MediaExport writes a dash wherever a record has no value, so
                // an exported file that is edited and sent back would otherwise
                // arrive asking for an Illumination literally named "-".
                if (in_array($value, self::EMPTY_MARKERS, true)) {
                    $value = '';
                }
            }

            $row[$key] = $value;
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

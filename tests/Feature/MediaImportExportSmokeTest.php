<?php

namespace Tests\Feature;

use Tests\TestCase;

class MediaImportExportSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // phpunit.xml forces sqlite/:memory:; this suite is a read-mostly smoke
        // test against the real development database.
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.port' => '3307',
            'database.connections.mysql.database' => 'new_brand_image',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
        ]);
        \Illuminate\Support\Facades\DB::purge('mysql');

        try {
            \Illuminate\Support\Facades\DB::connection('mysql')->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Development MySQL database is not reachable.');
        }

        // The first request of every test method never matches a route in this
        // app's test setup, so burn one on a trivial endpoint.
        $this->get('/test');

        // Publishing writes a row per file into media_import_history. Noting the
        // high-water mark lets tearDown remove only what this test added, so
        // repeated runs neither pile up rows nor make a later run believe its own
        // fixture file has "already been imported".
        $this->historyMark = \Illuminate\Support\Facades\Schema::hasTable('media_import_history')
            ? (int) \Illuminate\Support\Facades\DB::table('media_import_history')->max('id')
            : null;
    }

    protected function tearDown(): void
    {
        if ($this->historyMark !== null) {
            \Illuminate\Support\Facades\DB::table('media_import_history')
                ->where('id', '>', $this->historyMark)
                ->delete();
        }

        parent::tearDown();
    }

    /** Highest media_import_history id that existed before this test ran. */
    private ?int $historyMark = null;

    private function admin()
    {
        return $this->withSession(['user_id' => 1, 'name' => 'Test Admin']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/media/import-export')->assertRedirect(route('login'));
    }

    public function test_import_export_tab_renders(): void
    {
        $response = $this->admin()->get('/media/import-export');

        $response->assertOk();
        $response->assertSee('Bulk Data — Import &amp; Export', false);
        $response->assertSee('Download Template', false);
        $response->assertSee('Import Data', false);
        $response->assertSee('Export Matching Records', false);
        $response->assertSee('Columns you must fill', false);
        $response->assertViewHas('options');
    }

    public function test_export_tab_preselected_via_query(): void
    {
        $response = $this->admin()->get('/media/import-export?tab=export');
        $response->assertOk();
        $response->assertViewHas('activeTab', 'export');
    }

    public function test_template_downloads_as_xlsx(): void
    {
        $response = $this->admin()->get('/media/import/template');

        $response->assertOk();
        $this->assertStringContainsString(
            'media-bulk-upload-template.xlsx',
            $response->headers->get('content-disposition')
        );
    }

    /**
     * The exported workbook's picture columns must be clickable, while the cell
     * text stays exactly as written so an edited export can still be imported.
     */
    public function test_exported_image_columns_are_hyperlinks(): void
    {
        // A base path ending in a slash, as configured in production — the source
        // of the doubled slash in the URLs.
        config(['fileConstants.IMAGE_VIEW' => 'https://brand-image.co.in/storage/app/public//upload/images/media/']);

        $response = $this->admin()->get('/media/export?format=xlsx');
        $response->assertOk();

        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx')
            ->load($response->baseResponse->getFile()->getPathname())
            ->getActiveSheet();

        // Find the columns by their headings rather than by a fixed letter.
        $columns = [];
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
        for ($index = 1; $index <= $lastColumn; $index++) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
            $columns[trim((string) $sheet->getCell($letter . '1')->getValue())] = $letter;
        }

        $this->assertArrayHasKey('Image URLs', $columns);
        $this->assertArrayHasKey('Panorama Image URL', $columns);

        $linked = 0;

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            foreach (['Image URLs', 'Panorama Image URL'] as $heading) {
                $cell = $sheet->getCell($columns[$heading] . $row);
                $text = (string) $cell->getValue();
                $url = $cell->getHyperlink()->getUrl();

                if ($text === '' || $text === '-') {
                    $this->assertSame('', $url, 'an empty picture cell must not be a link');
                    continue;
                }

                $this->assertNotSame('', $url, "{$heading} must be clickable when it names a picture");
                // One hyperlink per cell is all Excel allows, so a cell naming
                // several pictures links to the first of them.
                $this->assertStringStartsWith($url, $text);
                // The text is untouched, so the importer can still read it back.
                $this->assertStringContainsString('https://', $text);
                $this->assertStringNotContainsString('public//upload', $url, 'doubled slash must be collapsed');

                $linked++;
            }
        }

        $this->assertGreaterThan(0, $linked, 'at least one picture cell must be a hyperlink');
    }

    public function test_export_downloads_xlsx_and_csv(): void
    {
        $xlsx = $this->admin()->get('/media/export?format=xlsx');
        $xlsx->assertOk();
        $this->assertStringContainsString('.xlsx', $xlsx->headers->get('content-disposition'));

        $csv = $this->admin()->get('/media/export?format=csv');
        $csv->assertOk();
        $this->assertStringContainsString('.csv', $csv->headers->get('content-disposition'));
    }

    public function test_export_via_post_with_selected_ids(): void
    {
        $ids = \Illuminate\Support\Facades\DB::table('media_management')
            ->where('is_deleted', 0)->limit(2)->pluck('id')->all();

        $response = $this->admin()->post('/media/export', [
            'format' => 'csv',
            'ids' => implode(',', $ids),
        ]);

        $response->assertOk();
        $this->assertStringContainsString('.csv', $response->headers->get('content-disposition'));

        // Header row + exactly the selected records, numbered from 1.
        $csv = trim(file_get_contents($response->baseResponse->getFile()->getPathname()));
        $lines = explode("\n", $csv);
        $this->assertCount(count($ids) + 1, $lines);
        $this->assertStringStartsWith('"1"', $lines[1]);
        $this->assertStringStartsWith('"2"', $lines[2]);
    }

    public function test_export_with_no_matches_returns_error(): void
    {
        $this->admin()
            ->from('/media/import-export')
            ->get('/media/export?hoarding_code=NO_SUCH_CODE_XYZ')
            ->assertRedirect('/media/import-export')
            ->assertSessionHas('error');
    }

    public function test_record_picker_returns_json(): void
    {
        $response = $this->admin()->getJson('/media/export/records?per_page=5');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'total',
            'current_page',
            'last_page',
            // Drive the serial-number column and the pager.
            'per_page',
            'from',
            'data' => [['id', 'hoarding_code', 'media_title', 'category_name', 'city_name', 'vendor_name', 'price', 'status']],
        ]);

        $response->assertJsonPath('per_page', 5);
        $response->assertJsonPath('current_page', 1);
        $response->assertJsonPath('from', 1);
    }

    /**
     * Paging is done by the server: each request returns only its own page, and
     * 'from' lets the picker number rows continuously across pages.
     */
    public function test_record_picker_pages_on_the_server(): void
    {
        $first = $this->admin()->getJson('/media/export/records?per_page=2&page=1');
        $first->assertOk();

        $total = $first->json('total');

        if ($total < 3) {
            $this->markTestSkipped('Needs at least 3 media records to page.');
        }

        $this->assertCount(2, $first->json('data'), 'a page must hold only per_page rows');
        $this->assertSame(1, $first->json('from'));

        $second = $this->admin()->getJson('/media/export/records?per_page=2&page=2');
        $second->assertOk();

        $this->assertSame(2, $second->json('current_page'));
        // Serial numbering continues: row 1 of page 2 is record number 3.
        $this->assertSame(3, $second->json('from'));
        $this->assertSame((int) ceil($total / 2), $second->json('last_page'));

        // Different pages really are different records.
        $this->assertNotSame(
            array_column($first->json('data'), 'id'),
            array_column($second->json('data'), 'id')
        );
    }

    public function test_record_picker_rejects_a_silly_page_size(): void
    {
        // 0 would break the paginator; 9999 would defeat paging.
        $this->admin()->getJson('/media/export/records?per_page=0')
            ->assertOk()->assertJsonPath('per_page', 1);

        $this->admin()->getJson('/media/export/records?per_page=9999')
            ->assertOk()->assertJsonPath('per_page', 200);
    }

    public function test_upload_validation_rejects_bad_file(): void
    {
        $this->admin()
            ->from('/media/import-export')
            ->post('/media/import/preview', ['mode' => 'insert'])
            ->assertSessionHasErrors('file');
    }

    public function test_publish_with_unknown_token_fails_gracefully(): void
    {
        $this->admin()
            ->post('/media/import/publish', ['token' => 'does-not-exist'])
            ->assertRedirect(route('media.import-export'))
            ->assertSessionHas('error');
    }

    public function test_error_log_for_unknown_token_fails_gracefully(): void
    {
        $this->admin()
            ->get('/media/import/error-log/does-not-exist')
            ->assertRedirect(route('media.import-export'))
            ->assertSessionHas('error');
    }

    /**
     * Full round trip through the web layer: upload a sheet with one good and
     * one bad row, land on the preview screen, then cancel. Nothing is written
     * to the inventory because the batch is discarded rather than published.
     */
    public function test_upload_renders_preview_and_can_be_cancelled(): void
    {
        $labels = \App\Support\MediaImportSchema::labels();

        $valid = [
            'Media Title' => 'ZZHTTP Valid Row',
            'Category' => 'Hoardings/Billboards',
            'State' => 'Maharashtra',
            'District' => 'Nashik',
            'City' => 'Nashik',
            'Area' => 'Govind Nagar',
            'Vendor Code' => 'MAH_NAS_BIMPL',
            'Width (ft)' => '30',
            'Height (ft)' => '15',
            'Latitude' => '19.9515151',
            'Longitude' => '73.7515151',
            'Price (Monthly)' => '55000',
            'Illumination' => 'Front Lit',
            'Facing' => 'North',
            // Hoardings/Billboards requires this too (see categoryRules()).
            'Area Type' => 'Urbun',
            'Address' => 'HTTP test address',
        ];
        $invalid = array_merge($valid, [
            'Media Title' => 'ZZHTTP Bad Row',
            'State' => 'Neverland',
            'Latitude' => '19.9525252',
        ]);

        $toRow = fn (array $values) => array_map(fn ($label) => $values[$label] ?? '', $labels);

        $path = tempnam(sys_get_temp_dir(), 'zzhttp') . '.csv';
        $handle = fopen($path, 'w');
        fputcsv($handle, $labels);
        fputcsv($handle, $toRow($valid));
        fputcsv($handle, $toRow($invalid));
        fclose($handle);

        $before = \Illuminate\Support\Facades\DB::table('media_management')->count();

        $response = $this->admin()->post('/media/import/preview', [
            'mode' => 'insert',
            'file' => new \Illuminate\Http\UploadedFile($path, 'zzhttp.csv', 'text/csv', null, true),
        ]);

        $response->assertOk();
        $response->assertSee('Import Preview', false);
        $response->assertSee('ZZHTTP Valid Row', false);
        $response->assertSee('State &quot;Neverland&quot; does not exist in the State master', false);
        $response->assertSee('Download Error Log', false);
        $response->assertSee('Confirm &amp; Publish 1 Record', false);
        // Friendlier publish confirmation, built server side so it pluralises.
        $response->assertSee('Save this media record now?', false);
        $response->assertSee('new media record will be added to your inventory', false);

        $batch = $response->viewData('batch');
        $this->assertSame(1, $batch['summary']['ready']);
        $this->assertSame(1, $batch['summary']['failed']);

        // The error log for this staged batch is downloadable.
        $this->admin()
            ->get('/media/import/error-log/' . $batch['token'])
            ->assertOk();

        // Cancelling must leave the inventory untouched.
        $this->admin()
            ->post('/media/import/discard', ['token' => $batch['token']])
            ->assertRedirect(route('media.import-export'))
            ->assertSessionHas('success');

        $this->assertSame(
            $before,
            \Illuminate\Support\Facades\DB::table('media_management')->count(),
            'Cancelling an import must not change the inventory.'
        );

        @unlink($path);
    }

    /**
     * The two mode options are flex labels wrapping a plain radio.
     *
     * Bootstrap's custom-control puts the description inside an inline <label>,
     * and a block of text in an inline box escaped the card and overlapped the
     * option below it instead of making the card taller.
     */
    public function test_mode_options_are_laid_out_so_the_text_cannot_escape(): void
    {
        $html = $this->admin()->get('/media/import-export')->getContent();

        preg_match_all('#<label class="ie-mode".*?</label>#s', $html, $matches);
        $this->assertCount(2, $matches[0], 'two mode options');

        $this->assertStringNotContainsString('custom-control custom-radio', $html);

        // Each radio sits inside its own label, so clicking the card selects it
        // natively and needs no JavaScript shim.
        foreach (['modeInsert', 'modeUpsert'] as $id) {
            $this->assertMatchesRegularExpression(
                '#<label class="ie-mode" for="' . $id . '">\s*<input type="radio" id="' . $id . '"#',
                $html
            );
        }
        $this->assertStringNotContainsString("\$('.ie-mode').on('click'", $html);

        // Flex row, with the description as a block inside its own span.
        $this->assertMatchesRegularExpression('#\.ie-mode\s*\{[^}]*display:\s*flex#s', $html);
        $this->assertMatchesRegularExpression('#\.ie-mode-sub\s*\{[^}]*display:\s*block#s', $html);

        // Exactly one option starts selected, and it is the safe one.
        $this->assertSame(1, preg_match_all('#<input[^>]*name="mode"[^>]*checked#', $html));
        $this->assertMatchesRegularExpression(
            '#<input type="radio" id="modeInsert" name="mode" value="insert"\s+class="ie-mode-input" checked>#',
            $html
        );

        // The chosen option is shown by a drawn circle and a worded badge, not by
        // the browser's own radio, so it looks the same everywhere.
        $this->assertSame(2, preg_match_all('#class="ie-mode-dot"#', $html));
        $this->assertSame(2, preg_match_all('#class="ie-mode-flag">Selected#', $html));

        foreach ([
            '.ie-mode-input:checked ~ .ie-mode-dot {',
            '.ie-mode-input:checked ~ .ie-mode-dot::after',
            '.ie-mode-input:checked ~ .ie-mode-text .ie-mode-flag',
            '.ie-mode-input:focus ~ .ie-mode-dot',
            '.ie-mode:has(.ie-mode-input:checked)',
        ] as $rule) {
            $this->assertStringContainsString($rule, $html, "missing selected-state rule: {$rule}");
        }
    }

    public function test_media_list_still_renders_with_new_button(): void
    {
        $response = $this->admin()->get('/media/list');

        $response->assertOk();
        $response->assertSee('Import / Export', false);
    }

    /* =====================================================================
     |  UPLOAD FAILURES THE ADMIN HAS TO BE ABLE TO ACT ON
     ===================================================================== */

    /**
     * Write $rows to a temporary CSV and post it to the preview endpoint.
     */
    private function upload(array $rows, array $extra = [])
    {
        $path = tempnam(sys_get_temp_dir(), 'zzmsg') . '.csv';
        $handle = fopen($path, 'w');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        $response = $this->admin()
            ->from('/media/import-export')
            ->post('/media/import/preview', array_merge([
                'mode' => 'insert',
                'file' => new \Illuminate\Http\UploadedFile($path, 'zzmsg.csv', 'text/csv', null, true),
            ], $extra));

        @unlink($path);

        return $response;
    }

    public function test_unrecognisable_header_row_explains_itself(): void
    {
        $response = $this->upload([
            ['Monthly Site Report — Nashik Division'],
            ['Sr', 'Site', 'Size', 'Rent'],
            ['1', 'Mumbai Naka', '40x20', '85000'],
        ]);

        $response->assertRedirect('/media/import-export');
        $error = session('error');

        // Names the file, quotes what it actually read, and says what to do.
        $this->assertStringContainsString('column headings could not be found', $error);
        $this->assertStringContainsString('zzmsg.csv', $error);
        $this->assertStringContainsString('Monthly Site Report', $error);
        $this->assertStringContainsString('Download Template', $error);
        $this->assertStringNotContainsString('Could not find a valid header row', $error);
    }

    public function test_missing_category_without_a_selected_category_is_explained(): void
    {
        $labels = \App\Support\MediaImportSchema::labels();
        $values = [
            'State' => 'Maharashtra',
            'District' => 'Nashik',
            'City' => 'Nashik',
            'Area' => 'Govind Nagar',
            'Vendor Code' => 'MAH_NAS_BIMPL',
            'Width (ft)' => '30',
            'Height (ft)' => '15',
            'Latitude' => '19.9511111',
            'Longitude' => '73.7511111',
            'Price (Monthly)' => '55000',
            // Category deliberately left blank, and no category_id posted.
        ];

        $response = $this->upload([
            $labels,
            array_map(fn ($label) => $values[$label] ?? '', $labels),
        ]);

        $response->assertRedirect('/media/import-export');
        $error = session('error');

        $this->assertStringContainsString('choose a media category before uploading', $error);
        $this->assertStringContainsString('Category column is empty', $error);
        // The old behaviour was a per-row "Category is required" on every line.
        $this->assertStringNotContainsString('header row', $error);
    }

    public function test_header_row_with_no_data_rows_is_explained(): void
    {
        $response = $this->upload([\App\Support\MediaImportSchema::labels()]);

        $response->assertRedirect('/media/import-export');
        $this->assertStringContainsString('no data rows underneath it', session('error'));
    }

    /**
     * One vendor may hold several media at one GPS position — two faces of a
     * gantry, panels along a wall, a bus fleet at one depot. Such a row must
     * import, and only be flagged in case it was pasted twice.
     */
    public function test_same_vendor_and_gps_is_flagged_but_still_imports(): void
    {
        $existing = \Illuminate\Support\Facades\DB::table('media_management as m')
            ->join('vendors as v', 'v.id', '=', 'm.vendor_id')
            ->join('category as c', 'c.id', '=', 'm.category_id')
            ->join('states as s', 's.id', '=', 'm.state_id')
            ->join('districts as d', 'd.id', '=', 'm.district_id')
            ->join('cities as ci', 'ci.id', '=', 'm.city_id')
            ->join('areas as a', 'a.id', '=', 'm.area_id')
            ->where('m.is_deleted', 0)
            ->whereNotNull('m.hoarding_code')
            ->select([
                'm.hoarding_code', 'm.latitude', 'm.longitude', 'v.vendor_code',
                'c.category_name', 's.state_name', 'd.district_name',
                'ci.city_name', 'a.area_name',
            ])
            ->first();

        if (!$existing) {
            $this->markTestSkipped('No existing media record to duplicate.');
        }

        $labels = \App\Support\MediaImportSchema::labels();
        $values = [
            'Category' => $existing->category_name,
            'State' => $existing->state_name,
            'District' => $existing->district_name,
            'City' => $existing->city_name,
            'Area' => $existing->area_name,
            'Vendor Code' => $existing->vendor_code,
            // Same vendor + same GPS as the record above: the duplicate rule.
            'Latitude' => $existing->latitude,
            'Longitude' => $existing->longitude,
            'Width (ft)' => '30',
            'Height (ft)' => '15',
            'Price (Monthly)' => '55000',
            'Media Title' => 'ZZDUP Duplicate Row',
            'Facing' => 'North',
            'Area Type' => 'Urbun',
            'Illumination' => 'Front Lit',
            'Address' => 'ZZDUP address',
        ];

        $response = $this->upload([
            $labels,
            array_map(fn ($label) => $values[$label] ?? '', $labels),
        ]);

        $response->assertOk();
        $batch = $response->viewData('batch');

        // The row imports: not an error, just flagged.
        $this->assertSame(1, $batch['summary']['ready']);
        $this->assertSame(0, $batch['summary']['failed']);
        $this->assertSame([], $batch['errors']);

        $this->assertSame(1, $batch['summary']['flagged']);
        $warning = $batch['warnings'][0];
        $this->assertStringContainsString('Same vendor and GPS position', $warning['message']);
        // Still names the record it matches, so a real duplicate is spottable.
        $this->assertStringContainsString($existing->hoarding_code, $warning['message']);

        $response->assertSee('worth a quick check', false);
        $response->assertSee('will be imported', false);

        $this->admin()->post('/media/import/discard', ['token' => $batch['token']]);
    }

    /**
     * The same vendor and position repeated twice inside one file is also only
     * a flag, naming the earlier row.
     */
    public function test_same_vendor_and_gps_repeated_within_the_file_is_flagged(): void
    {
        $labels = \App\Support\MediaImportSchema::labels();
        $values = [
            'Category' => 'Hoardings/Billboards',
            'State' => 'Maharashtra',
            'District' => 'Nashik',
            'City' => 'Nashik',
            'Area' => 'Govind Nagar',
            'Vendor Code' => 'MAH_NAS_BIMPL',
            'Width (ft)' => '30',
            'Height (ft)' => '15',
            'Latitude' => '19.9588888',
            'Longitude' => '73.7588888',
            'Price (Monthly)' => '55000',
            'Media Title' => 'ZZTWIN Face A',
            'Facing' => 'North',
            'Area Type' => 'Urbun',
            'Illumination' => 'Front Lit',
            'Address' => 'ZZTWIN address',
        ];

        // Two faces of one structure: identical vendor and coordinates.
        $faceB = array_merge($values, ['Media Title' => 'ZZTWIN Face B', 'Facing' => 'South']);

        $response = $this->upload([
            $labels,
            array_map(fn ($label) => $values[$label] ?? '', $labels),
            array_map(fn ($label) => $faceB[$label] ?? '', $labels),
        ]);

        $response->assertOk();
        $batch = $response->viewData('batch');

        // Both faces import.
        $this->assertSame(2, $batch['summary']['ready']);
        $this->assertSame(0, $batch['summary']['failed']);

        $this->assertSame(1, $batch['summary']['flagged']);
        $this->assertStringContainsString(
            'Same vendor and GPS position as row 2',
            $batch['warnings'][0]['message']
        );

        $this->admin()->post('/media/import/discard', ['token' => $batch['token']]);
    }

    /* =====================================================================
     |  UPDATING EXISTING MEDIA (upsert)
     ===================================================================== */

    /**
     * Only hoardings are given a Hoarding Code, so only the Hoardings/Billboards
     * template offers the column — asking for a code on a mall or transit sheet
     * would be asking for something that record can never have. The
     * all-categories template keeps it, since a mixed sheet may hold hoardings
     * and it is the one column that turns an upload into an update.
     */
    public function test_only_the_hoarding_template_offers_the_hoarding_code_column(): void
    {
        $categories = \Illuminate\Support\Facades\DB::table('category')
            ->where('is_deleted', 0)->pluck('category_name');

        $this->assertNotEmpty($categories);

        $this->assertContains(
            'Hoarding Code',
            \App\Support\MediaImportSchema::labels(),
            'the all-categories template must keep Hoarding Code'
        );

        foreach ($categories as $name) {
            $slug = \Illuminate\Support\Str::slug($name);
            $labels = \App\Support\MediaImportSchema::labels(
                \App\Support\MediaImportSchema::columnsForSlug($slug)
            );

            if (str_contains($slug, 'hoarding') || str_contains($slug, 'billboard')) {
                $this->assertContains('Hoarding Code', $labels, "{$name} template is missing Hoarding Code");
            } else {
                $this->assertNotContains('Hoarding Code', $labels, "{$name} template must not offer Hoarding Code");
            }
        }
    }

    /**
     * HD###### is a hoarding's code. A bulk upload issues one only to rows in
     * Hoardings/Billboards — media in every other category is stored without a
     * code rather than being handed a number that means nothing.
     */
    public function test_only_hoardings_are_given_a_hoarding_code_on_import(): void
    {
        $labels = \App\Support\MediaImportSchema::labels();

        $shared = [
            'State' => 'Maharashtra', 'District' => 'Nashik', 'City' => 'Nashik',
            'Area' => 'Govind Nagar', 'Vendor Code' => 'MAH_NAS_BIMPL',
            'Width (ft)' => '30', 'Height (ft)' => '15', 'Price (Monthly)' => '55000',
        ];

        $hoarding = array_merge($shared, [
            'Category' => 'Hoardings/Billboards',
            'Latitude' => '19.9711111', 'Longitude' => '73.7711111',
            'Media Title' => 'ZZCODE Hoarding', 'Facing' => 'North',
            'Area Type' => 'Urbun', 'Illumination' => 'Front Lit',
            'Address' => 'ZZCODE address',
        ]);

        $wallWrap = array_merge($shared, [
            'Category' => 'Wall Wrap',
            'Latitude' => '19.9722222', 'Longitude' => '73.7722222',
            'Media Title' => 'ZZCODE Wall Wrap',
        ]);

        $coordinates = ['19.9711111', '19.9722222'];

        try {
            $response = $this->upload([
                $labels,
                array_map(fn ($label) => $hoarding[$label] ?? '', $labels),
                array_map(fn ($label) => $wallWrap[$label] ?? '', $labels),
            ]);

            $response->assertOk();
            $batch = $response->viewData('batch');

            $this->assertSame([], $batch['errors']);
            $this->assertSame(2, $batch['summary']['ready']);

            // The preview says up front which row is getting a code.
            $this->assertSame('Auto', $batch['rows'][0]['display']['hoarding_code']);
            $this->assertSame('—', $batch['rows'][1]['display']['hoarding_code']);

            $this->admin()->post('/media/import/publish', ['token' => $batch['token']]);

            $codes = \Illuminate\Support\Facades\DB::table('media_management')
                ->whereIn('latitude', $coordinates)->orderBy('latitude')
                ->pluck('hoarding_code', 'media_title')->all();

            $this->assertCount(2, $codes);
            $this->assertMatchesRegularExpression('/^HD\d{6}$/', (string) $codes['ZZCODE Hoarding']);
            $this->assertNull($codes['ZZCODE Wall Wrap'], 'a wall wrap must not be given a hoarding code');
        } finally {
            \Illuminate\Support\Facades\DB::table('media_management')
                ->whereIn('latitude', $coordinates)->delete();
        }
    }

    /**
     * A code typed into the sheet is still honoured whatever the category —
     * the rule only stops codes being invented, it does not throw away one the
     * admin supplied (that is how an upsert names the record to change).
     */
    public function test_a_supplied_code_is_kept_for_a_non_hoarding_row(): void
    {
        $labels = \App\Support\MediaImportSchema::labels();

        $values = [
            'Category' => 'Wall Wrap', 'Hoarding Code' => 'ZZWW000001',
            'State' => 'Maharashtra', 'District' => 'Nashik', 'City' => 'Nashik',
            'Area' => 'Govind Nagar', 'Vendor Code' => 'MAH_NAS_BIMPL',
            'Width (ft)' => '30', 'Height (ft)' => '15', 'Price (Monthly)' => '55000',
            'Latitude' => '19.9733333', 'Longitude' => '73.7733333',
            'Media Title' => 'ZZCODE Supplied',
        ];

        try {
            $response = $this->upload([
                $labels,
                array_map(fn ($label) => $values[$label] ?? '', $labels),
            ]);

            $response->assertOk();
            $batch = $response->viewData('batch');

            $this->assertSame([], $batch['errors']);
            $this->assertSame('ZZWW000001', $batch['rows'][0]['display']['hoarding_code']);

            $this->admin()->post('/media/import/publish', ['token' => $batch['token']]);

            $this->assertSame('ZZWW000001', \Illuminate\Support\Facades\DB::table('media_management')
                ->where('latitude', '19.9733333')->value('hoarding_code'));
        } finally {
            \Illuminate\Support\Facades\DB::table('media_management')
                ->where('latitude', '19.9733333')->delete();
        }
    }

    /**
     * MediaExport writes "-" into every empty field, so an exported file that
     * is edited and sent straight back must not be read as asking for an
     * Illumination literally named "-".
     */
    public function test_dash_placeholders_from_an_export_are_read_as_empty(): void
    {
        $labels = \App\Support\MediaImportSchema::labels();
        $values = [
            'Category' => 'Hoardings/Billboards',
            'State' => 'Maharashtra', 'District' => 'Nashik', 'City' => 'Nashik',
            'Area' => 'Govind Nagar', 'Vendor Code' => 'MAH_NAS_BIMPL',
            'Width (ft)' => '30', 'Height (ft)' => '15',
            'Latitude' => '19.9577777', 'Longitude' => '73.7577777',
            'Price (Monthly)' => '55000', 'Media Title' => 'ZZDASH Row',
            'Facing' => 'North', 'Area Type' => 'Urbun', 'Illumination' => 'Front Lit',
            'Address' => 'ZZDASH address',
            // Exactly what an export puts in the columns this record has no
            // value for. None of these may be looked up as real master names.
            'Highway' => '-', 'Landmarks' => '-', 'Media Code' => '-',
            'Vehicle Count' => '-', 'Mall Name' => '-', 'Media Format' => '-',
            'Media Type' => '-', 'Panorama Image URL' => '-', 'Image URLs' => '-',
        ];

        $response = $this->upload([
            $labels,
            array_map(fn ($label) => $values[$label] ?? '', $labels),
        ]);

        $response->assertOk();
        $batch = $response->viewData('batch');

        $this->assertSame([], $batch['errors'], 'dash placeholders must not fail validation');
        $this->assertSame(1, $batch['summary']['ready']);

        $this->admin()->post('/media/import/discard', ['token' => $batch['token']]);
    }

    /**
     * The reported bug: export a record, change one cell, upload it back with
     * "Add new and update existing" — the record must change in place and
     * nothing may be appended.
     */
    public function test_editing_an_exported_row_updates_it_instead_of_adding(): void
    {
        // A category with no extra mandatory fields, so the round trip tests the
        // update path rather than this installation's older records happening to
        // be missing a field their category now requires.
        $target = \Illuminate\Support\Facades\DB::table('media_management as m')
            ->join('category as c', 'c.id', '=', 'm.category_id')
            ->where('m.is_deleted', 0)->whereNotNull('m.hoarding_code')
            ->where(function ($q) {
                $q->where('c.category_name', 'like', '%Wall%')
                    ->orWhere('c.category_name', 'like', '%Wrap%');
            })
            ->select(['m.id', 'm.hoarding_code', 'm.width'])
            ->first();

        if (!$target) {
            $this->markTestSkipped('No wall painting / wall wrap record to update.');
        }

        $before = \Illuminate\Support\Facades\DB::table('media_management')->count();
        $imagesBefore = \Illuminate\Support\Facades\DB::table('media_images')
            ->where('media_id', $target->id)->where('is_deleted', 0)->count();

        $csv = $this->admin()->get('/media/export?format=csv&hoarding_code=' . $target->hoarding_code);
        $csv->assertOk();

        $lines = array_values(array_filter(explode("\n", trim(
            file_get_contents($csv->baseResponse->getFile()->getPathname())
        ))));
        $rows = array_map(fn ($line) => str_getcsv(trim($line)), $lines);

        $widthColumn = array_search('Width (ft)', $rows[0], true);
        $this->assertNotFalse($widthColumn);

        $newWidth = ((float) $target->width) + 7;
        $rows[1][$widthColumn] = (string) $newWidth;

        $response = $this->upload([$rows[0], $rows[1]], ['mode' => 'upsert']);
        $response->assertOk();

        $batch = $response->viewData('batch');
        $this->assertSame([], $batch['errors']);
        $this->assertSame(1, $batch['summary']['update'], 'must update, not insert');
        $this->assertSame(0, $batch['summary']['insert']);

        $this->admin()->post('/media/import/publish', ['token' => $batch['token']])
            ->assertRedirect(route('media.list'));

        $after = \Illuminate\Support\Facades\DB::table('media_management')->count();
        $row = \Illuminate\Support\Facades\DB::table('media_management')->find($target->id);

        $this->assertSame($before, $after, 'an update must not append a row');
        $this->assertSame($newWidth, (float) $row->width);
        $this->assertSame($target->hoarding_code, $row->hoarding_code);

        // The exported sheet lists the record's own pictures; re-importing it
        // must not stack a second copy of each one.
        $this->assertSame(
            $imagesBefore,
            \Illuminate\Support\Facades\DB::table('media_images')
                ->where('media_id', $target->id)->where('is_deleted', 0)->count(),
            'updating from an exported sheet must not duplicate the images'
        );

        \Illuminate\Support\Facades\DB::table('media_management')
            ->where('id', $target->id)->update(['width' => $target->width]);
    }

    /**
     * A category template carries only its own category's columns — no Status,
     * no Media Code. Updating with one must leave the columns it does not carry
     * exactly as they were, rather than blanking them (or, for Status, silently
     * reactivating a record that was switched off).
     */
    public function test_update_only_writes_the_columns_the_file_supplies(): void
    {
        $target = \Illuminate\Support\Facades\DB::table('media_management as m')
            ->join('category as c', 'c.id', '=', 'm.category_id')
            ->where('m.is_deleted', 0)->whereNotNull('m.hoarding_code')
            ->where('c.category_name', 'like', '%Hoardings%')
            ->select('m.*', 'c.category_name')
            ->first();

        if (!$target) {
            $this->markTestSkipped('No hoardings record to update.');
        }

        $columns = \App\Support\MediaImportSchema::columnsForSlug(
            \Illuminate\Support\Str::slug($target->category_name)
        );
        $labels = \App\Support\MediaImportSchema::labels($columns);

        // The premise: this template has no Status and no Media Code column.
        $this->assertNotContains('Status', $labels);
        $this->assertNotContains('Media Code', $labels);

        $restore = [
            'is_active' => $target->is_active,
            'media_code' => $target->media_code,
            'price' => $target->price,
        ];

        // Put the record in the state that exposes the bug: switched off, with a
        // media code that an update must not clear.
        \Illuminate\Support\Facades\DB::table('media_management')
            ->where('id', $target->id)
            ->update(['is_active' => 0, 'media_code' => 'ZZKEEP001']);

        try {
            $lookup = fn ($table, $id, $column) => \Illuminate\Support\Facades\DB::table($table)
                ->where('id', $id)->value($column);

            $newPrice = ((float) $target->price) + 9;
            $values = [
                'Hoarding Code' => $target->hoarding_code,
                'Category' => $target->category_name,
                'State' => $lookup('states', $target->state_id, 'state_name'),
                'District' => $lookup('districts', $target->district_id, 'district_name'),
                'City' => $lookup('cities', $target->city_id, 'city_name'),
                'Area' => $lookup('areas', $target->area_id, 'area_name'),
                'Vendor Code' => $lookup('vendors', $target->vendor_id, 'vendor_code'),
                'Width (ft)' => $target->width,
                'Height (ft)' => $target->height,
                'Latitude' => $target->latitude,
                'Longitude' => $target->longitude,
                'Price (Monthly)' => (string) $newPrice,
                'Media Title' => $target->media_title ?: 'ZZKEEP title',
                'Facing' => $target->facing ?: 'North',
                'Area Type' => 'Urbun',
                'Illumination' => $lookup('illuminations', $target->illumination_id, 'illumination_name')
                    ?: 'Front Lit',
                'Address' => $target->address ?: 'ZZKEEP address',
            ];

            $response = $this->upload([
                $labels,
                array_map(fn ($label) => $values[$label] ?? '', $labels),
            ], ['mode' => 'upsert']);

            $response->assertOk();
            $batch = $response->viewData('batch');
            $this->assertSame([], $batch['errors']);
            $this->assertSame(1, $batch['summary']['update']);

            // The payload must simply not mention the columns the file lacks.
            $written = array_keys($batch['rows'][0]['payload']);
            $this->assertNotContains('is_active', $written);
            $this->assertNotContains('media_code', $written);
            $this->assertContains('price', $written);

            $this->admin()->post('/media/import/publish', ['token' => $batch['token']]);

            $after = \Illuminate\Support\Facades\DB::table('media_management')->find($target->id);

            $this->assertSame($newPrice, (float) $after->price, 'price must change');
            $this->assertSame(0, (int) $after->is_active, 'absent Status must not reactivate the record');
            $this->assertSame('ZZKEEP001', $after->media_code, 'absent Media Code must not be cleared');
        } finally {
            \Illuminate\Support\Facades\DB::table('media_management')
                ->where('id', $target->id)->update($restore);
        }
    }

    /**
     * The same file in "Add new records only" mode must refuse the row rather
     * than quietly duplicating the record, and say which mode to use.
     */
    public function test_existing_code_in_insert_mode_is_refused_with_advice(): void
    {
        $target = \Illuminate\Support\Facades\DB::table('media_management')
            ->where('is_deleted', 0)->whereNotNull('hoarding_code')->first();

        if (!$target) {
            $this->markTestSkipped('No media record to clash with.');
        }

        $labels = \App\Support\MediaImportSchema::labels();
        $values = [
            'Hoarding Code' => $target->hoarding_code,
            'Category' => 'Hoardings/Billboards',
            'State' => 'Maharashtra', 'District' => 'Nashik', 'City' => 'Nashik',
            'Area' => 'Govind Nagar', 'Vendor Code' => 'MAH_NAS_BIMPL',
            'Width (ft)' => '30', 'Height (ft)' => '15',
            'Latitude' => '19.9566666', 'Longitude' => '73.7566666',
            'Price (Monthly)' => '55000', 'Media Title' => 'ZZINS Row',
            'Facing' => 'North', 'Area Type' => 'Urbun', 'Illumination' => 'Front Lit',
            'Address' => 'ZZINS address',
        ];

        $response = $this->upload([
            $labels,
            array_map(fn ($label) => $values[$label] ?? '', $labels),
        ]);

        $response->assertOk();
        $batch = $response->viewData('batch');

        $this->assertSame(0, $batch['summary']['ready']);
        $this->assertSame(1, $batch['summary']['failed']);
        $this->assertStringContainsString('already exists', $batch['errors'][0]['issues']);
        $this->assertStringContainsString('Update existing records', $batch['errors'][0]['issues']);
        $this->assertSame($target->hoarding_code, $batch['errors'][0]['existing_code']);

        $this->admin()->post('/media/import/discard', ['token' => $batch['token']]);
    }

    /**
     * A sheet with blank Hoarding Codes always adds new records — the codes it
     * receives are generated here and never written back into the admin's file.
     * Uploading that same untouched file again must therefore be called out
     * before it doubles everything, rather than quietly duplicating.
     */
    public function test_reuploading_the_same_published_file_is_flagged(): void
    {
        $labels = \App\Support\MediaImportSchema::labels(
            \App\Support\MediaImportSchema::columnsForSlug('wall-wrap')
        );

        $values = [
            'Category' => 'Wall Wrap',
            'State' => 'Maharashtra', 'District' => 'Nashik', 'City' => 'Nashik',
            'Area' => 'Govind Nagar', 'Vendor Code' => 'MAH_NAS_BIMPL',
            'Width (ft)' => '12', 'Height (ft)' => '8',
            'Latitude' => '19.9633333', 'Longitude' => '73.7633333',
            'Price (Monthly)' => '12321',
            // Hoarding Code left blank, exactly as a fresh template arrives.
        ];

        $rows = [$labels, array_map(fn ($label) => $values[$label] ?? '', $labels)];
        $before = \Illuminate\Support\Facades\DB::table('media_management')->count();

        try {
            // First upload: nothing to warn about.
            $first = $this->upload($rows);
            $first->assertOk();
            $firstBatch = $first->viewData('batch');
            $this->assertNull($firstBatch['already_published']);

            $this->admin()->post('/media/import/publish', ['token' => $firstBatch['token']]);
            $this->assertSame(
                $before + 1,
                \Illuminate\Support\Facades\DB::table('media_management')->count()
            );

            // The very same bytes again. The file is recognised, and on top of
            // that the row itself is refused for already being in the inventory —
            // so there is nothing left to publish at all.
            $second = $this->upload($rows);
            $second->assertOk();
            $secondBatch = $second->viewData('batch');

            $this->assertNotNull($secondBatch['already_published'], 're-upload must be flagged');
            $this->assertSame(1, $secondBatch['already_published']['inserted']);
            $second->assertSee('You have already imported this file', false);

            $this->assertSame(0, $secondBatch['summary']['ready'], 'nothing may be publishable');
            $this->assertSame(1, $secondBatch['summary']['failed']);
            $this->assertStringContainsString(
                'already in the inventory as',
                $secondBatch['errors'][0]['issues']
            );

            // With no publishable row there is no publish button to press.
            $html = $second->getContent();
            $this->assertStringNotContainsString('id="publishBtn"', $html);
            $this->assertStringNotContainsString('Records ready to publish', $html);

            $this->assertSame(
                $before + 1,
                \Illuminate\Support\Facades\DB::table('media_management')->count()
            );

            $this->admin()->post('/media/import/discard', ['token' => $secondBatch['token']]);

            /* ---- the case the file-level warning still exists for ----
               The record is deleted, so the row is no longer a duplicate of
               anything and would import. Re-importing after a deletion is
               legitimate, so it stays possible — but has to be asked for. */
            \Illuminate\Support\Facades\DB::table('media_management')
                ->where('latitude', '19.9633333')->where('price', 12321)->delete();

            $third = $this->upload($rows);
            $third->assertOk();
            $thirdBatch = $third->viewData('batch');

            $this->assertNotNull($thirdBatch['already_published']);
            $this->assertSame(1, $thirdBatch['summary']['insert']);

            $thirdHtml = $third->getContent();
            $this->assertStringContainsString('id="publishBtn" disabled', $thirdHtml);
            $this->assertStringContainsString('allowDuplicateImport', $thirdHtml);
            $this->assertStringContainsString('would be added again as duplicate', $thirdHtml);
            $this->assertStringNotContainsString('Records ready to publish', $thirdHtml);

            $this->admin()->post('/media/import/discard', ['token' => $thirdBatch['token']]);

            // A file that has not been published before stays in the normal
            // green state, so the gating cannot leak into ordinary imports.
            $freshValues = array_merge($values, [
                'Latitude' => '19.9644444', 'Longitude' => '73.7644444',
            ]);
            $fresh = $this->upload([
                $labels,
                array_map(fn ($label) => $freshValues[$label] ?? '', $labels),
            ]);
            $freshHtml = $fresh->getContent();

            $this->assertStringContainsString('Records ready to publish', $freshHtml);
            $this->assertStringNotContainsString('id="publishBtn" disabled', $freshHtml);

            $this->admin()->post('/media/import/discard', [
                'token' => $fresh->viewData('batch')['token'],
            ]);
        } finally {
            \Illuminate\Support\Facades\DB::table('media_management')
                ->where('latitude', '19.9633333')->where('price', 12321)->delete();
        }

        $this->assertSame($before, \Illuminate\Support\Facades\DB::table('media_management')->count());
    }

    /**
     * A sheet holding rows that are already in the inventory plus one genuinely
     * new row must add only the new one. Sharing a vendor and GPS position is
     * not enough to call two rows the same media — a site carries several faces —
     * but when every value the sheet supplies matches too, the row is that media
     * coming round again and there is nothing to add.
     */
    public function test_rows_identical_to_existing_records_are_not_added_again(): void
    {
        $labels = \App\Support\MediaImportSchema::labels(
            \App\Support\MediaImportSchema::columnsForSlug('wall-wrap')
        );

        $row = fn (array $overrides) => array_map(fn ($label) => array_merge([
            'Category' => 'Wall Wrap',
            'State' => 'Maharashtra', 'District' => 'Nashik', 'City' => 'Nashik',
            'Area' => 'Govind Nagar', 'Vendor Code' => 'MAH_NAS_BIMPL',
            'Width (ft)' => '400', 'Height (ft)' => '150', 'Price (Monthly)' => '85000',
        ], $overrides)[$label] ?? '', $labels);

        $coordinates = ['19.9811111', '19.9822222', '19.9833333', '19.9844444'];

        $three = [
            $labels,
            $row(['Latitude' => $coordinates[0], 'Longitude' => '73.7811111']),
            $row(['Latitude' => $coordinates[1], 'Longitude' => '73.7822222', 'Price (Monthly)' => '45000']),
            $row(['Latitude' => $coordinates[2], 'Longitude' => '73.7833333', 'Price (Monthly)' => '60000']),
        ];

        $before = \Illuminate\Support\Facades\DB::table('media_management')->count();

        try {
            // Publish the three.
            $first = $this->upload($three);
            $first->assertOk();
            $this->admin()->post('/media/import/publish', [
                'token' => $first->viewData('batch')['token'],
            ]);

            $afterThree = \Illuminate\Support\Facades\DB::table('media_management')->count();
            $this->assertSame($before + 3, $afterThree);

            // The same three, plus one that really is new.
            $four = $three;
            $four[] = $row([
                'Latitude' => $coordinates[3], 'Longitude' => '73.7844444',
                'Width (ft)' => '200', 'Height (ft)' => '70', 'Price (Monthly)' => '40000',
            ]);

            $second = $this->upload($four);
            $second->assertOk();
            $batch = $second->viewData('batch');

            $this->assertSame(4, $batch['summary']['total_rows']);
            $this->assertSame(1, $batch['summary']['ready'], 'only the genuinely new row');
            $this->assertSame(3, $batch['summary']['failed'], 'the three already in the inventory');

            // Each refusal names the record it duplicates.
            foreach ($batch['errors'] as $error) {
                $this->assertStringContainsString('already in the inventory as', $error['issues']);
                $this->assertNotSame('', $error['existing_code']);
            }

            $this->admin()->post('/media/import/publish', ['token' => $batch['token']]);
            $this->assertSame(
                $afterThree + 1,
                \Illuminate\Support\Facades\DB::table('media_management')->count(),
                'exactly one row may be added'
            );

            // In upsert mode the identical rows resolve to their records instead.
            $third = $this->upload($four, ['mode' => 'upsert']);
            $third->assertOk();
            $upsert = $third->viewData('batch');

            $this->assertSame(0, $upsert['summary']['insert'], 'nothing new to add');
            $this->assertSame(4, $upsert['summary']['update'], 'each row matched to its record');

            $countBeforePublish = \Illuminate\Support\Facades\DB::table('media_management')->count();
            $this->admin()->post('/media/import/publish', ['token' => $upsert['token']]);
            $this->assertSame(
                $countBeforePublish,
                \Illuminate\Support\Facades\DB::table('media_management')->count(),
                'matching to existing records must not append rows'
            );
        } finally {
            \Illuminate\Support\Facades\DB::table('media_management')
                ->whereIn('latitude', $coordinates)->delete();
        }

        $this->assertSame($before, \Illuminate\Support\Facades\DB::table('media_management')->count());
    }

    /**
     * The reported case: change nothing but the price and re-upload with
     * "Add new and update existing". Without a Hoarding Code the row is matched
     * to the record at that vendor and GPS position, so the price reaches the
     * inventory instead of a second copy being added.
     */
    public function test_changing_only_the_price_updates_instead_of_adding(): void
    {
        $labels = \App\Support\MediaImportSchema::labels(
            \App\Support\MediaImportSchema::columnsForSlug('wall-wrap')
        );

        $row = fn (array $overrides) => array_map(fn ($label) => array_merge([
            'Category' => 'Wall Wrap',
            'State' => 'Maharashtra', 'District' => 'Nashik', 'City' => 'Nashik',
            'Area' => 'Govind Nagar', 'Vendor Code' => 'MAH_NAS_BIMPL',
            'Width (ft)' => '400', 'Height (ft)' => '150',
        ], $overrides)[$label] ?? '', $labels);

        $coordinates = ['19.9911111', '19.9922222'];
        $before = \Illuminate\Support\Facades\DB::table('media_management')->count();

        try {
            $this->admin()->post('/media/import/publish', [
                'token' => $this->upload([
                    $labels,
                    $row(['Latitude' => $coordinates[0], 'Longitude' => '73.7911111', 'Price (Monthly)' => '85000']),
                    $row(['Latitude' => $coordinates[1], 'Longitude' => '73.7922222', 'Price (Monthly)' => '60000']),
                ])->viewData('batch')['token'],
            ]);

            $created = \Illuminate\Support\Facades\DB::table('media_management')
                ->whereIn('latitude', $coordinates)->orderBy('id')
                ->pluck('hoarding_code', 'id')->all();

            $this->assertCount(2, $created);
            $countAfterInsert = \Illuminate\Support\Facades\DB::table('media_management')->count();

            // Only the prices differ now.
            $response = $this->upload([
                $labels,
                $row(['Latitude' => $coordinates[0], 'Longitude' => '73.7911111', 'Price (Monthly)' => '99999']),
                $row(['Latitude' => $coordinates[1], 'Longitude' => '73.7922222', 'Price (Monthly)' => '20000']),
            ], ['mode' => 'upsert']);

            $response->assertOk();
            $batch = $response->viewData('batch');

            $this->assertSame([], $batch['errors']);
            $this->assertSame(0, $batch['summary']['insert'], 'must not add new records');
            $this->assertSame(2, $batch['summary']['update'], 'both rows must be updates');

            $this->admin()->post('/media/import/publish', ['token' => $batch['token']]);

            $this->assertSame(
                $countAfterInsert,
                \Illuminate\Support\Facades\DB::table('media_management')->count(),
                'an update must not append rows'
            );

            $after = \Illuminate\Support\Facades\DB::table('media_management')
                ->whereIn('latitude', $coordinates)->orderBy('id')
                ->pluck('hoarding_code', 'id')->all();

            $this->assertEqualsCanonicalizing(
                [99999.0, 20000.0],
                \Illuminate\Support\Facades\DB::table('media_management')
                    ->whereIn('latitude', $coordinates)->pluck('price')
                    ->map(fn ($price) => (float) $price)->all(),
                'the new prices must be stored'
            );

            // A row matched on vendor and GPS carries no code of its own, and that
            // blank must never be written over whatever the record holds — for a
            // hoarding it would strip the code and let the next import reissue it.
            $this->assertSame($created, $after, 'the hoarding codes must survive the update');

            // Wall Wrap is not a hoarding, so no HD###### was ever minted for it.
            foreach ($after as $code) {
                $this->assertNull($code, 'only hoardings may carry a hoarding code');
            }
        } finally {
            \Illuminate\Support\Facades\DB::table('media_management')
                ->whereIn('latitude', $coordinates)->delete();
        }

        $this->assertSame($before, \Illuminate\Support\Facades\DB::table('media_management')->count());
    }

    /**
     * When two records already share a vendor and GPS position there is no way to
     * know which one an un-coded row means, so it must ask rather than guess.
     */
    public function test_an_ambiguous_position_asks_for_the_hoarding_code(): void
    {
        $labels = \App\Support\MediaImportSchema::labels(
            \App\Support\MediaImportSchema::columnsForSlug('wall-wrap')
        );

        $row = fn (array $overrides) => array_map(fn ($label) => array_merge([
            'Category' => 'Wall Wrap',
            'State' => 'Maharashtra', 'District' => 'Nashik', 'City' => 'Nashik',
            'Area' => 'Govind Nagar', 'Vendor Code' => 'MAH_NAS_BIMPL',
            'Latitude' => '19.9966666', 'Longitude' => '73.7966666',
        ], $overrides)[$label] ?? '', $labels);

        $before = \Illuminate\Support\Facades\DB::table('media_management')->count();

        try {
            // Two different panels at one site, added in "Add new records only".
            $this->admin()->post('/media/import/publish', [
                'token' => $this->upload([
                    $labels,
                    $row(['Width (ft)' => '400', 'Height (ft)' => '150', 'Price (Monthly)' => '85000']),
                    $row(['Width (ft)' => '10', 'Height (ft)' => '5', 'Price (Monthly)' => '1000']),
                ])->viewData('batch')['token'],
            ]);

            $this->assertSame(
                $before + 2,
                \Illuminate\Support\Facades\DB::table('media_management')->count(),
                'two faces at one site must both be added'
            );

            // Now an un-coded row at that position cannot be resolved.
            $response = $this->upload([
                $labels,
                $row(['Width (ft)' => '400', 'Height (ft)' => '150', 'Price (Monthly)' => '77777']),
            ], ['mode' => 'upsert']);

            $response->assertOk();
            $batch = $response->viewData('batch');

            $this->assertSame(0, $batch['summary']['ready']);
            $this->assertSame(1, $batch['summary']['failed']);
            $this->assertStringContainsString(
                'share this vendor and GPS position',
                $batch['errors'][0]['issues']
            );
            $this->assertStringContainsString(
                'Put the Hoarding Code',
                $batch['errors'][0]['issues']
            );

            $this->admin()->post('/media/import/discard', ['token' => $batch['token']]);
        } finally {
            \Illuminate\Support\Facades\DB::table('media_management')
                ->where('latitude', '19.9966666')->delete();
        }

        $this->assertSame($before, \Illuminate\Support\Facades\DB::table('media_management')->count());
    }

    /**
     * The other side of the same rule: a second face at one site differs in at
     * least one supplied value, and must still import.
     */
    public function test_a_different_media_at_the_same_position_still_imports(): void
    {
        $labels = \App\Support\MediaImportSchema::labels(
            \App\Support\MediaImportSchema::columnsForSlug('wall-wrap')
        );

        $row = fn (array $overrides) => array_map(fn ($label) => array_merge([
            'Category' => 'Wall Wrap',
            'State' => 'Maharashtra', 'District' => 'Nashik', 'City' => 'Nashik',
            'Area' => 'Govind Nagar', 'Vendor Code' => 'MAH_NAS_BIMPL',
            'Latitude' => '19.9855555', 'Longitude' => '73.7855555',
            'Width (ft)' => '400', 'Height (ft)' => '150', 'Price (Monthly)' => '85000',
        ], $overrides)[$label] ?? '', $labels);

        $before = \Illuminate\Support\Facades\DB::table('media_management')->count();

        try {
            $first = $this->upload([$labels, $row([])]);
            $this->admin()->post('/media/import/publish', [
                'token' => $first->viewData('batch')['token'],
            ]);

            // Same vendor, same position — but a different panel: other size and price.
            $second = $this->upload([
                $labels,
                $row(['Width (ft)' => '120', 'Height (ft)' => '40', 'Price (Monthly)' => '15000']),
            ]);
            $second->assertOk();
            $batch = $second->viewData('batch');

            $this->assertSame([], $batch['errors'], 'a genuinely different media must not be refused');
            $this->assertSame(1, $batch['summary']['insert']);
            // Still worth a look, just not blocked.
            $this->assertSame(1, $batch['summary']['flagged']);

            $this->admin()->post('/media/import/discard', ['token' => $batch['token']]);
        } finally {
            \Illuminate\Support\Facades\DB::table('media_management')
                ->where('latitude', '19.9855555')->delete();
        }

        $this->assertSame($before, \Illuminate\Support\Facades\DB::table('media_management')->count());
    }

    /* =====================================================================
     |  REPLACING A RECORD'S PICTURES FROM AN UPDATE
     ===================================================================== */

    /** A 1x1 PNG, small enough to keep these tests off the network. */
    private function pngBytes(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg=='
        );
    }

    /**
     * A record whose category template is short, plus the sheet row that
     * describes it. Returns [target, labels, values].
     */
    private function wallRecordSheet(): array
    {
        $target = \Illuminate\Support\Facades\DB::table('media_management as m')
            ->join('category as c', 'c.id', '=', 'm.category_id')
            ->where('m.is_deleted', 0)->whereNotNull('m.hoarding_code')
            ->where('c.category_name', 'like', '%Wall%')
            ->select('m.*', 'c.category_name')
            ->first();

        if (!$target) {
            $this->markTestSkipped('No wall painting / wall wrap record available.');
        }

        // The all-categories template on purpose: a wall category's own template
        // has no Hoarding Code column (only hoardings carry a code), and this
        // sheet names the record it is editing by its code.
        $labels = \App\Support\MediaImportSchema::labels();

        $lookup = fn ($table, $id, $column) => \Illuminate\Support\Facades\DB::table($table)
            ->where('id', $id)->value($column);

        return [$target, $labels, [
            'Hoarding Code' => $target->hoarding_code,
            'Category' => $target->category_name,
            'State' => $lookup('states', $target->state_id, 'state_name'),
            'District' => $lookup('districts', $target->district_id, 'district_name'),
            'City' => $lookup('cities', $target->city_id, 'city_name'),
            'Area' => $lookup('areas', $target->area_id, 'area_name'),
            'Vendor Code' => $lookup('vendors', $target->vendor_id, 'vendor_code'),
            'Width (ft)' => $target->width,
            'Height (ft)' => $target->height,
            'Latitude' => $target->latitude,
            'Longitude' => $target->longitude,
            'Price (Monthly)' => $target->price,
        ]];
    }

    /**
     * Changing an image name in the sheet replaces that picture: the one the
     * sheet no longer lists is detached and its file deleted, the one it still
     * lists is left alone, and the new name is downloaded and stored.
     */
    public function test_changing_an_image_name_replaces_the_old_picture(): void
    {
        [$target, $labels, $values] = $this->wallRecordSheet();

        $folder = config('fileConstants.IMAGE_ADD');
        $viewBase = rtrim((string) config('fileConstants.IMAGE_VIEW'), '/') . '/';
        $disk = \Illuminate\Support\Facades\Storage::disk('public');

        $dropped = 'zzdrop-image.png';
        $kept = 'zzkept-image.png';
        $fresh = 'zzfresh-image.png';

        foreach ([$dropped, $kept] as $name) {
            $disk->put($folder . '/' . $name, $this->pngBytes());
        }

        $existingRows = \Illuminate\Support\Facades\DB::table('media_images')
            ->where('media_id', $target->id)->pluck('id')->all();

        \Illuminate\Support\Facades\DB::table('media_images')->insert([
            ['media_id' => $target->id, 'images' => $dropped, 'is_active' => 1, 'is_deleted' => 0],
            ['media_id' => $target->id, 'images' => $kept, 'is_active' => 1, 'is_deleted' => 0],
        ]);

        // Keep one picture by its exported link, swap the other for a new name
        // that travels in the images ZIP.
        $values['Image URLs'] = $viewBase . $kept . ', ' . $fresh;

        try {
            $response = $this->uploadWithZip(
                [$labels, array_map(fn ($label) => $values[$label] ?? '', $labels)],
                [$fresh => $this->pngBytes()]
            );

            $response->assertOk();
            $batch = $response->viewData('batch');
            $this->assertSame([], $batch['errors']);
            $this->assertSame(1, $batch['summary']['update']);

            $record = $batch['rows'][0];
            $this->assertTrue($record['replace_gallery']);
            // The kept picture is not downloaded again, only the new one is.
            $this->assertSame([$fresh], $record['image_urls']);

            $this->admin()->post('/media/import/publish', ['token' => $batch['token']]);

            $attached = \Illuminate\Support\Facades\DB::table('media_images')
                ->where('media_id', $target->id)->where('is_deleted', 0)
                ->pluck('images')->all();

            $this->assertNotContains($dropped, $attached, 'the dropped picture must be detached');
            $this->assertContains($kept, $attached, 'the kept picture must remain attached');
            $this->assertCount(2, $attached, 'the kept picture plus the new one');

            $this->assertFalse(
                $disk->exists($folder . '/' . $dropped),
                'the dropped picture file must be deleted from disk'
            );
            $this->assertTrue(
                $disk->exists($folder . '/' . $kept),
                'the kept picture file must survive'
            );
        } finally {
            $leftovers = \Illuminate\Support\Facades\DB::table('media_images')
                ->where('media_id', $target->id)
                ->whereNotIn('id', $existingRows ?: [0])
                ->pluck('images')->all();

            foreach (array_merge($leftovers, [$dropped, $kept, $fresh]) as $name) {
                $disk->delete($folder . '/' . $name);
            }

            \Illuminate\Support\Facades\DB::table('media_images')
                ->where('media_id', $target->id)
                ->whereNotIn('id', $existingRows ?: [0])
                ->delete();
        }
    }

    /**
     * The safety rule: an empty Image URLs cell means "not specified", not
     * "delete my pictures". A price-only update must never clear a gallery.
     */
    public function test_blank_image_cell_on_update_keeps_the_pictures(): void
    {
        [$target, $labels, $values] = $this->wallRecordSheet();

        $folder = config('fileConstants.IMAGE_ADD');
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $keep = 'zzuntouched-image.png';
        $disk->put($folder . '/' . $keep, $this->pngBytes());

        $existingRows = \Illuminate\Support\Facades\DB::table('media_images')
            ->where('media_id', $target->id)->pluck('id')->all();

        \Illuminate\Support\Facades\DB::table('media_images')->insert([
            ['media_id' => $target->id, 'images' => $keep, 'is_active' => 1, 'is_deleted' => 0],
        ]);

        // An export writes a dash when a field is empty — it must not wipe here.
        $values['Image URLs'] = '-';

        try {
            $response = $this->upload(
                [$labels, array_map(fn ($label) => $values[$label] ?? '', $labels)],
                ['mode' => 'upsert']
            );

            $response->assertOk();
            $batch = $response->viewData('batch');
            $this->assertSame(1, $batch['summary']['update']);
            $this->assertFalse(
                $batch['rows'][0]['replace_gallery'],
                'a blank image cell must not rewrite the gallery'
            );

            $this->admin()->post('/media/import/publish', ['token' => $batch['token']]);

            $attached = \Illuminate\Support\Facades\DB::table('media_images')
                ->where('media_id', $target->id)->where('is_deleted', 0)
                ->pluck('images')->all();

            $this->assertContains($keep, $attached, 'pictures must survive a blank image cell');
            $this->assertTrue($disk->exists($folder . '/' . $keep), 'the file must survive');
        } finally {
            $disk->delete($folder . '/' . $keep);
            \Illuminate\Support\Facades\DB::table('media_images')
                ->where('media_id', $target->id)
                ->whereNotIn('id', $existingRows ?: [0])
                ->delete();
        }
    }

    /**
     * Post a sheet together with an images ZIP built from [name => bytes].
     */
    private function uploadWithZip(array $rows, array $files)
    {
        $csvPath = tempnam(sys_get_temp_dir(), 'zzsheet') . '.csv';
        $handle = fopen($csvPath, 'w');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        $zipPath = tempnam(sys_get_temp_dir(), 'zzpics') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($files as $name => $bytes) {
            $zip->addFromString($name, $bytes);
        }
        $zip->close();

        $response = $this->admin()
            ->from('/media/import-export')
            ->post('/media/import/preview', [
                'mode' => 'upsert',
                'file' => new \Illuminate\Http\UploadedFile($csvPath, 'sheet.csv', 'text/csv', null, true),
                'images_zip' => new \Illuminate\Http\UploadedFile($zipPath, 'pics.zip', 'application/zip', null, true),
            ]);

        @unlink($csvPath);
        @unlink($zipPath);

        return $response;
    }

    /**
     * The error log workbook itself: serial numbers from 1 and the colliding
     * hoarding code in its own column.
     */
    public function test_error_log_export_has_serial_numbers_and_existing_code(): void
    {
        $export = new \App\Exports\MediaImportErrorExport([
            ['row' => 5, 'hoarding_code' => '', 'existing_code' => 'HD000123',
                'media_title' => 'Site A', 'issues' => 'This media is already in the inventory as HD000123'],
            ['row' => 9, 'hoarding_code' => 'HD000900', 'existing_code' => '',
                'media_title' => '', 'issues' => 'State "Neverland" does not exist'],
            // A skipped-at-publish entry, which carries no existing_code key.
            ['row' => 11, 'hoarding_code' => '', 'media_title' => '', 'issues' => 'Something else'],
        ]);

        $this->assertSame([
            'Sr No.', 'Sheet Row No.', 'Hoarding Code',
            'Already In Inventory As', 'Media Title', 'Problem(s) Found',
        ], $export->headings());

        $rows = $export->array();

        $this->assertSame([1, 5, '-', 'HD000123', 'Site A',
            'This media is already in the inventory as HD000123'], $rows[0]);
        $this->assertSame([2, 9, 'HD000900', '-', '-', 'State "Neverland" does not exist'], $rows[1]);
        // Missing key must not raise, and serials keep counting.
        $this->assertSame([3, 11, '-', '-', '-', 'Something else'], $rows[2]);
    }
}

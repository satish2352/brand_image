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
    }

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
        $response->assertSee('Download Sample Template', false);
        $response->assertSee('Export Matching Records', false);
        $response->assertSee('Mandatory columns', false);
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
            'data' => [['id', 'hoarding_code', 'media_title', 'category_name', 'city_name', 'vendor_name', 'price', 'status']],
        ]);
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
        $response->assertSee('Confirm &amp; Publish 1 Record(s)', false);

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

    public function test_media_list_still_renders_with_new_button(): void
    {
        $response = $this->admin()->get('/media/list');

        $response->assertOk();
        $response->assertSee('Import / Export', false);
    }
}

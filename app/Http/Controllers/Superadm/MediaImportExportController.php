<?php

namespace App\Http\Controllers\Superadm;

use App\Exports\MediaExport;
use App\Exports\MediaImportErrorExport;
use App\Exports\MediaTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\MediaImportExportService;
use App\Support\MediaImportSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

/**
 * Bulk Data Upload module for the outdoor media inventory.
 *
 * Import : sample template -> upload -> validate -> preview -> publish
 * Export : filtered / selected / complete inventory as Excel or CSV
 */
class MediaImportExportController extends Controller
{
    protected $service;

    /** Filters accepted by both the export and the record picker. */
    private const FILTER_KEYS = [
        'state_id',
        'district_id',
        'city_id',
        'area_id',
        'category_id',
        'vendor_id',
        'illumination_id',
        'areatype_id',
        'highway_id',
        'media_type',
        'status',
        'hoarding_code',
        'from_date',
        'to_date',
        'min_price',
        'max_price',
    ];

    public function __construct(MediaImportExportService $service)
    {
        $this->service = $service;
    }

    /**
     * The Import / Export tab.
     */
    public function index(Request $request)
    {
        try {
            $options = $this->service->filterOptions();

            return view('superadm.mediamanagement.import-export', [
                'options' => $options,
                'requiredColumns' => MediaImportSchema::requiredColumns(),
                'optionalColumns' => MediaImportSchema::optionalColumns(),
                'activeTab' => $request->query('tab') === 'export' ? 'export' : 'import',
                'filters' => $this->filters($request),
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return back()->with('error', 'Something went wrong');
        }
    }

    /* =====================================================================
     |  IMPORT
     ===================================================================== */

    /**
     * Sample template: header row, two example rows built from this
     * installation's own masters, instructions and the exact master values the
     * importer accepts.
     */
    public function template(Request $request, $category = null)
    {
        try {
            $categoryId = ($category !== null && $category !== '') ? (int) $category : null;

            $fileName = 'media-bulk-upload-template';
            if ($categoryId && ($info = $this->service->category($categoryId))) {
                $fileName .= '-' . \Illuminate\Support\Str::slug($info['name']);
            }

            return Excel::download(
                new MediaTemplateExport(
                    $this->service->masterReference(),
                    $this->service->templateSampleRows($categoryId),
                    $this->service->templateColumns($categoryId)
                ),
                $fileName . '.xlsx'
            );
        } catch (\Exception $e) {
            Log::error($e);

            return back()->with('error', 'Could not generate the sample template');
        }
    }

    /**
     * Validate the uploaded sheet and show the preview screen. Nothing is
     * written to the inventory until the admin confirms.
     */
    public function preview(Request $request)
    {
        $request->validate(
            [
                'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
                'mode' => 'required|in:insert,upsert',
                'category_id' => 'nullable|integer',
            ],
            [
                'file.required' => 'Please choose an Excel (.xlsx) or CSV file to upload.',
                'file.mimes' => 'Only .xlsx, .xls and .csv files are supported.',
                'file.max' => 'The file must not be larger than 10MB.',
            ]
        );

        try {
            $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;

            $batch = $this->service->parseUpload($request->file('file'), $request->mode, $categoryId);

            return view('superadm.mediamanagement.import-preview', [
                'batch' => $batch,
                'previewRows' => array_slice($batch['rows'], 0, MediaImportExportService::PREVIEW_LIMIT),
                'previewLimit' => MediaImportExportService::PREVIEW_LIMIT,
            ]);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error($e);

            return back()->with('error', 'The file could not be read. Please check the format and try again.');
        }
    }

    /**
     * Write a previewed batch into media_management.
     */
    public function publish(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        try {
            $result = $this->service->publish($request->token);

            $message = "Import complete — {$result['inserted']} record(s) added";
            if ($result['updated'] > 0) {
                $message .= ", {$result['updated']} record(s) updated";
            }
            if (!empty($result['images'])) {
                $message .= ", {$result['images']} image(s) downloaded";
            }
            if (!empty($result['skipped'])) {
                $message .= ', ' . count($result['skipped']) . ' row(s) skipped';
            }

            return redirect()
                ->route('media.list')
                ->with('success', $message)
                ->with('import_skipped', $result['skipped'])
                ->with('import_image_warnings', $result['image_warnings']);
        } catch (RuntimeException $e) {
            return redirect()->route('media.import-export')->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error($e);

            return redirect()->route('media.import-export')
                ->with('error', 'The import could not be completed. No records were changed.');
        }
    }

    /**
     * Throw away a previewed batch.
     */
    public function discard(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        $this->service->discard($request->token);

        return redirect()->route('media.import-export')
            ->with('success', 'Import cancelled. No records were changed.');
    }

    /**
     * Error log for a previewed batch, so the team can fix and re-upload
     * only the failed rows.
     */
    public function errorLog(string $token)
    {
        $batch = $this->service->getBatch($token);

        if (!$batch) {
            return redirect()->route('media.import-export')
                ->with('error', 'This import session has expired. Please upload the file again.');
        }

        if (empty($batch['errors'])) {
            return back()->with('error', 'There are no errors to download for this file.');
        }

        return Excel::download(
            new MediaImportErrorExport($batch['errors']),
            'media-import-errors-' . date('Y-m-d-His') . '.xlsx'
        );
    }

    /* =====================================================================
     |  EXPORT
     ===================================================================== */

    /**
     * Export the inventory. Scope is either the current filters (or no filters
     * at all, i.e. the complete database) or an explicit list of record ids.
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'nullable|in:xlsx,csv',
            'ids' => 'nullable|string',
        ]);

        try {
            $format = $request->input('format', 'xlsx');
            $ids = $this->ids($request);
            $filters = $ids ? [] : $this->filters($request);

            $query = $this->service->exportQuery($filters, $ids);

            if ((clone $query)->count() === 0) {
                return back()->with('error', 'No media records match the selected filters.');
            }

            $fileName = 'media-inventory-' . date('Y-m-d-His') . '.' . $format;

            return Excel::download(
                new MediaExport($query),
                $fileName,
                $format === 'csv' ? ExcelFormat::CSV : ExcelFormat::XLSX
            );
        } catch (\Exception $e) {
            Log::error($e);

            return back()->with('error', 'The export could not be generated. Please try again.');
        }
    }

    /**
     * Record picker behind the "Load matching records" button on the Export
     * tab — lets the admin tick individual records before exporting.
     */
    public function records(Request $request)
    {
        try {
            $perPage = min((int) $request->input('per_page', 50), 200);

            $rows = $this->service->exportQuery($this->filters($request))
                ->paginate($perPage, ['*'], 'page', (int) $request->input('page', 1));

            return response()->json([
                'status' => true,
                'total' => $rows->total(),
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'data' => collect($rows->items())->map(fn ($row) => [
                    'id' => $row->id,
                    'hoarding_code' => $row->hoarding_code ?: '-',
                    'media_title' => $row->media_title ?: '-',
                    'category_name' => $row->category_name ?: '-',
                    'city_name' => $row->city_name ?: '-',
                    'area_name' => $row->area_name ?: '-',
                    'vendor_name' => $row->vendor_name ?: '-',
                    'price' => (float) $row->price,
                    'status' => $row->is_active ? 'Active' : 'Inactive',
                ])->values(),
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json([
                'status' => false,
                'message' => 'Could not load records',
            ], 500);
        }
    }

    /* =====================================================================
     |  HELPERS
     ===================================================================== */

    private function filters(Request $request): array
    {
        $filters = [];

        foreach (self::FILTER_KEYS as $key) {
            $value = $request->input($key);

            if ($value !== null && $value !== '') {
                $filters[$key] = $value;
            }
        }

        return $filters;
    }

    /**
     * @return array<int>
     */
    private function ids(Request $request): array
    {
        $raw = $request->input('ids');

        if (empty($raw)) {
            return [];
        }

        $ids = is_array($raw) ? $raw : explode(',', $raw);

        return array_values(array_filter(array_map('intval', $ids)));
    }
}

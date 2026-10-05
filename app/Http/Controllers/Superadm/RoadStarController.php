<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use App\Http\Services\RoadStar\RoadStarService;
use App\Http\Services\RoadStar\RoadStarSyncService;
use App\Jobs\SyncRoadStarSites;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Admin-only RoadStar endpoints (routes/web.php, inside the SuperAdmin group,
 * under media/roadstar). All JSON. POSTs go through CSRF like every other
 * admin form. Nothing here returns or logs the RoadStar credentials; they stay
 * in RoadStarService.
 *
 * {encodedId} is the media id base64-encoded, as on the media pages.
 */
class RoadStarController extends Controller
{
    /** Most hoardings one bulk request may queue. */
    private const BULK_LIMIT = 200;

    public function __construct(private RoadStarSyncService $roadStar, private RoadStarService $client) {}

    /** GET media/roadstar/test-connection — credentials + reachability, no side effects. */
    public function testConnection(Request $request): JsonResponse
    {
        if (!$this->client->isConfigured()) {
            return response()->json(['ok' => false, 'message' => 'RoadStar is not configured on the server.'], 503);
        }

        $period = $this->roadStar->period();
        $r = $this->client->using('admin', null, $this->adminId($request))->testConnection($period['start'], $period['end']);

        return response()->json([
            'ok'          => $r['ok'],
            'message'     => $r['ok'] ? 'Connected to RoadStar; credentials accepted.' : $r['error'],
            'error_type'  => $r['error_type'],
            'http_status' => $r['http_status'],
            'duration_ms' => $r['duration_ms'],
        ], $r['ok'] ? 200 : 502);
    }

    /** GET media/roadstar/{encodedId}/status — mapping, sync status, latest audience (MySQL only). */
    public function status(string $encodedId): JsonResponse
    {
        $id = $this->decode($encodedId);
        $data = $id ? $this->roadStar->forDisplay($id) : ['found' => false];

        if (!$data['found']) {
            return response()->json(['ok' => false, 'message' => 'Hoarding not found.'], 404);
        }

        return response()->json(['ok' => true] + $data);
    }

    /**
     * GET media/roadstar/{encodedId}/site-data — manual test:
     * media id → RoadStar site id → POST /sitedata → parsed response.
     * Calls RoadStar live; stores nothing.
     */
    public function siteData(Request $request, string $encodedId): JsonResponse
    {
        $id = $this->decode($encodedId);
        if (!$id) {
            return response()->json(['ok' => false, 'message' => 'Hoarding not found.'], 404);
        }

        $r = $this->roadStar->preview($id, $this->adminId($request));
        $code = match ($r['status']) {
            'not_found'  => 404,
            'not_mapped' => 422,
            default      => 200,
        };

        return response()->json($r, $code);
    }

    /** POST media/roadstar/{encodedId}/map — set (or clear) the RoadStar site id. */
    public function map(Request $request, string $encodedId): JsonResponse
    {
        $id = $this->decode($encodedId);
        if (!$id) {
            return response()->json(['ok' => false, 'message' => 'Hoarding not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'roadstar_site_id' => ['nullable', 'string', 'max:100', 'regex:' . RoadStarSyncService::SITE_ID_PATTERN],
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $r = $this->roadStar->map($id, $request->input('roadstar_site_id'));

        return response()->json($r, $r['ok'] ? 200 : 422);
    }

    /** POST media/roadstar/{encodedId}/register — POST /addsite with the hoarding's coordinates. */
    public function register(Request $request, string $encodedId): JsonResponse
    {
        $id = $this->decode($encodedId);
        if (!$id) {
            return response()->json(['ok' => false, 'message' => 'Hoarding not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'roadstar_site_id' => ['nullable', 'string', 'max:100', 'regex:' . RoadStarSyncService::SITE_ID_PATTERN],
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $r = $this->roadStar->register($id, $request->input('roadstar_site_id'), 'admin', $this->adminId($request));

        return response()->json($r, $r['ok'] ? 200 : 422);
    }

    /**
     * POST media/roadstar/{encodedId}/sync — "Sync RoadStar Data" and the
     * single-media test: RoadStarSyncService::syncOne(), now, in this request.
     * Optional body: roadstar_site_id (map to this id first), register=1
     * (call /addsite if RoadStar says the site is not available).
     * Returns the steps taken and the saved result (MySQL).
     */
    public function sync(Request $request, string $encodedId): JsonResponse
    {
        $id = $this->decode($encodedId);
        if (!$id) {
            return response()->json(['ok' => false, 'message' => 'Hoarding not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'roadstar_site_id' => ['nullable', 'string', 'max:100', 'regex:' . RoadStarSyncService::SITE_ID_PATTERN],
            'register'         => ['nullable', 'boolean'],
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $r = $this->roadStar->syncOne($id, $request->input('roadstar_site_id'), $request->boolean('register'), 'admin', $this->adminId($request));

        $code = match ($r['status']) {
            'not_found'                    => 404,
            'not_mapped', 'invalid_site_id' => 422,
            'busy'                         => 409,
            default                        => 200,
        };

        return response()->json($r, $code);
    }

    /**
     * POST media/roadstar/sync-bulk — queue a sync for up to 200 hoardings.
     * Body: media_ids[] (plain numeric media ids).
     */
    public function syncBulk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'media_ids'   => ['required', 'array', 'min:1', 'max:' . self::BULK_LIMIT],
            'media_ids.*' => ['required', 'integer', 'min:1'],
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }
        if (!$this->roadStar->isConfigured()) {
            return response()->json(['ok' => false, 'message' => 'RoadStar is not configured on the server.'], 503);
        }
        if (!$this->roadStar->bulkEnabled()) {
            return response()->json(['ok' => false, 'message' => 'Bulk RoadStar sync is turned off (ROADSTAR_BULK_SYNC_ENABLED). Sync hoardings one at a time.'], 409);
        }

        $requested = array_values(array_unique(array_map('intval', $request->input('media_ids'))));
        $mapped = DB::table('media_management')
            ->whereIn('id', $requested)
            ->where('is_deleted', 0)
            ->whereNotNull('roadstar_site_id')
            ->where('roadstar_site_id', '!=', '')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        $jobs = 0;
        foreach (array_chunk($mapped, $this->roadStar->batchSize()) as $chunk) {
            $this->roadStar->markQueued($chunk);
            SyncRoadStarSites::dispatch($chunk, 'admin');
            $jobs++;
        }

        return response()->json([
            'ok'         => true,
            'message'    => count($mapped) . ' hoarding(s) queued for RoadStar sync in ' . $jobs . ' job(s).',
            'queued'     => $mapped,
            'not_mapped' => array_values(array_diff($requested, $mapped)),
        ]);
    }

    private function decode(string $encodedId): ?int
    {
        $decoded = base64_decode($encodedId, true);

        return $decoded !== false && ctype_digit($decoded) && (int) $decoded > 0 ? (int) $decoded : null;
    }

    private function adminId(Request $request): ?int
    {
        $id = $request->session()->get('user_id');

        return is_numeric($id) ? (int) $id : null;
    }
}

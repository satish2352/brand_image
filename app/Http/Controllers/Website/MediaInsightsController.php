<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Services\Insights\HoardingTrafficService;
use App\Http\Services\Insights\MediaInsightsService;
use App\Http\Services\RoadStar\RoadStarSyncService;
use App\Models\ApiUsageLog;
use App\Models\MediaManagement;
use App\Support\AdminSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Media Location Insights on the media details page.
 *
 * show()     GET — the insights panel as HTML, loaded by the page with AJAX
 *            (so the page shows a loading state). On the FIRST view of a
 *            hoarding with no nearby places yet it fetches them once, within
 *            the credit budget; every other view reads MySQL only.
 * refresh()  POST — admin only (site admin mode or the admin panel, the same
 *            check shared links use), CSRF-protected, throttled on the route.
 *            May spend API credits, within the budget.
 *
 * Responses carry HTML, a status and a message — never an API key or raw
 * provider data.
 */
class MediaInsightsController extends Controller
{
    public function __construct(private MediaInsightsService $insights) {}

    public function show(string $encodedId): JsonResponse
    {
        $id = $this->decode($encodedId);
        if ($id === null) {
            return response()->json(['ok' => false, 'message' => 'Invalid media.'], 404);
        }

        // First view of a hoarding that has no nearby places yet (or has moved /
        // expired): fetch them once for THIS hoarding's coordinates. Every later
        // view is served from the database. Skipped when the key is missing,
        // the provider is paused or today's credit budget is used up; a
        // hoarding that keeps failing is left for a day (see isDue).
        if (config('services.insights.fetch_on_view', true)) {
            try {
                if ($this->insights->canAffordRequest() && $this->insights->isDue($id)) {
                    $this->insights->refresh($id, null, 'viewer');
                }
            } catch (Throwable $e) {
                Log::warning('Fetch-on-view failed', ['media_id' => $id, 'message' => $e->getMessage()]);
            }
        }

        try {
            $data = $this->insights->forDisplay($id);
        } catch (Throwable $e) {
            Log::warning('Media insights unavailable', ['media_id' => $id, 'message' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Location insights are not available right now.'], 503);
        }

        if (!$data) {
            return response()->json(['ok' => false, 'message' => 'Media not found.'], 404);
        }

        $isAdmin = $this->isAdmin();

        return response()->json([
            'ok'   => true,
            'html' => view('insights.panel', [
                'insights'      => $data,
                'insightsAdmin' => $isAdmin,
                'insightsQuota' => $isAdmin ? $this->insights->quota() : null,
                'mediaId'       => $id,
            ])->render(),
        ]);
    }

    public function refresh(string $encodedId): JsonResponse
    {
        if (!$this->isAdmin()) {
            return response()->json(['ok' => false, 'message' => 'Only admins can refresh insights.'], 403);
        }

        $id = $this->decode($encodedId);
        if ($id === null) {
            return response()->json(['ok' => false, 'message' => 'Invalid media.'], 404);
        }

        try {
            $result = $this->insights->refresh($id, AdminSession::siteId() ?? session('user_id'), 'admin');
        } catch (Throwable $e) {
            Log::error('Refresh insights failed', ['media_id' => $id, 'message' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Could not refresh insights. Please try again later.'], 500);
        }

        if ($result['status'] === 'not_found') {
            return response()->json(['ok' => false, 'message' => $result['message']], 404);
        }

        return response()->json([
            'ok'      => in_array($result['status'], [ApiUsageLog::SUCCESS, ApiUsageLog::CACHE_HIT], true),
            'status'  => $result['status'],
            'message' => $result['message'],
            'quota'   => array_intersect_key($this->insights->quota(), array_flip(['provider', 'period', 'used', 'limit', 'remaining'])),
        ]);
    }

    /**
     * POST "Sync RoadStar Data" on the Media Details page — admin only, same
     * check as refresh(), CSRF-protected and throttled on the route. Syncs a
     * hoarding already mapped to a RoadStar site (mapping and /addsite are
     * done from the admin panel). Returns status + message only.
     */
    public function roadstarSync(string $encodedId, RoadStarSyncService $roadStar): JsonResponse
    {
        if (!$this->isAdmin()) {
            return response()->json(['ok' => false, 'message' => 'Only admins can sync RoadStar data.'], 403);
        }

        $id = $this->decode($encodedId);
        if ($id === null) {
            return response()->json(['ok' => false, 'message' => 'Invalid media.'], 404);
        }

        try {
            $r = $roadStar->syncOne($id, null, false, 'admin', AdminSession::siteId() ?? session('user_id'));
        } catch (Throwable $e) {
            Log::error('RoadStar sync failed', ['media_id' => $id, 'message' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Could not sync RoadStar data. Please try again later.'], 500);
        }

        $code = match ($r['status']) {
            'not_found'  => 404,
            'not_mapped' => 422,
            'busy'       => 409,
            default      => 200,
        };
        $message = $r['status'] === 'not_mapped'
            ? 'This hoarding is not mapped to a RoadStar site yet. Map it from the admin panel (Media → View Details).'
            : $r['message'];

        return response()->json(['ok' => $r['ok'], 'status' => $r['status'], 'message' => $message], $code);
    }

    /**
     * GET — the hoarding's stored TomTom Traffic Flow, read from MySQL only.
     * Never calls TomTom: the data is fetched and refreshed by the scheduled
     * `refresh:hoarding-traffic` command. traffic_data_available is false
     * until the first fetch has succeeded.
     */
    public function traffic(string $encodedId, HoardingTrafficService $traffic): JsonResponse
    {
        $id = $this->decode($encodedId);
        if ($id === null || !MediaManagement::where('id', $id)->where('is_deleted', 0)->exists()) {
            return response()->json(['ok' => false, 'message' => 'Media not found.'], 404);
        }

        try {
            return response()->json(['ok' => true, 'media_id' => $id] + $traffic->forDisplay($id));
        } catch (Throwable $e) {
            Log::warning('Hoarding traffic unavailable', ['media_id' => $id, 'message' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Traffic data is not available right now.'], 503);
        }
    }

    /**
     * GET — the Nearby Landmark options for one town on the search form:
     * places the provider already returned for that town's hoardings, read
     * from the database (no API request).
     */
    public function cityPlaces(\Illuminate\Http\Request $request): JsonResponse
    {
        $cityId = (int) $request->query('city_id');
        if ($cityId <= 0) {
            return response()->json([]);
        }

        try {
            return response()->json($this->insights->nearbyPlacesForCity($cityId));
        } catch (Throwable $e) {
            Log::warning('Nearby places for city failed', ['city_id' => $cityId, 'message' => $e->getMessage()]);

            return response()->json([]);
        }
    }

    private function isAdmin(): bool
    {
        return AdminSession::onSite() || AdminSession::onPanel();
    }

    private function decode(string $encodedId): ?int
    {
        $id = base64_decode($encodedId, true);

        return ($id !== false && ctype_digit($id)) ? (int) $id : null;
    }
}

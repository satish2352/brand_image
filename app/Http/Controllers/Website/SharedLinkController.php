<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SharedLinkVisitor;
use App\Http\Services\Website\HomeService;
use App\Models\SharedLink;
use App\Models\SharedLinkItem;
use Illuminate\Http\Request;
use App\Support\AdminSession;
use App\Support\LocationCache;
use App\Support\MasterCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Shareable hoarding shortlists.
 *
 * The team filters /search as normal, ticks the hoardings that suit a client's
 * brief and generates a link. The client opens it and sees those hoardings
 * only, in the usual card + map listing, and can add them to the cart and
 * carry on into the existing campaign / booking flow.
 */
class SharedLinkController extends Controller
{
    public function __construct(private HomeService $homeService) {}

    /**
     * Generate a link for the ticked hoardings. Admin only: the tick boxes and
     * the Share bar are rendered for a logged-in team member, and this checks
     * again rather than trusting that the UI was the only way in.
     */
    public function store(Request $request)
    {
        if (!AdminSession::onSite()) {
            return response()->json([
                'ok'      => false,
                'message' => 'Please log in to the admin panel to share hoardings.',
            ], 403);
        }

        $data = $request->validate([
            'media_ids'   => 'required|array|min:1',
            'media_ids.*' => 'integer',
            'title'       => 'nullable|string|max:150',
        ], [
            'media_ids.required' => 'Select at least one hoarding to share.',
        ]);

        // Only ids that are really live media, so a tampered request cannot
        // mint a link full of deleted or inactive rows.
        $mediaIds = DB::table('media_management')
            ->whereIn('id', $data['media_ids'])
            ->where('is_deleted', 0)
            ->where('is_active', 1)
            ->pluck('id')
            ->all();

        if (empty($mediaIds)) {
            return response()->json([
                'ok'      => false,
                'message' => 'None of the selected hoardings are available to share.',
            ], 422);
        }

        $link = DB::transaction(function () use ($data, $mediaIds) {
            $link = SharedLink::create([
                'token'      => SharedLink::newToken(),
                'title'      => $data['title'] ?? null,
                'created_by' => AdminSession::siteId(),
            ]);

            // Kept in the order the team ticked them; the listing page reads
            // them back the same way.
            foreach ($mediaIds as $mediaId) {
                SharedLinkItem::create([
                    'shared_link_id' => $link->id,
                    'media_id'       => $mediaId,
                ]);
            }

            return $link;
        });

        return response()->json([
            'ok'    => true,
            'url'   => route('shared.link.show', $link->token),
            'count' => count($mediaIds),
        ]);
    }

    /**
     * The client's view: the shortlist, and a filter card that can only ever
     * narrow it.
     *
     * GET renders the whole shortlist; the filter card posts back here.
     *
     * The four location levels — Category, State, District, Town — are fixed
     * by whatever the team shortlisted and render disabled. They are not read
     * from the request at all: they are recomputed from the link's own rows on
     * every hit, so a hand-crafted POST cannot swap them for someone else's
     * district. Every other filter is the client's to use, and each one is
     * applied on top of media_ids, which bounds the result set to this link.
     */
    public function show(Request $request, string $token)
    {
        $link = SharedLink::where('token', $token)->first();

        if (!$link) {
            // A 404 view rather than an exception: the person holding the link
            // is a client, not a developer.
            return response()->view('website.shared-missing', [], 404);
        }

        // Remember which shortlist this visitor is on. SharedLinkVisitor reads
        // it to keep them off the Map and the search results; the home page and
        // the header read it to drop the controls that lead there.
        session([SharedLinkVisitor::SESSION_KEY => $link->token]);

        $mediaIds = $link->mediaIds();

        // Everything the filter card is allowed to offer, read off the
        // shortlisted rows themselves. Offering the full masters instead would
        // fill the dropdowns with districts and highways that cannot possibly
        // match — every one of them a dead end.
        $scope = $this->scopeOf($mediaIds);

        // "Clear Filters" posts clear=1; drop the client's narrowing and show
        // the whole shortlist again. The locked levels are added back below,
        // since they describe the link rather than being a filter on it.
        $filters = $request->filled('clear')
            ? []
            : array_filter(
                $request->only([
                    'area_id',
                    'areatype_id',
                    'highway_id',
                    'landmark_ids',
                    'from_date',
                    'to_date',
                    'available_days',
                    'min_area',
                    'max_area',
                    'min_price',
                    'max_price',
                    'radius_id',
                ]),
                fn($v) => $v !== null && $v !== '' && $v !== []
            );

        // Server-side, and last: the request never gets a say in these.
        foreach ($scope['locked'] as $field => $options) {
            // Only a single value is a filter. A shortlist spanning two
            // districts shows "All … in this shortlist" and filters on
            // neither — media_ids already holds the boundary.
            if (count($options) === 1) {
                $filters[$field] = (string) array_key_first($options);
            }
        }

        $mediaList = $this->homeService->getMediaByIds($mediaIds, $filters);

        // The map plots exactly what the list shows.
        $mapMedia = $mediaList->filter(
            fn($m) => !empty($m->latitude) && !empty($m->longitude)
        )->values();

        return view('website.shared', [
            'link'       => $link,
            'mediaList'  => $mediaList,
            'mapMedia'   => $mapMedia,
            'filters'    => $filters,
            'biScope'    => $scope,
            'areaTypes'  => collect($scope['options']['areatype_id'])
                ->map(fn($name, $id) => (object) ['id' => $id, 'areatype_name' => $name])
                ->values(),
            'areaRange'  => $scope['area_range'],
            // Live rows only, not count($mediaIds): media taken off the
            // platform since the link was made still have a row on it, and
            // "3 of 5 Results" would then never reach 5.
            'totalCount' => $scope['count'],
        ]);
    }

    /**
     * Every area in the given town(s), as id => name.
     *
     * The whole town, deliberately, not only the areas the shortlisted
     * hoardings sit in: the client is narrowing a list someone else drew up,
     * and a menu offering only the answers already in front of them is not a
     * filter. Shares LocationCache's per-city key with the AJAX endpoint
     * /search uses, so both read the same cached rows.
     */
    private function areasIn(array $cityIds): array
    {
        $out = [];

        foreach (array_filter($cityIds) as $cityId) {
            $rows = Cache::remember(
                LocationCache::areasKey($cityId),
                LocationCache::TTL,
                fn() => DB::table('areas')
                    ->where(['city_id' => $cityId, 'is_active' => 1, 'is_deleted' => 0])
                    ->orderBy('area_name')
                    ->get(['id', 'area_name'])
            );

            foreach ($rows as $row) {
                $out[(int) $row->id] = $row->area_name;
            }
        }

        asort($out, SORT_NATURAL | SORT_FLAG_CASE);

        return $out;
    }

    /**
     * The area type master, as id => name. Same cache entry and row shape the
     * home and search pages read, so this warms no second copy.
     */
    private function areaTypeOptions(): array
    {
        $rows = Cache::remember(
            MasterCache::AREA_TYPES,
            MasterCache::TTL,
            fn() => DB::table('areatype')->where('is_active', 1)->where('is_deleted', 0)->get()
        );

        return collect($rows)->pluck('areatype_name', 'id')->all();
    }

    /**
     * What the shortlist is made of: the distinct values behind each filter,
     * plus the size range its media actually span.
     *
     * `locked`  — the four levels the client cannot change. Each is an
     *             id => name map; one entry means the whole shortlist sits at
     *             that value and it becomes a real filter, more than one means
     *             the shortlist straddles them and the field only labels that.
     * `options` — the levels the client CAN change. These carry the same
     *             full lists /search offers, so the client sees every area,
     *             area type, highway and landmark rather than only the
     *             handful the shortlisted rows happen to use. A choice that
     *             matches nothing returns an empty list rather than being
     *             missing from the menu; what it can never do is reach
     *             outside the link, because every query stays bounded by
     *             media_ids.
     */
    private function scopeOf(array $mediaIds): array
    {
        $empty = [
            'locked'     => [],
            'options'    => [
                'area_id'     => [],
                'areatype_id' => [],
            ],
            'area_range' => (object) ['min_area' => 0, 'max_area' => 0],
            'count'      => 0,
        ];

        if (empty($mediaIds)) {
            return $empty;
        }

        $rows = DB::table('media_management as m')
            ->leftJoin('category as ct', 'ct.id', '=', 'm.category_id')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('cities as c', 'c.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('areatype as at', 'at.id', '=', 'm.areatype_id')
            ->leftJoin('highway as hw', 'hw.id', '=', 'm.highway_id')
            ->whereIn('m.id', $mediaIds)
            ->where('m.is_deleted', 0)
            ->where('m.is_active', 1)
            ->select([
                'm.category_id',
                'ct.category_name',
                'm.state_id',
                's.state_name',
                'm.district_id',
                'd.district_name',
                'm.city_id',
                'c.city_name',
                'm.area_id',
                'a.area_name',
                'm.areatype_id',
                'at.areatype_name',
                'm.highway_id',
                'hw.highway_name',
                DB::raw('CAST(m.area_auto AS DECIMAL(15,2)) as area_sqft'),
            ])
            ->get();

        if ($rows->isEmpty()) {
            return $empty;
        }

        // id => label, skipping rows where the level is not set. Sorted by
        // label so a multi-value field reads alphabetically rather than by
        // whichever media happened to be ticked first.
        $pairs = function (string $idKey, string $nameKey) use ($rows): array {
            $out = [];

            foreach ($rows as $row) {
                if (!empty($row->$idKey)) {
                    $out[(int) $row->$idKey] = $row->$nameKey ?: ('#' . $row->$idKey);
                }
            }

            asort($out, SORT_NATURAL | SORT_FLAG_CASE);

            return $out;
        };

        $sizes = $rows->pluck('area_sqft')->filter(fn($v) => $v !== null)->map(fn($v) => (float) $v);

        $locked = [
            'category_id' => $pairs('category_id', 'category_name'),
            'state_id'    => $pairs('state_id', 'state_name'),
            'district_id' => $pairs('district_id', 'district_name'),
            'city_id'     => $pairs('city_id', 'city_name'),
        ];

        return [
            'locked' => $locked,
            // Highway and Landmarks are not listed here at all: the filter
            // card already receives both masters from the view composer, and
            // with nothing to override it uses them, exactly as /search does.
            'options' => [
                // Every area in the town this shortlist sits in — the same
                // list /search fills in once that town is picked. Same cache
                // key and columns as the AJAX endpoint behind it, so the two
                // share one entry rather than each warming their own.
                'area_id'     => $this->areasIn(array_keys($locked['city_id'])),
                'areatype_id' => $this->areaTypeOptions(),
            ],
            // The slider spans what this shortlist holds, not the whole
            // inventory — otherwise both handles sit on a range where every
            // position returns the same rows. floor/ceil so the widest and
            // narrowest media stay inside their own range after rounding.
            'area_range' => (object) [
                'min_area' => $sizes->isEmpty() ? 0 : (int) floor($sizes->min()),
                'max_area' => $sizes->isEmpty() ? 0 : (int) ceil($sizes->max()),
            ],
            'count' => $rows->count(),
        ];
    }
}

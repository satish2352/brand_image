<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SharedLinkVisitor;
use App\Http\Services\Website\HomeService;
use App\Models\SharedLink;
use App\Models\SharedLinkItem;
use Illuminate\Http\Request;
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
        if (!$request->session()->has('user_id')) {
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

        $link = DB::transaction(function () use ($data, $mediaIds, $request) {
            $link = SharedLink::create([
                'token'      => SharedLink::newToken(),
                'title'      => $data['title'] ?? null,
                'created_by' => $request->session()->get('user_id'),
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
     * The client's view: the shortlist and nothing else.
     *
     * There is no filter form and no route from here into the full inventory —
     * the page can only ever render the ids stored against this token.
     */
    public function show(string $token)
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


        $mediaList = $this->homeService->getMediaByIds($link->mediaIds());

        // The map plots exactly what the list shows.
        $mapMedia = $mediaList->filter(
            fn($m) => !empty($m->latitude) && !empty($m->longitude)
        )->values();

        return view('website.shared', compact('link', 'mediaList', 'mapMedia'));
    }
}

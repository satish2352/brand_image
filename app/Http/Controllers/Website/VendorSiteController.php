<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Mail\VendorSiteOtpMail;
use App\Models\MediaImage;
use App\Models\Vendor;
use App\Models\VendorSlider;
use App\Support\MediaAvailability;
use App\Support\MediaImageUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Vendor Website Showcase (PRD 2.9): brand-adda.co.in/{vendor-slug}.
 *
 * Public, but opened with an OTP sent to the vendor's registered email (as
 * the PRD specifies). A verified browser keeps access for ACCESS_HOURS. The
 * signed-in vendor itself and Brand Adda admins skip the OTP, for previews.
 *
 * Shows only that vendor's approved, active media and its map — no login,
 * cart, campaign, booking, About Us, Contact, footer or timer.
 */
class VendorSiteController extends Controller
{
    private const OTP_MINUTES  = 10;
    private const ACCESS_HOURS = 12;

    private function vendorBySlug(string $slug): Vendor
    {
        return Vendor::where('slug', $slug)->where('is_deleted', 0)->where('is_active', 1)->firstOrFail();
    }

    private function sessionKey(Vendor $vendor): string
    {
        return 'vendor_site_access.' . $vendor->id;
    }

    private function hasAccess(Vendor $vendor): bool
    {
        if (auth('vendor')->id() === $vendor->id || session('user_id') || session('site_admin_id')) {
            return true;
        }

        return (int) session($this->sessionKey($vendor), 0) > now()->timestamp;
    }

    /** sh*****@domain.com — enough to recognise, not enough to harvest. */
    private function maskEmail(?string $email): string
    {
        if (!$email || !str_contains($email, '@')) {
            return 'the registered email';
        }
        [$user, $domain] = explode('@', $email, 2);

        return mb_substr($user, 0, 2) . str_repeat('*', max(3, mb_strlen($user) - 2)) . '@' . $domain;
    }

    private function mediaQuery(Vendor $vendor)
    {
        return DB::table('media_management as m')
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('cities as cty', 'cty.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('illuminations as i', 'i.id', '=', 'm.illumination_id')
            ->where('m.vendor_id', $vendor->id)
            ->where('m.is_deleted', 0)
            ->where('m.is_active', 1)
            ->where('m.approval_status', 'approved');
    }

    /** Filters handed to HomeRepository (the main /search filter logic). */
    private const SEARCH_FILTERS = [
        'category_id', 'state_id', 'district_id', 'city_id', 'area_id', 'areatype_id', 'highway_id', 'landmark_ids',
        'nearby_place', 'nearby_distance', 'from_date', 'to_date', 'available_days', 'hoarding_code',
        'min_area', 'max_area', 'min_radius', 'max_radius', 'min_price', 'max_price',
    ];

    /** The vendor website's filters from the query string, cleaned; empty ones dropped. */
    private function siteFilters(Request $request): array
    {
        $f = [];
        foreach (['category_id', 'state_id', 'district_id', 'city_id', 'area_id', 'areatype_id', 'highway_id'] as $k) {
            if ((int) $request->query($k) > 0) {
                $f[$k] = (int) $request->query($k);
            }
        }
        $landmarks = array_values(array_filter(array_map('intval', (array) $request->query('landmark_ids', []))));
        if ($landmarks) {
            $f['landmark_ids'] = $landmarks;
        }
        $place = trim((string) $request->query('nearby_place', ''));
        if (preg_match('/^-?\d{1,3}(\.\d+)?,-?\d{1,3}(\.\d+)?$/', $place)) {
            $f['nearby_place'] = $place;
            $f['nearby_place_name'] = mb_substr(trim((string) $request->query('nearby_place_name', '')), 0, 120);
            $dist = (int) $request->query('nearby_distance');
            $f['nearby_distance'] = in_array($dist, \App\Http\Repository\Website\HomeRepository::NEARBY_DISTANCES, true) ? $dist : 1000;
        }
        foreach (['from_date', 'to_date'] as $k) {
            $d = (string) $request->query($k, '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d)) {
                $f[$k] = $d;
            }
        }
        if (isset($f['from_date'], $f['to_date']) && $f['from_date'] > $f['to_date']) {
            [$f['from_date'], $f['to_date']] = [$f['to_date'], $f['from_date']];
        }
        // 0 = instantly available, 7 / 15 = free within that many days (as on /search).
        $days = $request->query('available_days');
        if ($days !== null && $days !== '' && in_array((int) $days, [0, 7, 15], true)) {
            $f['available_days'] = (int) $days;
        }
        if (($code = trim((string) $request->query('hoarding_code', ''))) !== '') {
            $f['hoarding_code'] = mb_substr($code, 0, 40);
        }
        foreach (['min_area', 'max_area', 'min_radius', 'max_radius', 'min_price', 'max_price'] as $k) {
            $v = $request->query($k);
            if (is_numeric($v) && (float) $v >= 0) {
                $f[$k] = (float) $v + 0; // 12.0 -> 12
            }
        }
        if (($q = trim((string) $request->query('q', ''))) !== '') {
            $f['q'] = mb_substr($q, 0, 100);
        }
        if ($request->query('available')) {
            $f['available'] = 1;
        }

        return $f;
    }

    /** Choices for the vendor website's filters, limited to what this vendor has. */
    private function filterOptions(Vendor $vendor): array
    {
        $base = fn () => $this->mediaQuery($vendor);

        $towns = (clone $base())->whereNotNull('cty.id')->distinct()->pluck('cty.id');
        $nearby = [];
        foreach ($towns as $cityId) {
            try {
                $nearby[$cityId] = app(\App\Http\Services\Insights\MediaInsightsService::class)->nearbyPlacesForCity((int) $cityId);
            } catch (\Throwable $e) {
                $nearby[$cityId] = [];
            }
        }

        $ranges = (clone $base())->selectRaw('MIN(CAST(m.area_auto AS DECIMAL(15,2))) a_min, MAX(CAST(m.area_auto AS DECIMAL(15,2))) a_max, MIN(m.price) p_min, MAX(m.price) p_max')->first();

        return [
            'states'    => (clone $base())->join('states as s', 's.id', '=', 'm.state_id')->distinct()->orderBy('s.state_name')->get(['s.id', 's.state_name']),
            'areas'     => (clone $base())->whereNotNull('a.id')->distinct()->orderBy('a.area_name')->get(['a.id', 'a.area_name', 'a.city_id']),
            'areaTypes' => (clone $base())->join('areatype as at', 'at.id', '=', 'm.areatype_id')->distinct()->orderBy('at.areatype_name')->get(['at.id', 'at.areatype_name']),
            'highways'  => (clone $base())->join('highway as hw', 'hw.id', '=', 'm.highway_id')->distinct()->orderBy('hw.highway_name')->get(['hw.id', 'hw.highway_name']),
            'landmarks' => (clone $base())->join('media_landmark as ml', 'ml.media_id', '=', 'm.id')->join('landmark as l', 'l.id', '=', 'ml.landmark_id')
                ->where('l.is_deleted', 0)->distinct()->orderBy('l.landmark_name')->get(['l.id', 'l.landmark_name']),
            'nearby'    => $nearby,
            'distances' => \App\Http\Repository\Website\HomeRepository::NEARBY_DISTANCES,
            'area'      => ['min' => (int) floor((float) ($ranges->a_min ?? 0)), 'max' => (int) ceil((float) ($ranges->a_max ?? 0))],
            'price'     => ['min' => (int) floor((float) ($ranges->p_min ?? 0)), 'max' => (int) ceil((float) ($ranges->p_max ?? 0))],
            'radiusMax' => \App\Support\RadiusRange::max(),
        ];
    }

    public function show(Request $request, string $vendorSlug)
    {
        $vendor = $this->vendorBySlug($vendorSlug);

        if (!$this->hasAccess($vendor)) {
            return view('website.vendor-site.otp', [
                'vendor'      => $vendor,
                'maskedEmail' => $this->maskEmail($vendor->email),
                'otpSent'     => (bool) session('vendor_site_otp_sent.' . $vendor->id),
                'minutes'     => self::OTP_MINUTES,
            ]);
        }

        $filters = $this->siteFilters($request);

        // The same filters as the main /search (HomeRepository), limited to this vendor.
        $search = \Illuminate\Support\Arr::only($filters, self::SEARCH_FILTERS);
        $matchIds = null;
        if ($search) {
            $rows = app(\App\Http\Repository\Website\HomeRepository::class)->vendorSiteMatches($vendor->id, $search);
            // From / To Date: keep media that are free for the whole period.
            if (!empty($search['from_date']) && !empty($search['to_date'])) {
                $rows = $rows->filter(fn ($r) => empty($r->is_booked));
            }
            $matchIds = $rows->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $query = $this->mediaQuery($vendor)
            ->when($matchIds !== null, fn ($q) => $q->whereIn('m.id', $matchIds ?: [0]))
            ->when(trim((string) ($filters['q'] ?? '')), function ($q, $t) {
                $like = '%' . $t . '%';
                $q->where(fn ($w) => $w->where('m.media_title', 'like', $like)->orWhere('m.hoarding_code', 'like', $like)
                    ->orWhere('a.area_name', 'like', $like)->orWhere('m.address', 'like', $like));
            });

        $all = (clone $query)->orderBy('m.hoarding_code')->get([
            'm.id', 'm.media_title', 'm.hoarding_code', 'm.media_code', 'm.price', 'm.width', 'm.height', 'm.latitude', 'm.longitude',
            'm.panorama_image', // cards show a 360° View link when there is one
            'c.category_name', 'a.area_name', 'cty.city_name', 'd.district_name', 'i.illumination_name',
        ]);

        $status = MediaAvailability::forMedia($all->pluck('id')->all());
        $all = $all->map(function ($m) use ($status) {
            $m->availability = $status[$m->id];
            return $m;
        });

        if (!empty($filters['available'])) {
            $all = $all->filter(fn ($m) => $m->availability['status'] === MediaAvailability::AVAILABLE)->values();
        }

        $covers = MediaImage::whereIn('media_id', $all->pluck('id'))->where('is_deleted', 0)->orderBy('id')
            ->get(['media_id', 'images'])->groupBy('media_id')->map(fn ($g) => $g->first()->images);

        // Cards load 8 at a time (two rows of four): the first page with the page itself, the rest as the
        // list is scrolled (an XHR for ?page=N returns just those cards, or nothing at the end).
        $perPage = 8;
        $page = max(1, (int) $request->query('page', 1));
        $pageItems = $all->slice(($page - 1) * $perPage, $perPage)->values();

        if ($request->ajax()) {
            return $pageItems->isEmpty()
                ? response('')
                : response()->view('website.vendor-site.partials.inventory-cards', ['vendor' => $vendor, 'items' => $pageItems, 'covers' => $covers]);
        }

        $base = $this->mediaQuery($vendor);

        return view('website.vendor-site.home', [
            'vendor'     => $vendor,
            'slides'     => VendorSlider::where('vendor_id', $vendor->id)->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(),
            'media'      => $all,
            'firstItems' => $all->take($perPage)->values(),
            'perPage'    => $perPage,
            'covers'     => $covers,
            'imageBase'  => config('fileConstants.IMAGE_VIEW'),
            'filters'    => $filters,
            'categories' => (clone $base)->whereNotNull('c.id')->distinct()->orderBy('c.category_name')->get(['c.id', 'c.category_name']),
            'districts'  => (clone $base)->whereNotNull('d.id')->distinct()->orderBy('d.district_name')->get(['d.id', 'd.district_name', 'd.state_id']),
            'towns'      => (clone $base)->whereNotNull('cty.id')->distinct()->orderBy('cty.city_name')->get(['cty.id', 'cty.city_name', 'cty.district_id']),
            'options'    => $this->filterOptions($vendor),
            'totals'     => ['media' => (clone $base)->count(), 'districts' => (clone $base)->distinct()->count('m.district_id')],
            'mapPoints'  => $all->filter(fn ($m) => (float) $m->latitude && (float) $m->longitude)->values()->map(fn ($m) => [
                'id'     => $m->id,
                'img'    => isset($covers[$m->id]) ? MediaImageUrl::for($covers[$m->id]) : null,
                'price'  => '₹ ' . number_format((float) $m->price) . ' / month',
                'lat'    => (float) $m->latitude,
                'lng'    => (float) $m->longitude,
                'title'  => ($m->hoarding_code ?: $m->media_code) . ' · ' . $m->media_title,
                'meta'   => trim(($m->width && $m->height ? (float) $m->width . ' × ' . (float) $m->height . ' ft' : '') . ' · ' . $m->availability['label'], ' ·'),
                'url'    => route('vendor-site.media', [$vendor->slug, $m->id]),
                'status' => $m->availability['status'],
            ]),
        ]);
    }

    /** Map page: every live site of the vendor on one map, with search and filters. */
    public function map(string $vendorSlug)
    {
        $vendor = $this->vendorBySlug($vendorSlug);

        if (!$this->hasAccess($vendor)) {
            return redirect()->route('vendor-site.show', $vendor->slug);
        }

        // Listed by category, so the page's filter is in order.
        $media = $this->mediaQuery($vendor)->orderByRaw('c.category_name IS NULL, c.category_name')->orderBy('m.hoarding_code')->get([
            'm.id', 'm.media_title', 'm.hoarding_code', 'm.media_code', 'm.price', 'm.width', 'm.height', 'm.latitude', 'm.longitude',
            'c.category_name', 'a.area_name', 'cty.city_name',
        ]);
        $status = MediaAvailability::forMedia($media->pluck('id')->all());
        $covers = MediaImage::whereIn('media_id', $media->pluck('id'))->where('is_deleted', 0)->orderBy('id')
            ->get(['media_id', 'images'])->groupBy('media_id')->map(fn ($g) => $g->first()->images);

        $base = fn () => $this->mediaQuery($vendor);
        $ranges = (clone $base())->selectRaw('MIN(CAST(m.area_auto AS DECIMAL(15,2))) a_min, MAX(CAST(m.area_auto AS DECIMAL(15,2))) a_max, MIN(m.price) p_min, MAX(m.price) p_max')->first();

        // The /explore filter groups. State and Area Type keep the complete /explore lists;
        // the others list only what this vendor's own media have.
        $m = app(\App\Http\Controllers\Website\ExploreController::class)->masters();
        $rows = fn ($list, string $name) => collect($list)->map(fn ($r) => ['id' => $r->id, 'name' => $r->{$name}]);
        $own = fn (string $id, string $name) => (clone $base())->whereNotNull($id)->distinct()->orderBy($name)
            ->get([$id . ' as id', $name . ' as name'])->map(fn ($r) => ['id' => $r->id, 'name' => $r->name]);
        $opts = $this->filterOptions($vendor);

        return view('website.vendor-site.map', [
            'groups'  => [
                ['key' => 'state_id',     'title' => 'State',      'search' => false, 'rows' => $rows($m['states'], 'state_name')],
                ['key' => 'district_id',  'title' => 'District',   'search' => true,  'rows' => $own('d.id', 'd.district_name')],
                ['key' => 'city_id',      'title' => 'Town',       'search' => true,  'rows' => $own('cty.id', 'cty.city_name')],
                ['key' => 'area_id',      'title' => 'Area',       'search' => true,  'rows' => $own('a.id', 'a.area_name')],
                ['key' => 'category_id',  'title' => 'Media Type', 'search' => false, 'rows' => $own('c.id', 'c.category_name')],
                ['key' => 'areatype_id',  'title' => 'Area Type',  'search' => false, 'rows' => $rows($m['areaTypes'], 'areatype_name')],
                ['key' => 'highway_id',   'title' => 'Highway',    'search' => true,  'rows' => $opts['highways']->map(fn ($r) => ['id' => $r->id, 'name' => $r->highway_name])],
                ['key' => 'landmark_ids', 'title' => 'Landmarks',  'search' => true,  'rows' => $opts['landmarks']->map(fn ($r) => ['id' => $r->id, 'name' => $r->landmark_name])],
            ],
            'price'     => ['min' => (int) floor((float) ($ranges->p_min ?? 0)), 'max' => (int) ceil((float) ($ranges->p_max ?? 0))],
            'area'      => ['min' => (int) floor((float) ($ranges->a_min ?? 0)), 'max' => (int) ceil((float) ($ranges->a_max ?? 0))],
            'radiusMax' => \App\Support\RadiusRange::max(),
            'vendor'  => $vendor,
            'colours' => ['available' => '#16A34A', 'booked' => '#2563EB', 'blocked' => '#DC2626', 'ask' => '#D97706'],
            'sites'   => $media->map(function ($m) use ($vendor, $status, $covers) {
                $av = $status[$m->id];
                $hasPin = (float) $m->latitude && (float) $m->longitude;
                $name = $m->media_title ?: $m->category_name;
                $size = (float) $m->width > 0 && (float) $m->height > 0 ? (float) $m->width . ' × ' . (float) $m->height . ' ft' : null;

                return [
                    'id'     => $m->id,
                    'title'  => collect([$m->hoarding_code ?: $m->media_code, $name])->filter()->implode(' · '),
                    'place'  => collect([$m->area_name, $m->city_name])->filter()->unique()->implode(', '),
                    'city'   => $m->city_name,
                    'category' => $m->category_name ?: 'Other',
                    'meta'   => collect([$m->category_name, $size, $av['label']])->filter()->implode(' · '),
                    'price'  => '₹ ' . number_format((float) $m->price) . ' / month',
                    'img'    => isset($covers[$m->id]) ? MediaImageUrl::for($covers[$m->id]) : null,
                    'url'    => route('vendor-site.media', [$vendor->slug, $m->id]),
                    'lat'    => $hasPin ? (float) $m->latitude : null,
                    'lng'    => $hasPin ? (float) $m->longitude : null,
                    'status' => $av['status'],
                ];
            }),
        ]);
    }

    /**
     * The map page's filters (GET, JSON): the /explore search (ExploreRepository),
     * limited to this vendor's live media. Returns the ids to keep on the map.
     */
    public function mapSearch(Request $request, string $vendorSlug)
    {
        $vendor = $this->vendorBySlug($vendorSlug);
        if (!$this->hasAccess($vendor)) {
            return response()->json(['ids' => []], 403);
        }

        $ints = fn ($v) => array_values(array_filter(array_map('intval', (array) $v), fn ($i) => $i > 0));
        $date = fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : null;
        $num  = fn ($v) => is_numeric($v) && (float) $v >= 0 ? (float) $v : null;

        $filters = [
            'state_id'     => $ints($request->query('state_id')),
            'district_id'  => $ints($request->query('district_id')),
            'city_id'      => $ints($request->query('city_id')),
            'area_id'      => $ints($request->query('area_id')),
            'category_id'  => $ints($request->query('category_id')),
            'areatype_id'  => $ints($request->query('areatype_id')),
            'highway_id'   => $ints($request->query('highway_id')),
            'landmark_ids' => $ints($request->query('landmark_ids')),
            'radius_id'    => $num($request->query('radius_id')),
            'min_price'    => $num($request->query('min_price')),
            'max_price'    => $num($request->query('max_price')),
            'min_area'     => $num($request->query('min_area')),
            'max_area'     => $num($request->query('max_area')),
            'from_date'    => $date($request->query('from_date')),
            'to_date'      => $date($request->query('to_date')),
            'q'            => mb_substr(trim((string) $request->query('q', '')), 0, 200) ?: null,
        ];

        $ids = app(\App\Http\Repository\Website\ExploreRepository::class)->vendorMatchIds($vendor->id, $filters);
        // Only live media (approved) — the same set the page shows.
        $live = $this->mediaQuery($vendor)->whereIn('m.id', $ids ?: [0])->pluck('m.id')->map(fn ($id) => (int) $id)->all();

        return response()->json(['ids' => $live]);
    }

    /** Separate Contact page: brand-adda.co.in/{vendor-slug}/contact */
    public function contact(string $vendorSlug)
    {
        $vendor = $this->vendorBySlug($vendorSlug);

        if (!$this->hasAccess($vendor)) {
            return redirect()->route('vendor-site.show', $vendor->slug);
        }

        $mediaOptions = $this->mediaQuery($vendor)->orderBy('m.hoarding_code')
            ->get(['m.id', 'm.hoarding_code', 'm.media_code', 'm.media_title', 'a.area_name']);

        return view('website.vendor-site.contact', [
            'vendor'       => $vendor,
            'contact'      => $this->contactFor($vendor),
            'mediaOptions' => $mediaOptions,
            'selectedMedia' => (int) request('media') ?: null,
            'draft'         => $this->enquiryDraft($mediaOptions->firstWhere('id', (int) request('media'))),
            'totals'       => [
                'media'     => $mediaOptions->count(),
                'districts' => $this->mediaQuery($vendor)->distinct()->count('m.district_id'),
            ],
        ]);
    }

    /** First line of the enquiry when dates were picked on a media page (?media=&from=&to=). */
    private function enquiryDraft(?object $media): string
    {
        $day = fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? \Carbon\Carbon::parse($v) : null;
        $from = $day(request('from'));
        $to = $day(request('to'));

        if (!$media || !$from || !$to || $to->lt($from)) {
            return '';
        }

        return 'I would like to book ' . ($media->media_title ?: $media->hoarding_code) . ' from ' . $from->format('d M Y') . ' to ' . $to->format('d M Y') . '. Please share the price for these dates.';
    }

    /**
     * Contact form on the vendor website. The enquiry is saved for that
     * vendor and appears in its panel under Enquiries, with a notification.
     */
    public function submitEnquiry(Request $request, string $vendorSlug)
    {
        $vendor = $this->vendorBySlug($vendorSlug);

        if (!$this->hasAccess($vendor)) {
            return redirect()->route('vendor-site.show', $vendor->slug);
        }

        // Hidden field real visitors never fill: quietly drop bot posts.
        if (filled($request->input('website'))) {
            return redirect()->route('vendor-site.contact', $vendor->slug)->with('enquiry_sent', true);
        }

        // Tidy what people type before checking it: collapse spaces, and reduce
        // the mobile to its 10 digits ("+91 98765-43210" → "9876543210").
        $clean = fn ($v) => is_string($v) ? trim(preg_replace('/\s+/u', ' ', $v)) : $v;
        $mobile = preg_replace('/\D/', '', (string) $request->input('mobile'));
        if (strlen($mobile) === 12 && str_starts_with($mobile, '91')) {
            $mobile = substr($mobile, 2);
        } elseif (strlen($mobile) === 11 && str_starts_with($mobile, '0')) {
            $mobile = substr($mobile, 1);
        }
        $request->merge([
            'name'    => $clean($request->input('name')),
            'email'   => strtolower((string) $clean($request->input('email'))),
            'mobile'  => $mobile,
            'company' => $clean($request->input('company')),
            'city'    => $clean($request->input('city')),
            'message' => is_string($request->input('message')) ? trim($request->input('message')) : $request->input('message'),
        ]);

        $data = $request->validate([
            'name'     => ['required', 'string', 'min:3', 'max:60', 'regex:/^[\pL][\pL .\'-]*$/u'],
            'mobile'   => ['required', 'digits:10', 'regex:/^[6-9]\d{9}$/', 'not_regex:/^(\d)\1{9}$/'],
            'email'    => ['required', 'max:100', 'email:rfc,strict', 'regex:/^[^@\s]+@[^@\s]+\.[a-z]{2,}$/i'],
            'company'  => ['nullable', 'string', 'min:2', 'max:100', 'regex:/^[\pL\pN][\pL\pN .,&\'()\/-]*$/u'],
            'city'     => ['nullable', 'string', 'min:2', 'max:50', 'regex:/^[\pL][\pL .\'-]*$/u'],
            'media_id' => 'nullable|integer',
            'message'  => ['required', 'string', 'min:10', 'max:1000', 'regex:/\pL{2,}/u'],
        ], [
            'name.required'     => 'Please enter your full name.',
            'name.min'          => 'Name must be at least 3 characters.',
            'name.max'          => 'Name cannot be more than 60 characters.',
            'name.regex'        => 'Name can contain only letters, spaces, dots, hyphens and apostrophes.',
            'mobile.required'   => 'Please enter your mobile number.',
            'mobile.digits'     => 'Mobile number must be exactly 10 digits.',
            'mobile.regex'      => 'Mobile number must start with 6, 7, 8 or 9.',
            'mobile.not_regex'  => 'Please enter a real mobile number.',
            'email.required'    => 'Please enter your email ID.',
            'email.email'       => 'Please enter a valid email ID, e.g. name@company.com.',
            'email.regex'       => 'Please enter a valid email ID, e.g. name@company.com.',
            'email.max'         => 'Email ID cannot be more than 100 characters.',
            'company.min'       => 'Company / brand must be at least 2 characters.',
            'company.max'       => 'Company / brand cannot be more than 100 characters.',
            'company.regex'     => 'Company / brand contains characters that are not allowed.',
            'city.min'          => 'City must be at least 2 characters.',
            'city.max'          => 'City cannot be more than 50 characters.',
            'city.regex'        => 'City can contain only letters and spaces.',
            'message.required'  => 'Please write your message.',
            'message.min'       => 'Please tell us a little more (at least 10 characters).',
            'message.max'       => 'Message cannot be more than 1000 characters.',
            'message.regex'     => 'Please write your message in words.',
        ]);

        // At most 5 sent enquiries per visitor per vendor in 10 minutes.
        $limitKey = 'vendor-enquiry:' . $vendor->id . ':' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($limitKey, 5)) {
            $minutes = (int) ceil(\Illuminate\Support\Facades\RateLimiter::availableIn($limitKey) / 60);
            return back()->withInput()->withErrors([
                'message' => 'You have sent several enquiries already. Please try again in ' . $minutes . ' minute' . ($minutes === 1 ? '' : 's') . ', or call us directly.',
            ]);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($limitKey, 600);

        // Only this vendor's live media can be referenced.
        if (!empty($data['media_id']) && !$this->mediaQuery($vendor)->where('m.id', $data['media_id'])->exists()) {
            $data['media_id'] = null;
        }

        $enquiry = \App\Models\VendorEnquiry::create($data + [
            'vendor_id' => $vendor->id,
            'status'    => 'new',
            'ip'        => $request->ip(),
        ]);

        $about = $enquiry->media ? ' about ' . ($enquiry->media->display_code) : '';
        \App\Support\VendorAudit::notifyVendor(
            $vendor->id,
            'enquiry_received',
            'New enquiry from ' . $enquiry->name,
            \Illuminate\Support\Str::limit($enquiry->message, 120) . $about,
            route('vendor.enquiries', ['open' => $enquiry->id]),
            $enquiry->media_id
        );

        return redirect()->route('vendor-site.contact', $vendor->slug)->with('enquiry_sent', true);
    }

    /**
     * Contact details shown on the vendor website, from the vendor's record in
     * the admin Vendor master (mobile, email, address, city).
     */
    private function contactFor(Vendor $vendor): array
    {
        $place = DB::table('vendors as v')
            ->leftJoin('cities as c', 'c.id', '=', 'v.city_id')
            ->leftJoin('districts as d', 'd.id', '=', 'v.district_id')
            ->leftJoin('states as s', 's.id', '=', 'v.state_id')
            ->where('v.id', $vendor->id)
            ->first(['c.city_name', 'd.district_name', 's.state_name']);

        $digits = preg_replace('/\D/', '', (string) $vendor->mobile);
        // WhatsApp needs the country code; Indian 10-digit numbers get 91.
        $whatsapp = strlen($digits) === 10 ? '91' . $digits : $digits;

        $location = collect([$place->city_name ?? null, $place->district_name ?? null, $place->state_name ?? null])
            ->filter()->unique()->implode(', ');

        // Don't repeat the city when the address already spells it out.
        if ($vendor->address && !empty($place->city_name) && stripos($vendor->address, $place->city_name) !== false) {
            $location = '';
        }

        return [
            'mobile'   => $vendor->mobile,
            'tel'      => $digits ? '+' . (strlen($digits) === 10 ? '91' . $digits : $digits) : null,
            'whatsapp' => $whatsapp ?: null,
            'email'    => $vendor->email,
            'address'  => $vendor->address,
            'location' => $location,
            'mapsUrl'  => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(trim($vendor->address . ', ' . ($location ?: ($place->city_name ?? '')), ', ')),
        ];
    }

    public function media(string $vendorSlug, int $mediaId)
    {
        $vendor = $this->vendorBySlug($vendorSlug);

        if (!$this->hasAccess($vendor)) {
            return redirect()->route('vendor-site.show', $vendor->slug);
        }

        $media = $this->mediaQuery($vendor)
            ->leftJoin('areatype as at', 'at.id', '=', 'm.areatype_id')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->where('m.id', $mediaId)
            ->first(['m.*', 'c.category_name', 'a.area_name', 'cty.city_name', 'd.district_name', 's.state_name',
                'i.illumination_name', 'at.areatype_name']);

        abort_unless($media, 404);

        $ranges = MediaAvailability::ranges($mediaId);
        $availability = MediaAvailability::forMedia([$mediaId])[$mediaId];

        // Next free day: the day after the range covering today (if any), skipping any
        // range that follows on directly or overlaps.
        $availableFrom = null;
        if ($availability['to']) {
            $availableFrom = \Carbon\Carbon::parse($availability['to'])->addDay();
            foreach ($ranges as $r) {
                if ($r->from_date <= $availableFrom->toDateString() && $r->to_date >= $availableFrom->toDateString()) {
                    $availableFrom = \Carbon\Carbon::parse($r->to_date)->addDay();
                }
            }
        }

        // More sites of this vendor: same city first, then the same category.
        $similar = $this->mediaQuery($vendor)
            ->where('m.id', '<>', $mediaId)
            ->orderByRaw('m.city_id = ? DESC, m.category_id = ? DESC, m.id DESC', [(int) $media->city_id, (int) $media->category_id])
            ->limit(4)
            ->get(['m.id', 'm.media_title', 'm.price', 'a.area_name', 'cty.city_name']);
        $similarStatus = MediaAvailability::forMedia($similar->pluck('id')->all());
        $similar->each(fn ($s) => $s->availability = $similarStatus[$s->id]);

        return view('website.vendor-site.media', [
            'vendor'        => $vendor,
            'media'         => $media,
            'images'        => MediaImage::where('media_id', $mediaId)->where('is_deleted', 0)->get(),
            'imageBase'     => config('fileConstants.IMAGE_VIEW'),
            'availability'  => $availability,
            'availableFrom' => $availableFrom,
            'ranges'        => $ranges,
            // The calendar also marks bookings that are over, as the Brand Adda media page does.
            'bookedRanges'  => DB::table('media_booked_date')->where('media_id', $mediaId)->where('is_active', 1)->where('is_deleted', 0)
                ->orderBy('from_date')->get(['from_date as from', 'to_date as to']),
            'contact'       => $this->contactFor($vendor),
            'similar'       => $similar,
            'similarCovers' => MediaImage::whereIn('media_id', $similar->pluck('id'))->where('is_deleted', 0)->orderBy('id')
                ->get(['media_id', 'images'])->groupBy('media_id')->map(fn ($g) => $g->first()->images),
            'landmarks'     => DB::table('media_landmark as ml')->join('landmark as l', 'l.id', '=', 'ml.landmark_id')
                ->where('ml.media_id', $mediaId)->where('l.is_deleted', 0)->pluck('l.landmark_name'),
        ]);
    }

    public function sendOtp(string $vendorSlug)
    {
        $vendor = $this->vendorBySlug($vendorSlug);

        if (!$vendor->email) {
            return back()->with('error', 'This vendor has no registered email. Contact Brand Adda.');
        }

        $otp = (string) random_int(100000, 999999);
        // Kept in this browser's session: only the browser that asked can use it.
        session(['vendor_site_otp.' . $vendor->id => [
            'hash'     => Hash::make($otp),
            'expires'  => now()->addMinutes(self::OTP_MINUTES)->timestamp,
            'attempts' => 0,
        ]]);

        try {
            Mail::to($vendor->email)->send(new VendorSiteOtpMail($vendor, $otp, self::OTP_MINUTES));
        } catch (\Throwable $e) {
            Log::error('Vendor site OTP mail failed: ' . $e->getMessage());
            return back()->with('error', 'We could not send the code right now. Please try again in a few minutes.');
        }

        session(['vendor_site_otp_sent.' . $vendor->id => true]);

        return back()->with('success', 'A 6-digit code was sent to ' . $this->maskEmail($vendor->email) . '.');
    }

    public function verifyOtp(Request $request, string $vendorSlug)
    {
        $vendor = $this->vendorBySlug($vendorSlug);

        $otp = preg_replace('/\D/', '', implode('', (array) $request->input('otp', [])) ?: (string) $request->input('code'));
        $key = 'vendor_site_otp.' . $vendor->id;
        $pending = session($key);

        if (!$pending || $pending['expires'] < now()->timestamp || $pending['attempts'] >= 5) {
            session()->forget([$key, 'vendor_site_otp_sent.' . $vendor->id]);
            return back()->with('error', 'The code has expired. Send a new one.');
        }

        if (strlen($otp) !== 6 || !Hash::check($otp, $pending['hash'])) {
            $pending['attempts']++;
            session([$key => $pending]);
            return back()->with('error', 'That code is not correct. Please check and try again.');
        }

        session()->forget($key);
        session()->forget('vendor_site_otp_sent.' . $vendor->id);
        session([$this->sessionKey($vendor) => now()->addHours(self::ACCESS_HOURS)->timestamp]);

        return redirect()->route('vendor-site.show', $vendor->slug);
    }
}

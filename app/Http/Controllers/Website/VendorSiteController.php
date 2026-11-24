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

        $filters = $request->only(['category_id', 'district_id', 'city_id', 'q', 'available']);

        $query = $this->mediaQuery($vendor)
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('m.category_id', (int) $v))
            ->when($filters['district_id'] ?? null, fn ($q, $v) => $q->where('m.district_id', (int) $v))
            ->when($filters['city_id'] ?? null, fn ($q, $v) => $q->where('m.city_id', (int) $v))
            ->when(trim((string) ($filters['q'] ?? '')), function ($q, $t) {
                $like = '%' . $t . '%';
                $q->where(fn ($w) => $w->where('m.media_title', 'like', $like)->orWhere('m.hoarding_code', 'like', $like)
                    ->orWhere('a.area_name', 'like', $like)->orWhere('m.address', 'like', $like));
            });

        $all = (clone $query)->orderBy('m.hoarding_code')->get([
            'm.id', 'm.media_title', 'm.hoarding_code', 'm.media_code', 'm.price', 'm.width', 'm.height', 'm.latitude', 'm.longitude',
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

        // Cards load 6 at a time: the first page with the page itself, the rest as the
        // list is scrolled (an XHR for ?page=N returns just those cards, or nothing at the end).
        $perPage = 6;
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
            'districts'  => (clone $base)->whereNotNull('d.id')->distinct()->orderBy('d.district_name')->get(['d.id', 'd.district_name']),
            'towns'      => (clone $base)->whereNotNull('cty.id')->distinct()->orderBy('cty.city_name')->get(['cty.id', 'cty.city_name', 'cty.district_id']),
            'totals'     => ['media' => (clone $base)->count(), 'districts' => (clone $base)->distinct()->count('m.district_id')],
            'mapPoints'  => $all->filter(fn ($m) => $m->latitude && $m->longitude)->values()->map(fn ($m) => [
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
            'totals'       => [
                'media'     => $mediaOptions->count(),
                'districts' => $this->mediaQuery($vendor)->distinct()->count('m.district_id'),
            ],
        ]);
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

        // Next free day: the day after the range covering today (if any).
        $availableFrom = null;
        if ($availability['to']) {
            $availableFrom = \Carbon\Carbon::parse($availability['to'])->addDay();
        }

        return view('website.vendor-site.media', [
            'vendor'        => $vendor,
            'media'         => $media,
            'images'        => MediaImage::where('media_id', $mediaId)->where('is_deleted', 0)->get(),
            'imageBase'     => config('fileConstants.IMAGE_VIEW'),
            'availability'  => $availability,
            'availableFrom' => $availableFrom,
            'ranges'        => $ranges,
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

<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Mail\VendorSiteOtpMail;
use App\Models\MediaImage;
use App\Models\Vendor;
use App\Models\VendorSlider;
use App\Support\MediaAvailability;
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

        $base = $this->mediaQuery($vendor);

        return view('website.vendor-site.home', [
            'vendor'     => $vendor,
            'slides'     => VendorSlider::where('vendor_id', $vendor->id)->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(),
            'media'      => $all,
            'covers'     => $covers,
            'imageBase'  => config('fileConstants.IMAGE_VIEW'),
            'filters'    => $filters,
            'categories' => (clone $base)->whereNotNull('c.id')->distinct()->orderBy('c.category_name')->get(['c.id', 'c.category_name']),
            'districts'  => (clone $base)->whereNotNull('d.id')->distinct()->orderBy('d.district_name')->get(['d.id', 'd.district_name']),
            'towns'      => (clone $base)->whereNotNull('cty.id')->distinct()->orderBy('cty.city_name')->get(['cty.id', 'cty.city_name', 'cty.district_id']),
            'totals'     => ['media' => (clone $base)->count(), 'districts' => (clone $base)->distinct()->count('m.district_id')],
            'mapPoints'  => $all->filter(fn ($m) => $m->latitude && $m->longitude)->values()->map(fn ($m) => [
                'lat'    => (float) $m->latitude,
                'lng'    => (float) $m->longitude,
                'title'  => ($m->hoarding_code ?: $m->media_code) . ' · ' . $m->media_title,
                'meta'   => trim(($m->width && $m->height ? (float) $m->width . ' × ' . (float) $m->height . ' ft' : '') . ' · ' . $m->availability['label'], ' ·'),
                'url'    => route('vendor-site.media', [$vendor->slug, $m->id]),
                'status' => $m->availability['status'],
            ]),
        ]);
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

<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use App\Mail\VendorCredentialsMail;
use App\Models\MediaImage;
use App\Models\MediaManagement;
use App\Models\PanelNotification;
use App\Models\Vendor;
use App\Models\VendorActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Support\VendorAudit;

/**
 * Super Admin side of Vendor Inventory Management (PRD 2.12 + 2.11 B + 2.1.4):
 * the approval queue, assigning inventory to vendors, the vendor activity
 * log, vendor notifications, and issuing vendor login details.
 */
class VendorPortalController extends Controller
{
    private const STATUS_LABELS = ['pending' => 'Pending Approval', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    private function vendors()
    {
        return Vendor::where('is_deleted', 0)->orderBy('vendor_name')->get(['id', 'vendor_name', 'vendor_code']);
    }

    /* ------------------------------ approvals ------------------------------ */

    public function approvals(Request $request)
    {
        $status = array_key_exists($request->status, self::STATUS_LABELS) ? $request->status : 'pending';
        $vendorId = (int) $request->vendor_id;
        $term = trim((string) $request->q);

        // Left join: a submission whose vendor was later removed still needs a decision.
        $media = DB::table('media_management as m')
            ->leftJoin('vendors as v', 'v.id', '=', 'm.vendor_id')
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
            ->leftJoin('cities as cty', 'cty.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('users as rv', 'rv.id', '=', 'm.reviewed_by')
            ->where('m.is_deleted', 0)
            ->whereNotNull('m.submitted_by_vendor_id')
            ->where('m.approval_status', $status)
            ->when($vendorId, fn ($q) => $q->where('m.vendor_id', $vendorId))
            ->when($term !== '', function ($q) use ($term) {
                $like = '%' . $term . '%';
                $q->where(fn ($w) => $w->where('m.hoarding_code', 'like', $like)->orWhere('m.media_title', 'like', $like)
                    ->orWhere('a.area_name', 'like', $like)->orWhere('cty.city_name', 'like', $like));
            })
            ->orderByDesc($status === 'pending' ? 'm.submitted_at' : 'm.reviewed_at')
            ->select(['m.id', 'm.hoarding_code', 'm.media_code', 'm.media_title', 'm.price', 'm.submitted_at', 'm.reviewed_at',
                'm.rejection_reason', 'v.vendor_name', 'v.vendor_code', 'c.category_name', 'cty.city_name', 'a.area_name',
                'rv.name as reviewer_name'])
            ->paginate(20)
            ->withQueryString();

        // First photo of each row, for the thumbnail column.
        $thumbs = MediaImage::whereIn('media_id', $media->pluck('id'))->where('is_deleted', 0)->orderBy('id')
            ->get(['media_id', 'images'])->groupBy('media_id')->map(fn ($g) => $g->first()->images);

        $counts = DB::table('media_management')->where('is_deleted', 0)->whereNotNull('submitted_by_vendor_id')
            ->when($vendorId, fn ($q) => $q->where('vendor_id', $vendorId))
            ->selectRaw('approval_status, COUNT(*) as total')->groupBy('approval_status')->pluck('total', 'approval_status');

        return view('superadm.vendor-portal.approvals', [
            'media'   => $media,
            'thumbs'  => $thumbs,
            'status'  => $status,
            'labels'  => self::STATUS_LABELS,
            'counts'  => $counts,
            'vendors' => $this->vendors(),
        ]);
    }

    public function review(int $id)
    {
        $media = DB::table('media_management as m')
            ->leftJoin('vendors as v', 'v.id', '=', 'm.vendor_id')
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('cities as cty', 'cty.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('illuminations as i', 'i.id', '=', 'm.illumination_id')
            ->leftJoin('areatype as at', 'at.id', '=', 'm.areatype_id')
            ->leftJoin('highway as h', 'h.id', '=', 'm.highway_id')
            ->leftJoin('users as rv', 'rv.id', '=', 'm.reviewed_by')
            ->where('m.id', $id)
            ->where('m.is_deleted', 0)
            ->first(['m.*', 'v.vendor_name', 'v.vendor_code', 'v.email as vendor_email', 'v.mobile as vendor_mobile',
                'c.category_name', 's.state_name', 'd.district_name', 'cty.city_name', 'a.area_name', 'i.illumination_name',
                'at.areatype_name', 'h.highway_name', 'rv.name as reviewer_name']);

        abort_unless($media, 404);

        return view('superadm.vendor-portal.review', [
            'media'     => $media,
            'images'    => MediaImage::where('media_id', $id)->where('is_deleted', 0)->get(),
            'sizes'     => \App\Models\MediaLocationSize::where('media_id', $id)->get(),
            'landmarks' => DB::table('media_landmark as ml')->join('landmark as l', 'l.id', '=', 'ml.landmark_id')
                ->where('ml.media_id', $id)->where('l.is_deleted', 0)->pluck('l.landmark_name'),
            'history'   => VendorActivityLog::retained()->where('media_id', $id)->latest('created_at')->limit(15)->get(),
            'labels'    => self::STATUS_LABELS,
        ]);
    }

    public function approve(int $id)
    {
        $media = MediaManagement::where('id', $id)->where('is_deleted', 0)->firstOrFail();

        if ($media->approval_status === 'approved') {
            return back()->with('error', 'This media is already approved.');
        }

        $media->forceFill([
            'approval_status'  => 'approved',
            'rejection_reason' => null,
            'is_active'        => 1,
            'reviewed_by'      => session('user_id'),
            'reviewed_at'      => now(),
        ])->save();

        VendorAudit::adminDid($media->vendor_id, 'Media Approved', [
            'Vendor'                => optional($media->vendor)->vendor_name,
            'Approving Super Admin' => session('name'),
        ], $media);

        if ($media->vendor_id) {
            VendorAudit::notifyVendor($media->vendor_id, 'media_approved', 'Media approved – ' . $media->display_code,
                ($media->media_title ?: $media->display_code) . ' is approved and now live on Brand Adda.',
                route('vendor.media.show', $media->id), $media->id);
        }

        return redirect()->route('admin.vendor.approvals')->with('success', $media->display_code . ' approved and published.');
    }

    public function reject(Request $request, int $id)
    {
        $data = $request->validate(['reason' => 'required|string|min:5|max:1000'], [
            'reason.required' => 'Enter the reason for rejection; the vendor will see it.',
            'reason.min'      => 'Please give a little more detail in the rejection reason.',
        ]);

        $media = MediaManagement::where('id', $id)->where('is_deleted', 0)->firstOrFail();

        $media->forceFill([
            'approval_status'  => 'rejected',
            'rejection_reason' => $data['reason'],
            'is_active'        => 0,
            'reviewed_by'      => session('user_id'),
            'reviewed_at'      => now(),
        ])->save();

        VendorAudit::adminDid($media->vendor_id, 'Media Rejected', [
            'Vendor'           => optional($media->vendor)->vendor_name,
            'Rejection reason' => $data['reason'],
        ], $media);

        if ($media->vendor_id) {
            VendorAudit::notifyVendor($media->vendor_id, 'media_rejected', 'Media rejected – ' . $media->display_code,
                'Reason: ' . $data['reason'], route('vendor.media.edit', $media->id), $media->id);
        }

        return redirect()->route('admin.vendor.approvals')->with('success', $media->display_code . ' rejected. The vendor has been notified.');
    }

    /* ---------------------- inventory assignment --------------------------- */

    public function inventory(Request $request)
    {
        $vendorId = (int) $request->vendor_id;
        $term = trim((string) $request->q);

        $base = DB::table('media_management as m')
            ->leftJoin('vendors as v', 'v.id', '=', 'm.vendor_id')
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
            ->leftJoin('cities as cty', 'cty.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->where('m.is_deleted', 0)
            ->when($term !== '', function ($q) use ($term) {
                $like = '%' . $term . '%';
                $q->where(fn ($w) => $w->where('m.hoarding_code', 'like', $like)->orWhere('m.media_title', 'like', $like)->orWhere('a.area_name', 'like', $like));
            })
            ->select(['m.id', 'm.hoarding_code', 'm.media_code', 'm.media_title', 'm.price', 'm.is_active', 'm.approval_status',
                'v.vendor_name', 'c.category_name', 'cty.city_name', 'a.area_name']);

        // Server-side paging: the vendor list uses ?page / ?per_page, the selected vendor's
        // media its own ?mpage / ?mper_page, so paging one list never resets the other.
        $sizes = [10, 20, 50, 100];
        $perPage  = in_array((int) $request->per_page, $sizes, true) ? (int) $request->per_page : 10;
        $mPerPage = in_array((int) $request->mper_page, $sizes, true) ? (int) $request->mper_page : 20;

        $assigned = null;
        $assignedPending = 0;
        if ($vendorId) {
            $own = (clone $base)->where('m.vendor_id', $vendorId);
            $assignedPending = (clone $own)->where('m.approval_status', 'pending')->count();
            $assigned = $own->orderByDesc('m.id')->paginate($mPerPage, ['*'], 'mpage')->withQueryString()->fragment('manage');
        }

        $summary = DB::table('vendors as v')
            ->leftJoin('media_management as m', function ($j) {
                $j->on('m.vendor_id', '=', 'v.id')->where('m.is_deleted', 0);
            })
            ->where('v.is_deleted', 0)
            // Vendor search: name, code, email or site address.
            ->when(trim((string) $request->vq) !== '', function ($q) use ($request) {
                $like = '%' . addcslashes(trim((string) $request->vq), '\\%_') . '%';
                $q->where(fn ($w) => $w->where('v.vendor_name', 'like', $like)->orWhere('v.vendor_code', 'like', $like)
                    ->orWhere('v.email', 'like', $like)->orWhere('v.slug', 'like', $like));
            })
            ->groupBy('v.id', 'v.vendor_name', 'v.vendor_code', 'v.email', 'v.slug', 'v.credentials_sent_at', 'v.last_login_at', 'v.is_active')
            ->orderByDesc('v.id')
            ->select([
                'v.id', 'v.vendor_name', 'v.vendor_code', 'v.email', 'v.slug', 'v.credentials_sent_at', 'v.last_login_at', 'v.is_active',
                DB::raw('COUNT(m.id) as total'),
                DB::raw("SUM(CASE WHEN m.approval_status = 'pending' THEN 1 ELSE 0 END) as pending"),
            ])
            ->paginate($perPage)
            ->withQueryString();

        return view('superadm.vendor-portal.inventory', [
            'vendors'  => $this->vendors(),
            'vendorId' => $vendorId,
            'vendor'   => $vendorId ? Vendor::find($vendorId) : null,
            'assigned' => $assigned,
            'assignedPending' => $assignedPending,
            'summary'  => $summary,
            'perPage'  => $perPage,
            'mPerPage' => $mPerPage,
            'pageSizes' => $sizes,
        ]);
    }

    public function assign(Request $request)
    {
        $data = $request->validate([
            'vendor_id'  => 'required|integer|exists:vendors,id',
            'media_ids'  => 'required|array|min:1',
            'media_ids.*' => 'integer',
        ], ['media_ids.required' => 'Select the media to assign.']);

        $vendor = Vendor::findOrFail($data['vendor_id']);
        $media  = MediaManagement::whereIn('id', $data['media_ids'])->where('is_deleted', 0)->get();

        foreach ($media as $m) {
            $previousVendor = $m->vendor_id;
            if ((int) $previousVendor === $vendor->id) {
                continue;
            }
            $m->forceFill(['vendor_id' => $vendor->id])->save();

            VendorAudit::adminDid($vendor->id, 'Inventory Added for Vendor', ['Vendor' => $vendor->vendor_name], $m);
            VendorAudit::notifyVendor($vendor->id, 'inventory_assigned', 'Inventory assigned – ' . $m->display_code,
                'Brand Adda assigned ' . ($m->media_title ?: $m->display_code) . ' to your account.', route('vendor.media.show', $m->id), $m->id);

            if ($previousVendor) {
                VendorAudit::adminDid($previousVendor, 'Inventory Assignment Removed', ['Moved to' => $vendor->vendor_name], $m);
                VendorAudit::notifyVendor($previousVendor, 'inventory_removed', 'Inventory assignment removed – ' . $m->display_code,
                    ($m->media_title ?: $m->display_code) . ' is no longer assigned to you.', null, $m->id);
            }
        }

        return back()->with('success', $media->count() . ' media assigned to ' . $vendor->vendor_name . '.');
    }

    public function unassign(Request $request)
    {
        $data = $request->validate([
            'vendor_id'   => 'required|integer|exists:vendors,id',
            'media_ids'   => 'required|array|min:1',
            'media_ids.*' => 'integer',
        ], ['media_ids.required' => 'Select the media to remove from this vendor.']);

        $media = MediaManagement::whereIn('id', $data['media_ids'])->where('vendor_id', $data['vendor_id'])->get();

        foreach ($media as $m) {
            $m->forceFill(['vendor_id' => null])->save();

            VendorAudit::adminDid((int) $data['vendor_id'], 'Inventory Assignment Removed', [], $m);
            VendorAudit::notifyVendor((int) $data['vendor_id'], 'inventory_removed', 'Inventory assignment removed – ' . $m->display_code,
                ($m->media_title ?: $m->display_code) . ' is no longer assigned to you.', null, $m->id);
        }

        return back()->with('success', $media->count() . ' media removed from the vendor.');
    }

    /* --------------------------- activity log ------------------------------ */

    public function activity(Request $request)
    {
        // Vendor / activity / media-code filters; the summary cards count within these.
        $base = VendorActivityLog::retained()
            ->when($request->vendor_id, fn ($q, $v) => $q->where('vendor_id', (int) $v))
            ->when($request->action, fn ($q, $a) => $q->where('action', $a))
            ->when(trim((string) $request->q), fn ($q, $t) => $q->where('media_code', 'like', '%' . $t . '%'));

        // ...plus "done by" and dates for the list (the cards set these, so they are left out of
        // the card counts — otherwise picking "By vendors" would show "By Brand Adda" as 0).
        $filtered = (clone $base)
            ->when($request->actor, fn ($q, $a) => $q->where('actor_type', $a))
            ->when($request->from_date, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to_date, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));

        $byActor = (clone $base)->selectRaw('actor_type, COUNT(*) as total')->groupBy('actor_type')->pluck('total', 'actor_type');

        // Server-side pagination: only one page of rows is read from the database.
        $perPage = in_array((int) $request->per_page, [10, 20, 50, 100], true) ? (int) $request->per_page : 10;

        $log = (clone $filtered)->with('vendor:id,vendor_name')
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        // A page past the end (e.g. after filtering) goes to the last page.
        if ($log->isEmpty() && $log->currentPage() > 1 && $log->lastPage() >= 1) {
            return redirect()->to($log->url($log->lastPage()));
        }

        return view('superadm.vendor-portal.activity', [
            'log'     => $log,
            'vendors' => $this->vendors(),
            'actions' => VendorActivityLog::retained()->distinct()->orderBy('action')->pluck('action'),
            'summary' => [
                'total'  => (int) $byActor->sum(),
                'vendor' => (int) ($byActor['vendor'] ?? 0),
                'admin'  => (int) ($byActor['admin'] ?? 0),
                'today'  => (clone $base)->whereDate('created_at', now()->toDateString())->count(),
            ],
            'filtered' => collect($request->only(['vendor_id', 'actor', 'action', 'from_date', 'to_date', 'q']))->filter(fn ($v) => filled($v))->isNotEmpty(),
            'perPage'  => $perPage,
        ]);
    }

    /* ------------------------------ enquiries ------------------------------ */

    /**
     * Enquiries sent from every vendor's website Contact page. Read-only for
     * Brand Adda: the vendor works them and sets their status in its panel.
     */
    public function enquiries(Request $request)
    {
        $vendorId = (int) $request->vendor_id ?: null;
        $status   = array_key_exists((string) $request->status, \App\Models\VendorEnquiry::STATUSES) ? $request->status : null;
        $term     = trim((string) $request->q);

        // Columns named with their table: the list joins vendors, which has email / mobile too.
        $base = \App\Models\VendorEnquiry::query()
            ->when($vendorId, fn ($q) => $q->where('vendor_enquiries.vendor_id', $vendorId))
            ->when($term !== '', function ($q) use ($term) {
                $like = '%' . addcslashes($term, '\\%_') . '%';
                $q->where(function ($w) use ($like) {
                    foreach (['name', 'email', 'mobile', 'company', 'city', 'message'] as $col) {
                        $w->orWhere('vendor_enquiries.' . $col, 'like', $like);
                    }
                });
            });

        $perPage = in_array((int) $request->per_page, [10, 20, 50, 100], true) ? (int) $request->per_page : 20;

        $enquiries = (clone $base)
            ->when($status, fn ($q) => $q->where('vendor_enquiries.status', $status))
            ->with(['media:id,hoarding_code,media_code,media_title'])
            ->leftJoin('vendors as v', 'v.id', '=', 'vendor_enquiries.vendor_id')
            ->select('vendor_enquiries.*', 'v.vendor_name', 'v.slug as vendor_slug')
            ->latest('vendor_enquiries.created_at')
            ->paginate($perPage)
            ->withQueryString();

        $selected = $request->open
            ? \App\Models\VendorEnquiry::with('media')->leftJoin('vendors as v', 'v.id', '=', 'vendor_enquiries.vendor_id')
                ->select('vendor_enquiries.*', 'v.vendor_name')->find((int) $request->open)
            : null;

        return view('superadm.vendor-portal.enquiries', [
            'enquiries' => $enquiries,
            'selected'  => $selected,
            'status'    => $status,
            'vendorId'  => $vendorId,
            'counts'    => (clone $base)->selectRaw('vendor_enquiries.status, COUNT(*) as total')->groupBy('vendor_enquiries.status')->pluck('total', 'status'),
            'vendors'   => $this->vendors(),
            'perPage'   => $perPage,
        ]);
    }

    /* --------------------------- notifications ----------------------------- */

    public function notifications()
    {
        return view('superadm.vendor-portal.notifications', [
            'notifications' => PanelNotification::forAdmin()->with('vendor:id,vendor_name')->latest()->paginate(30),
            'unread'        => PanelNotification::forAdmin()->where('is_read', false)->count(),
        ]);
    }

    public function readAllNotifications()
    {
        PanelNotification::forAdmin()->where('is_read', false)->update(['is_read' => true]);

        return back()->with('success', 'All vendor notifications marked as read.');
    }

    public function openNotification(int $id)
    {
        $n = PanelNotification::forAdmin()->findOrFail($id);
        $n->update(['is_read' => true]);

        return redirect($n->link ?: route('admin.vendor.notifications'));
    }

    /* ----------------------------- credentials ----------------------------- */

    /**
     * Generate a password and email the vendor its login details (PRD 2.1.4).
     * Sending again issues a new password, which doubles as a reset.
     */
    public function sendCredentials(int $id)
    {
        $vendor = Vendor::where('is_deleted', 0)->findOrFail($id);

        if (!$vendor->email) {
            return back()->with('error', 'This vendor has no email address.');
        }

        $clash = Vendor::where('email', $vendor->email)->where('id', '!=', $vendor->id)->where('is_deleted', 0)->exists();
        if ($clash) {
            return back()->with('error', 'Another vendor uses the same email address. Give each vendor its own email before sending login details.');
        }

        $password = Str::password(10, true, true, false);

        try {
            Mail::to($vendor->email)->send(new VendorCredentialsMail($vendor, $password));
        } catch (\Throwable $e) {
            Log::error('Vendor credentials mail failed: ' . $e->getMessage());
            return back()->with('error', 'The email could not be sent, so the password was not changed. Check the mail settings.');
        }

        $vendor->forceFill(['password' => Hash::make($password), 'credentials_sent_at' => now()])->save();
        VendorAudit::adminDid($vendor->id, 'Login Details Sent', ['Email' => $vendor->email]);

        return back()->with('success', 'Login details emailed to ' . $vendor->email . '.');
    }
}

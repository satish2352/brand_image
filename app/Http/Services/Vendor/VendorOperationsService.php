<?php

namespace App\Http\Services\Vendor;

use App\Mail\OperationPhotosMail;
use App\Models\MediaOperation;
use App\Models\MediaOperationPhoto;
use App\Models\Vendor;
use App\Support\MediaAvailability;
use App\Support\VendorAudit;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Operations Module (PRD 2.8).
 *
 *   Pending Media → Installation → Monitoring Pics → End Date Pics → Completed
 *
 * Super Admin runs the workflow (uploads, shares, marks removal) for every
 * vendor; the vendor panel shows the same screens read-only. Methods take a
 * vendor id, or null for all vendors (Super Admin).
 *
 * "Pending Media" is approved inventory that is Available with no booking
 * coming up. Every booking of the vendor's media (a PAID website order or an
 * ADMIN_BOOKED order from the existing Admin Booking screen) becomes one
 * media_operations row, which then moves forward as photos are shared:
 *
 *  - Installation : until installation photos are shared with the user
 *  - Monitoring   : after that, until the booking end date
 *  - End Date     : from the end date until removal is confirmed
 *  - Completed    : removal recorded
 */
class VendorOperationsService
{
    public const PAID_STATUSES = ['PAID', 'ADMIN_BOOKED'];

    public const STAGE_ORDER = ['pending', 'installation', 'monitoring', 'end_date', 'completed'];

    public const STAGE_LABELS = [
        'pending'      => 'Pending Media',
        'installation' => 'Installation',
        'monitoring'   => 'Monitoring Pics',
        'end_date'     => 'End Date Pics',
        'completed'    => 'Completed / Media Remove',
    ];

    /** Create an operation for every booking of vendor media that has none yet (null = all vendors). */
    public function syncBookings(?int $vendorId = null): void
    {
        $missing = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('media_management as m', 'm.id', '=', 'oi.media_id')
            ->leftJoin('media_operations as op', 'op.order_item_id', '=', 'oi.id')
            ->when($vendorId, fn ($q) => $q->where('m.vendor_id', $vendorId), fn ($q) => $q->whereNotNull('m.vendor_id'))
            ->whereIn('o.payment_status', self::PAID_STATUSES)
            ->where('oi.is_deleted', 0)
            ->whereNull('op.id')
            ->get(['oi.id as order_item_id', 'oi.order_id', 'oi.media_id', 'oi.to_date', 'm.vendor_id']);

        foreach ($missing as $row) {
            // A booking that ended before this module existed has nothing left
            // to photograph; it starts out completed so it does not clutter the
            // Installation queue.
            $ended = Carbon::parse($row->to_date)->lt(now()->subDays(7)->startOfDay());

            MediaOperation::firstOrCreate(
                ['order_item_id' => $row->order_item_id],
                [
                    'vendor_id'    => $row->vendor_id,
                    'media_id'     => $row->media_id,
                    'order_id'     => $row->order_id,
                    'stage'        => $ended ? 'completed' : 'installation',
                    'completed_at' => $ended ? now() : null,
                ]
            );
        }

        // Bookings the vendor or admin entered as booked dates (Update Availability) have no
        // order. Those that do not mirror an order (website / Admin Booking orders write a
        // booked-date row with the same media and dates) get an operation of their own.
        $booked = DB::table('media_booked_date as b')
            ->join('media_management as m', 'm.id', '=', 'b.media_id')
            ->leftJoin('media_operations as op', 'op.booked_date_id', '=', 'b.id')
            ->when($vendorId, fn ($q) => $q->where('m.vendor_id', $vendorId), fn ($q) => $q->whereNotNull('m.vendor_id'))
            ->where('b.status_type', 'booked')
            ->where('b.is_active', 1)
            ->where('b.is_deleted', 0)
            ->whereNull('op.id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('order_items as oi')
                    ->whereColumn('oi.media_id', 'b.media_id')
                    ->whereColumn('oi.from_date', 'b.from_date')
                    ->whereColumn('oi.to_date', 'b.to_date')
                    ->where('oi.is_deleted', 0);
            })
            ->get(['b.id as booked_date_id', 'b.media_id', 'b.to_date', 'm.vendor_id']);

        foreach ($booked as $row) {
            $ended = Carbon::parse($row->to_date)->lt(now()->subDays(7)->startOfDay());

            MediaOperation::firstOrCreate(
                ['booked_date_id' => $row->booked_date_id],
                [
                    'vendor_id'    => $row->vendor_id,
                    'media_id'     => $row->media_id,
                    'stage'        => $ended ? 'completed' : 'installation',
                    'completed_at' => $ended ? now() : null,
                ]
            );
        }
    }

    /** Stage shown for an operation: monitoring rolls into end_date on the end date. */
    public function effectiveStage(object $op): string
    {
        if ($op->stage === 'monitoring' && Carbon::parse($op->to_date)->lte(now()->startOfDay())) {
            return 'end_date';
        }

        return $op->stage;
    }

    /** Operations with booking details, stage worked out (one vendor, or null = all vendors). */
    public function operations(?int $vendorId = null)
    {
        $this->syncBookings($vendorId);

        // An operation comes from an order (order_items) or from a vendor / admin booked-date row;
        // dates and the booking number fall back to the booked-date row when there is no order.
        return DB::table('media_operations as op')
            ->leftJoin('order_items as oi', 'oi.id', '=', 'op.order_item_id')
            ->leftJoin('orders as o', 'o.id', '=', 'op.order_id')
            ->leftJoin('media_booked_date as b', 'b.id', '=', 'op.booked_date_id')
            ->join('media_management as m', 'm.id', '=', 'op.media_id')
            ->leftJoin('website_users as u', 'u.id', '=', 'o.user_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('vendors as v', 'v.id', '=', 'op.vendor_id')
            ->when($vendorId, fn ($q) => $q->where('op.vendor_id', $vendorId))
            ->where(fn ($q) => $q->whereNotNull('oi.id')
                // A booked-date booking the vendor later removed is dropped, unless it already finished.
                ->orWhere(fn ($w) => $w->whereNotNull('b.id')->where(fn ($x) => $x->where('b.is_deleted', 0)->orWhere('op.stage', 'completed'))))
            ->orderByRaw('COALESCE(oi.from_date, b.from_date) DESC')
            ->get([
                'op.*', 'v.vendor_name',
                DB::raw("COALESCE(o.order_no, CONCAT('Booking #', b.id)) as order_no"),
                DB::raw('COALESCE(oi.from_date, b.from_date) as from_date'),
                DB::raw('COALESCE(oi.to_date, b.to_date) as to_date'),
                DB::raw("CASE WHEN op.booked_date_id IS NOT NULL THEN COALESCE(b.source, 'vendor') END as booked_by"),
                'm.media_title', 'm.hoarding_code', 'm.media_code', 'a.area_name',
                'u.name as user_name', 'u.email as user_email', 'u.mobile_number as user_mobile',
            ])
            ->map(function ($op) {
                $op->effective_stage = $this->effectiveStage($op);
                return $op;
            });
    }

    /** Approved, available vendor media with no booking from today on (null = all vendors). */
    /**
     * Pending Media query: approved, free media with no booking coming up. Built as a query so the
     * list can be paginated on the server and counted without loading every row.
     */
    public function pendingMediaQuery(?int $vendorId = null, ?string $term = null)
    {
        $today = now()->toDateString();
        $term  = trim((string) $term);

        return DB::table('media_management as m')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('cities as cty', 'cty.id', '=', 'm.city_id')
            ->leftJoin('vendors as v', 'v.id', '=', 'm.vendor_id')
            ->when($vendorId, fn ($q) => $q->where('m.vendor_id', $vendorId), fn ($q) => $q->whereNotNull('m.vendor_id'))
            ->where('m.is_deleted', 0)
            ->where('m.approval_status', 'approved')
            ->where('m.is_available', 1)
            ->whereNotExists(function ($q) use ($today) {
                $q->select(DB::raw(1))->from('media_booked_date as b')
                    ->whereColumn('b.media_id', 'm.id')
                    ->where('b.is_active', 1)->where('b.is_deleted', 0)
                    ->where('b.to_date', '>=', $today);
            })
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('m.hoarding_code', 'like', "%{$term}%")
                ->orWhere('m.media_code', 'like', "%{$term}%")
                ->orWhere('m.media_title', 'like', "%{$term}%")
                ->orWhere('a.area_name', 'like', "%{$term}%")
                ->orWhere('cty.city_name', 'like', "%{$term}%")
                ->orWhere('v.vendor_name', 'like', "%{$term}%")));
    }

    /** All pending media (kept for callers that need the full list). */
    public function pendingMedia(?int $vendorId = null)
    {
        return $this->pendingMediaQuery($vendorId)->orderBy('m.hoarding_code')
            ->get(['m.id', 'm.media_title', 'm.hoarding_code', 'm.media_code', 'a.area_name', 'cty.city_name', 'v.vendor_name']);
    }

    /** One page of pending media (server-side pagination), searchable with ?q=. */
    public function pendingMediaPage(?int $vendorId = null, ?string $term = null, int $perPage = 20)
    {
        return $this->pendingMediaQuery($vendorId, $term)->orderBy('m.hoarding_code')
            ->paginate($perPage, ['m.id', 'm.media_title', 'm.hoarding_code', 'm.media_code', 'a.area_name', 'cty.city_name', 'v.vendor_name'])
            ->withQueryString();
    }

    /** Count per stage for the stage tabs and the dashboard. */
    public function stageCounts(?int $vendorId = null): array
    {
        $counts = array_fill_keys(self::STAGE_ORDER, 0);
        $counts['pending'] = $this->pendingMediaQuery($vendorId)->count();

        foreach ($this->operations($vendorId) as $op) {
            $counts[$op->effective_stage]++;
        }

        return $counts;
    }

    public function findOwned(int $vendorId, int $operationId): MediaOperation
    {
        return MediaOperation::where('id', $operationId)->where('vendor_id', $vendorId)->firstOrFail();
    }

    /** The joined row for one operation (booking + media + user); null vendor = any vendor. */
    public function detail(?int $vendorId, int $operationId): ?object
    {
        return $this->operations($vendorId)->firstWhere('id', $operationId);
    }

    /** Photos are uploaded by Super Admin ($vendor null); the vendor id stays for older rows. */
    public function uploadPhoto(?Vendor $vendor, MediaOperation $op, string $stage, string $shot, ?string $date, UploadedFile $file): MediaOperationPhoto
    {
        $date = $stage === 'monitoring' ? Carbon::parse($date)->toDateString() : null;

        // One photo per shot (per requested date for monitoring): a new upload
        // replaces the old one, as long as it has not been shared yet.
        $existing = MediaOperationPhoto::where('operation_id', $op->id)
            ->where('stage', $stage)
            ->where('shot_type', $shot)
            ->when($date, fn ($q) => $q->whereDate('photo_date', $date), fn ($q) => $q->whereNull('photo_date'))
            ->first();

        if ($existing && $existing->shared_at) {
            throw ValidationException::withMessages(['photo' => 'This photo has already been shared with the user and cannot be replaced.']);
        }

        $path = $file->store('upload/operations/' . $op->id, 'public');

        if ($existing) {
            Storage::disk('public')->delete($existing->file);
            $existing->update(['file' => $path, 'uploaded_by_vendor_id' => $vendor?->id]);
            $photo = $existing;
        } else {
            $photo = MediaOperationPhoto::create([
                'operation_id'          => $op->id,
                'stage'                 => $stage,
                'shot_type'             => $shot,
                'photo_date'            => $date,
                'file'                  => $path,
                'uploaded_by_vendor_id' => $vendor?->id,
            ]);
        }

        return $photo;
    }

    /**
     * Email a stage's three shots to the booking user and move the operation
     * on. Returns the photos that were sent.
     *
     * A booking the vendor / admin entered as booked dates has no user to email:
     * its photos are recorded as done and the operation moves on all the same.
     */
    /**
     * $channel: 'email' mails the photos; 'whatsapp' only records the share (Brand Adda sends the
     * WhatsApp message from its own phone / WhatsApp Web). $to is the email / mobile actually used:
     * the booking user's by default, or any other person Brand Adda types in. Photos can be shared
     * again as often as needed (to more people, or the same person again).
     */
    public function share(Vendor $vendor, MediaOperation $op, string $stage, ?string $date, string $channel = 'email', ?string $to = null): array
    {
        $detail = $this->detail($vendor->id, $op->id);
        if (!$detail) {
            throw ValidationException::withMessages(['share' => 'Booking not found.']);
        }
        $noUser = empty($detail->user_email) && $op->booked_date_id;
        $to = trim((string) ($to ?: ($channel === 'whatsapp' ? $detail->user_mobile : $detail->user_email)));

        if ($channel === 'whatsapp' && $to === '' && !$noUser) {
            throw ValidationException::withMessages(['share' => 'Enter the mobile number to WhatsApp the photos to.']);
        }
        if ($channel === 'email' && $to === '' && !$noUser) {
            throw ValidationException::withMessages(['share' => 'Enter the email address to send the photos to.']);
        }

        $date = $stage === 'monitoring' ? Carbon::parse($date)->toDateString() : null;

        $photos = MediaOperationPhoto::where('operation_id', $op->id)
            ->where('stage', $stage)
            ->when($date, fn ($q) => $q->whereDate('photo_date', $date), fn ($q) => $q->whereNull('photo_date'))
            ->get()
            ->keyBy('shot_type');

        $missing = array_diff(array_keys(MediaOperationPhoto::SHOTS), $photos->keys()->all());
        if ($missing) {
            throw ValidationException::withMessages([
                'share' => 'Upload all three photos (Newspaper, Long and Close shot) before sharing.',
            ]);
        }

        if ($channel === 'email' && $to !== '') {
            Mail::to($to)->send(new OperationPhotosMail($vendor, $detail, $stage, $date, $photos->values()->all()));
        }

        MediaOperationPhoto::whereIn('id', $photos->pluck('id'))->update(['shared_at' => now()]);

        // Stage moves forward only: sharing older photos again (e.g. installation during
        // Monitoring, or anything once Completed) never sends a booking back.
        if ($stage === 'installation') {
            $op->update(['installation_shared_at' => now()] + ($op->stage === 'installation' ? ['stage' => 'monitoring'] : []));
        } elseif ($stage === 'end_date') {
            $op->update(['end_date_shared_at' => now()] + (in_array($op->stage, ['installation', 'monitoring'], true) ? ['stage' => 'end_date'] : []));
        }

        return $photos->values()->all();
    }

    public function complete(Vendor $vendor, MediaOperation $op, ?string $note): void
    {
        // Brand Adda can close a booking at any active stage (flex removed early, campaign cancelled…);
        // the board warns when the end-date photos were not shared.
        if ($op->stage === 'completed') {
            throw ValidationException::withMessages(['complete' => 'This booking is already completed.']);
        }

        $op->update([
            'stage'        => 'completed',
            'completed_at' => now(),
            'removal_note' => $note,
        ]);
    }

    /** Photos of one operation grouped as [stage][date-key][shot] => photo. */
    public function photosByStage(MediaOperation $op): array
    {
        $out = [];
        foreach ($op->photos()->orderBy('photo_date')->get() as $photo) {
            $key = $photo->photo_date ? $photo->photo_date->toDateString() : '_';
            $out[$photo->stage][$key][$photo->shot_type] = $photo;
        }

        return $out;
    }
}

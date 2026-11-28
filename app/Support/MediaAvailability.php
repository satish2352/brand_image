<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The four web statuses of PRD 2.3, worked out from data the platform already
 * keeps, so the website, the admin panel and the vendor panel agree:
 *
 *  - Booked   : today falls in a media_booked_date range of type 'booked'
 *               (orders, admin bookings, or a vendor's offline booking)
 *  - Blocked  : today falls in a range of type 'blocked' (vendor hold)
 *  - Ask for Availability : media_management.is_available = 0 — the existing
 *               admin "Not Available" switch, which keeps a site visible but
 *               not bookable
 *  - Available: none of the above (the default)
 *
 * Every range lives in media_booked_date, which the website's search already
 * reads to mark media as booked, so a vendor block reaches the website with no
 * extra wiring.
 */
class MediaAvailability
{
    public const AVAILABLE = 'available';
    public const BOOKED    = 'booked';
    public const BLOCKED   = 'blocked';
    public const ASK       = 'ask';

    public const LABELS = [
        self::AVAILABLE => 'Available',
        self::BOOKED    => 'Booked',
        self::BLOCKED   => 'Blocked',
        self::ASK       => 'Ask for Availability',
    ];

    /** Badge colours: [background, text]. */
    public const COLORS = [
        self::AVAILABLE => ['#DCFCE7', '#166534'],
        self::BOOKED    => ['#DBEAFE', '#1E40AF'],
        self::BLOCKED   => ['#FEE2E2', '#991B1B'],
        self::ASK       => ['#FEF3C7', '#92400E'],
    ];

    /**
     * Current status of each media id.
     *
     * @return array<int, array{status:string,label:string,from:?string,to:?string}>
     */
    public static function forMedia(array $mediaIds, ?Carbon $on = null): array
    {
        $mediaIds = array_values(array_unique(array_map('intval', $mediaIds)));
        if (!$mediaIds) {
            return [];
        }

        $day = ($on ?? now())->toDateString();

        $ranges = DB::table('media_booked_date')
            ->whereIn('media_id', $mediaIds)
            ->where('is_active', 1)
            ->where('is_deleted', 0)
            ->where('from_date', '<=', $day)
            ->where('to_date', '>=', $day)
            ->orderBy('to_date', 'desc')
            ->get()
            ->groupBy('media_id');

        $flags = DB::table('media_management')->whereIn('id', $mediaIds)->pluck('is_available', 'id');

        $out = [];
        foreach ($mediaIds as $id) {
            $range = optional($ranges->get($id))->first();

            if ($range) {
                $status = ($range->status_type ?? 'booked') === 'blocked' ? self::BLOCKED : self::BOOKED;
                $out[$id] = self::row($status, $range->from_date, $range->to_date);
            } elseif ((int) ($flags[$id] ?? 1) === 0) {
                $out[$id] = self::row(self::ASK);
            } else {
                $out[$id] = self::row(self::AVAILABLE);
            }
        }

        return $out;
    }

    /**
     * Media that must not be deleted: booked or blocked today or later (a vendor range, an admin
     * booking or a customer order that has not ended yet). Past bookings do not count.
     *
     * @return array<int, string> media id => why, e.g. "booked till 30 Nov 2026"
     */
    public static function deleteBlockers(array $mediaIds): array
    {
        $mediaIds = array_values(array_unique(array_map('intval', $mediaIds)));
        if (!$mediaIds) {
            return [];
        }
        $today = now()->toDateString();
        $out = [];

        $ranges = DB::table('media_booked_date')
            ->whereIn('media_id', $mediaIds)
            ->where('is_active', 1)
            ->where('is_deleted', 0)
            ->where('to_date', '>=', $today)
            ->orderBy('to_date', 'desc')
            ->get(['media_id', 'status_type', 'to_date']);
        foreach ($ranges->groupBy('media_id') as $id => $rows) {
            $last = $rows->first();
            $out[(int) $id] = (($last->status_type ?? 'booked') === 'blocked' ? 'blocked' : 'booked')
                . ' till ' . Carbon::parse($last->to_date)->format('d M Y');
        }

        $orders = DB::table('order_items')
            ->whereIn('media_id', array_diff($mediaIds, array_keys($out)))
            ->where('is_deleted', 0)
            ->where('to_date', '>=', $today)
            ->selectRaw('media_id, MAX(to_date) as to_date')
            ->groupBy('media_id')
            ->get();
        foreach ($orders as $row) {
            $out[(int) $row->media_id] = 'booked till ' . Carbon::parse($row->to_date)->format('d M Y');
        }

        return $out;
    }

    /** Active and future ranges of one media item, newest first. */
    public static function ranges(int $mediaId)
    {
        return DB::table('media_booked_date')
            ->where('media_id', $mediaId)
            ->where('is_active', 1)
            ->where('is_deleted', 0)
            ->where('to_date', '>=', now()->toDateString())
            ->orderBy('from_date')
            ->get();
    }

    /**
     * Apply a status chosen in the vendor panel.
     *
     * @return array{previous:string,current:string} labels, for the activity log
     */
    public static function apply(object $media, string $status, ?string $from, ?string $to, int $vendorId): array
    {
        $previous = self::forMedia([$media->id])[$media->id]['label'] ?? self::LABELS[self::AVAILABLE];

        DB::transaction(function () use ($media, $status, $from, $to, $vendorId) {
            switch ($status) {
                case self::AVAILABLE:
                    // Lift the vendor's own current holds; bookings from orders
                    // and the admin are not the vendor's to clear.
                    DB::table('media_booked_date')
                        ->where('media_id', $media->id)
                        ->where('source', 'vendor')
                        ->where('is_deleted', 0)
                        ->where('from_date', '<=', now()->toDateString())
                        ->where('to_date', '>=', now()->toDateString())
                        ->update(['is_deleted' => 1, 'is_active' => 0, 'updated_at' => now()]);
                    DB::table('media_management')->where('id', $media->id)->update(['is_available' => 1, 'updated_at' => now()]);
                    break;

                case self::ASK:
                    DB::table('media_management')->where('id', $media->id)->update(['is_available' => 0, 'updated_at' => now()]);
                    break;

                case self::BOOKED:
                case self::BLOCKED:
                    $fromDate = Carbon::parse($from)->toDateString();
                    $toDate   = Carbon::parse($to)->toDateString();

                    if (self::overlaps($media->id, $fromDate, $toDate)) {
                        throw ValidationException::withMessages([
                            'from_date' => 'These dates overlap an existing booked or blocked period.',
                        ]);
                    }

                    DB::table('media_booked_date')->insert([
                        'media_id'    => $media->id,
                        'from_date'   => $fromDate,
                        'to_date'     => $toDate,
                        'status_type' => $status,
                        'source'      => 'vendor',
                        'vendor_id'   => $vendorId,
                        'is_active'   => 1,
                        'is_deleted'  => 0,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                    DB::table('media_management')->where('id', $media->id)->update(['is_available' => 1, 'updated_at' => now()]);
                    break;
            }
        });

        $current = self::forMedia([$media->id])[$media->id]['label'];

        return ['previous' => $previous, 'current' => $current];
    }

    public static function overlaps(int $mediaId, string $from, string $to, ?int $ignoreId = null): bool
    {
        return DB::table('media_booked_date')
            ->where('media_id', $mediaId)
            ->where('is_active', 1)
            ->where('is_deleted', 0)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('from_date', '<=', $to)
            ->where('to_date', '>=', $from)
            ->exists();
    }

    private static function row(string $status, ?string $from = null, ?string $to = null): array
    {
        return [
            'status' => $status,
            'label'  => self::LABELS[$status],
            'from'   => $from,
            'to'     => $to,
            'bg'     => self::COLORS[$status][0],
            'fg'     => self::COLORS[$status][1],
        ];
    }
}

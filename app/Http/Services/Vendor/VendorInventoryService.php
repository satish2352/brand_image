<?php

namespace App\Http\Services\Vendor;

use App\Models\MediaManagement;
use App\Support\MediaAvailability;
use Illuminate\Support\Facades\DB;

/**
 * Inventory reads for the vendor panel. Every query starts from
 * `vendor_id = <signed-in vendor>`, which is what keeps one vendor out of
 * another's data (PRD 1.4).
 */
class VendorInventoryService
{
    public const APPROVAL_LABELS = [
        'approved' => 'Approved',
        'pending'  => 'Pending Approval',
        'rejected' => 'Rejected',
    ];

    /** The media row, only if it belongs to this vendor and is not deleted. */
    public function findOwned(int $vendorId, int $mediaId): MediaManagement
    {
        return MediaManagement::where('id', $mediaId)
            ->where('vendor_id', $vendorId)
            ->where('is_deleted', 0)
            ->firstOrFail();
    }

    public function query(int $vendorId, array $filters = [])
    {
        $query = DB::table('media_management as m')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('cities as cty', 'cty.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
            ->leftJoin('vendors as v', 'v.id', '=', 'm.vendor_id')
            ->select([
                'm.id', 'm.media_code', 'm.hoarding_code', 'm.media_title', 'm.price', 'm.width', 'm.height',
                'm.is_active', 'm.is_available', 'm.approval_status', 'm.rejection_reason', 'm.created_at',
                'v.vendor_name', 'c.category_name', 's.state_name', 'd.district_name', 'cty.city_name', 'a.area_name',
            ])
            ->where('m.vendor_id', $vendorId)
            ->where('m.is_deleted', 0);

        $this->applyFilters($query, $filters);

        return $query;
    }

    /** Same filter meanings as the admin Media List, so both panels agree. */
    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['category_id'])) {
            $query->where('m.category_id', (int) $filters['category_id']);
        }
        if (!empty($filters['district_id'])) {
            $query->where('m.district_id', (int) $filters['district_id']);
        }
        if (!empty($filters['city_id'])) {
            $query->where('m.city_id', (int) $filters['city_id']);
        }
        if (!empty($filters['month'])) {
            $query->whereMonth('m.created_at', (int) $filters['month']);
        }
        if (!empty($filters['year'])) {
            $query->whereYear('m.created_at', (int) $filters['year']);
        }
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->whereBetween(DB::raw('DATE(m.created_at)'), [$filters['from_date'], $filters['to_date']]);
        } elseif (!empty($filters['from_date'])) {
            $query->whereDate('m.created_at', '>=', $filters['from_date']);
        } elseif (!empty($filters['to_date'])) {
            $query->whereDate('m.created_at', '<=', $filters['to_date']);
        }
        if (trim((string) ($filters['sector_code'] ?? '')) !== '') {
            $code = trim($filters['sector_code']);
            $query->where(function ($q) use ($code) {
                $q->where('m.hoarding_code', 'like', '%' . addcslashes($code, '\\%_') . '%');
                $this->orCodeIgnoringZeros($q, $code);
            });
        }
        if (!empty($filters['approval']) && isset(self::APPROVAL_LABELS[$filters['approval']])) {
            $query->where('m.approval_status', $filters['approval']);
        }
        $raw = trim(preg_replace('/\s+/', ' ', (string) ($filters['q'] ?? '')));
        if ($raw !== '') {
            $words = array_slice(array_unique(explode(' ', $raw)), 0, 8);
            $query->where(function ($q) use ($raw, $words) {
                // The whole text in one field ("Mumbai Highway", "hd 42") ...
                $q->where(fn ($w) => $this->matchAnyField($w, $raw));
                // ... or every word somewhere, in any field ("Hoardings Nashik HD42").
                if (count($words) > 1) {
                    $q->orWhere(function ($all) use ($words) {
                        foreach ($words as $word) {
                            $all->where(fn ($w) => $this->matchAnyField($w, $word));
                        }
                    });
                }
            });
        }
    }

    /** Searchable columns: sector code, media code, title, address, category and location. */
    private const SEARCH_COLUMNS = [
        'm.hoarding_code', 'm.media_code', 'm.media_title', 'm.address',
        'c.category_name', 's.state_name', 'd.district_name', 'cty.city_name', 'a.area_name',
    ];

    private function matchAnyField($query, string $text): void
    {
        // Escape LIKE wildcards so "MAH_NAS" or "50%" match literally.
        $like = '%' . addcslashes($text, '\\%_') . '%';
        foreach (self::SEARCH_COLUMNS as $col) {
            $query->orWhere($col, 'like', $like);
        }
        $this->orCodeIgnoringZeros($query, $text);
    }

    /**
     * Hoarding codes are zero-padded (HD000042). Let "HD00042", "HD42", "hd 42"
     * or just "42" find it too: same letters, same number, any count of leading zeros.
     */
    private function orCodeIgnoringZeros($query, string $term): void
    {
        if (!preg_match('/^([A-Za-z]*)[\s\-]*0*(\d{1,9})$/', $term, $m)) {
            return;
        }
        // Only letters and digits reach the pattern, so it is safe to build.
        $pattern = '^' . ($m[1] !== '' ? $m[1] : '[A-Za-z]*') . '0*' . $m[2] . '$';
        $query->orWhereRaw('m.hoarding_code REGEXP ?', [$pattern]);
    }

    /** Sortable columns of the inventory table. */
    public const SORTS = [
        'code'     => 'm.hoarding_code',
        'title'    => 'm.media_title',
        'category' => 'c.category_name',
        'city'     => 'cty.city_name',
        'price'    => 'm.price',
        'created'  => 'm.id',
    ];

    public function paginate(int $vendorId, array $filters, int $perPage = 10)
    {
        $sort = self::SORTS[$filters['sort'] ?? 'created'] ?? 'm.id';
        $dir  = ($filters['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $page = $this->query($vendorId, $filters)->orderBy($sort, $dir)->paginate($perPage)->withQueryString();

        $statuses = MediaAvailability::forMedia($page->pluck('id')->all());
        $page->getCollection()->transform(function ($row) use ($statuses) {
            $row->availability = $statuses[$row->id] ?? null;
            return $row;
        });

        return $page;
    }

    /** Counts per approval state, for the list tabs and the dashboard. */
    public function approvalCounts(int $vendorId): array
    {
        $rows = DB::table('media_management')
            ->where('vendor_id', $vendorId)
            ->where('is_deleted', 0)
            ->selectRaw('approval_status, COUNT(*) as total')
            ->groupBy('approval_status')
            ->pluck('total', 'approval_status');

        return [
            'all'      => (int) $rows->sum(),
            'approved' => (int) ($rows['approved'] ?? 0),
            'pending'  => (int) ($rows['pending'] ?? 0),
            'rejected' => (int) ($rows['rejected'] ?? 0),
        ];
    }

    /** Dashboard tiles: approved inventory split by today's web status. */
    public function statusCounts(int $vendorId): array
    {
        $ids = DB::table('media_management')
            ->where('vendor_id', $vendorId)
            ->where('is_deleted', 0)
            ->where('approval_status', 'approved')
            ->pluck('id')
            ->all();

        $counts = array_fill_keys(array_keys(MediaAvailability::LABELS), 0);
        foreach (MediaAvailability::forMedia($ids) as $status) {
            $counts[$status['status']]++;
        }

        return ['total' => count($ids)] + $counts;
    }

    /**
     * Media that is booked or blocked today, soonest to come free first — the
     * dashboard's "Upcoming Availability".
     */
    public function upcomingAvailability(int $vendorId, int $limit = 6)
    {
        $today = now()->toDateString();

        return DB::table('media_booked_date as b')
            ->join('media_management as m', 'm.id', '=', 'b.media_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('cities as cty', 'cty.id', '=', 'm.city_id')
            ->where('m.vendor_id', $vendorId)
            ->where('m.is_deleted', 0)
            ->where('b.is_active', 1)
            ->where('b.is_deleted', 0)
            ->where('b.from_date', '<=', $today)
            ->where('b.to_date', '>=', $today)
            ->orderBy('b.to_date')
            ->limit($limit)
            ->get([
                'm.id', 'm.media_title', 'm.hoarding_code', 'm.media_code', 'a.area_name', 'cty.city_name',
                'b.status_type', 'b.from_date', 'b.to_date',
            ])
            ->map(function ($row) {
                $status = $row->status_type === 'blocked' ? MediaAvailability::BLOCKED : MediaAvailability::BOOKED;
                $row->label = MediaAvailability::LABELS[$status];
                [$row->bg, $row->fg] = MediaAvailability::COLORS[$status];
                $row->available_from = \Carbon\Carbon::parse($row->to_date)->addDay();
                return $row;
            });
    }
}

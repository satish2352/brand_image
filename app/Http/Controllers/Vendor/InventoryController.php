<?php

namespace App\Http\Controllers\Vendor;

use App\Exports\VendorBrandedExport;
use App\Http\Controllers\Controller;
use App\Http\Services\Vendor\VendorInventoryService;
use App\Models\Category;
use App\Support\MediaAvailability;
use App\Support\VendorAudit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Inventory List & Search (PRD 2.5), Availability & Status (PRD 2.3) and the
 * read-only Bookings list (PRD 2.7 — booking itself stays in Admin Booking).
 */
class InventoryController extends Controller
{
    private const FILTERS = ['category_id', 'district_id', 'city_id', 'year', 'month', 'from_date', 'to_date', 'sector_code', 'approval', 'q', 'sort', 'dir'];

    public function __construct(private VendorInventoryService $inventory)
    {
    }

    private function vendor()
    {
        return auth('vendor')->user();
    }

    private function filterOptions(int $vendorId): array
    {
        return [
            'categories' => Category::where('is_active', 1)->where('is_deleted', 0)->orderBy('category_name')->get(),
            // Only the districts / towns this vendor actually has media in.
            'districts'  => DB::table('districts as d')->join('media_management as m', 'm.district_id', '=', 'd.id')
                ->where('m.vendor_id', $vendorId)->where('m.is_deleted', 0)
                ->distinct()->orderBy('d.district_name')->get(['d.id', 'd.district_name']),
            'towns'      => DB::table('cities as c')->join('media_management as m', 'm.city_id', '=', 'c.id')
                ->where('m.vendor_id', $vendorId)->where('m.is_deleted', 0)
                ->distinct()->orderBy('c.city_name')->get(['c.id', 'c.city_name', 'c.district_id']),
            'years'      => getYears(),
            'months'     => getMonths(),
        ];
    }

    public function index(Request $request)
    {
        $vendor  = $this->vendor();
        $filters = $request->only(self::FILTERS);
        $perPage = in_array((int) $request->per_page, [10, 20, 50, 100], true) ? (int) $request->per_page : 10;

        $media = $this->inventory->paginate($vendor->id, $filters, $perPage);

        return view('vendor.inventory.index', $this->filterOptions($vendor->id) + [
            'filters' => $filters,
            'media'   => $media,
            'deleteBlockers' => MediaAvailability::deleteBlockers($media->pluck('id')->all()), // booked / blocked: no delete
            'counts'  => $this->inventory->approvalCounts($vendor->id),
            'perPage' => $perPage,
        ]);
    }

    public function export(Request $request)
    {
        $vendor = $this->vendor();
        $rows   = $this->inventory->query($vendor->id, $request->only(self::FILTERS))->orderBy('m.id', 'desc')->get();
        $status = MediaAvailability::forMedia($rows->pluck('id')->all());

        $data = $rows->values()->map(fn ($r, $i) => [
            $i + 1, $r->hoarding_code, $r->media_title, $r->category_name, $r->state_name, $r->district_name,
            $r->city_name, $r->area_name, (float) $r->price, $r->vendor_name, $r->media_code,
            $status[$r->id]['label'] ?? '', VendorInventoryService::APPROVAL_LABELS[$r->approval_status] ?? $r->approval_status,
            $r->is_active ? 'Active' : 'Inactive',
        ])->all();

        $headings = ['Sr. No.', 'Sector Code', 'Media Title', 'Category', 'State', 'District', 'City', 'Area',
            'Price / Month', 'Vendor Name', 'Media Code', 'Status', 'Approval', 'Active'];

        return Excel::download(
            new VendorBrandedExport($vendor, 'Media Management', $headings, $data),
            Str::slug($vendor->vendor_name) . '-media-management-' . now()->format('Ymd') . '.xlsx'
        );
    }

    /** Availability page: approved media with today's status and upcoming ranges. */
    public function availability(Request $request)
    {
        $vendor  = $this->vendor();
        $filters = $request->only(['q', 'category_id', 'status']) + ['approval' => 'approved'];

        // Server-side pagination: the status filter runs in SQL (same rule as
        // MediaAvailability::forMedia), so only one page of rows is read.
        $query = $this->inventory->query($vendor->id, $filters);
        if (!empty($filters['status']) && isset(MediaAvailability::LABELS[$filters['status']])) {
            $this->whereStatusToday($query, $filters['status']);
        }

        $perPage = in_array((int) $request->per_page, [10, 20, 50, 100], true) ? (int) $request->per_page : 10;
        $page = $query->orderBy('m.hoarding_code')->orderBy('m.id')->paginate($perPage)->withQueryString();
        if ($page->isEmpty() && $page->currentPage() > 1) {
            return redirect()->to($page->url($page->lastPage()));
        }

        $rows   = collect($page->items());
        $status = MediaAvailability::forMedia($rows->pluck('id')->all());

        $ranges = DB::table('media_booked_date')
            ->whereIn('media_id', $rows->pluck('id'))
            ->where('is_active', 1)->where('is_deleted', 0)
            ->where('to_date', '>=', now()->toDateString())
            ->orderBy('from_date')->get()->groupBy('media_id');

        $page->setCollection($rows->map(function ($r) use ($status, $ranges) {
            $r->availability = $status[$r->id];
            $r->ranges = $ranges->get($r->id, collect());
            return $r;
        }));

        return view('vendor.inventory.availability', [
            'rows'       => $page,
            'perPage'    => $perPage,
            'filters'    => $filters,
            'categories' => Category::where('is_active', 1)->where('is_deleted', 0)->orderBy('category_name')->get(),
        ]);
    }

    /**
     * Limit $query to media whose status today is $status. Mirrors MediaAvailability::forMedia:
     * the covering range with the latest end decides Booked / Blocked; with none, a media
     * flagged not-available is Ask for Availability, anything else is Available.
     */
    private function whereStatusToday($query, string $status): void
    {
        $today = now()->toDateString();
        $current = "(SELECT COALESCE(b.status_type, 'booked') FROM media_booked_date b
            WHERE b.media_id = m.id AND b.is_active = 1 AND b.is_deleted = 0 AND b.from_date <= ? AND b.to_date >= ?
            ORDER BY b.to_date DESC LIMIT 1)";

        match ($status) {
            MediaAvailability::BLOCKED   => $query->whereRaw("$current = 'blocked'", [$today, $today]),
            MediaAvailability::BOOKED    => $query->whereRaw("$current <> 'blocked'", [$today, $today]),
            MediaAvailability::ASK       => $query->whereRaw("$current IS NULL", [$today, $today])->where('m.is_available', 0),
            MediaAvailability::AVAILABLE => $query->whereRaw("$current IS NULL", [$today, $today])
                ->where(fn ($q) => $q->whereNull('m.is_available')->orWhere('m.is_available', '<>', 0)),
            default => null,
        };
    }

    /** Modal body for "Update Availability" (loaded over AJAX). */
    public function availabilityForm(int $id)
    {
        $media = $this->inventory->findOwned($this->vendor()->id, $id);

        return view('vendor.inventory.partials.availability-form', [
            'media'   => $media,
            'current' => MediaAvailability::forMedia([$id])[$id],
            'ranges'  => MediaAvailability::ranges($id),
        ]);
    }

    public function updateAvailability(Request $request, int $id)
    {
        $vendor = $this->vendor();
        $media  = $this->inventory->findOwned($vendor->id, $id);

        if ($media->approval_status !== 'approved') {
            return back()->with('error', 'Availability can be set after Brand Adda approves this media.');
        }

        $data = $request->validate([
            'status'    => 'required|in:available,booked,blocked,ask',
            'from_date' => 'required_if:status,booked,blocked|nullable|date',
            'to_date'   => 'required_if:status,booked,blocked|nullable|date|after_or_equal:from_date',
        ], [
            'from_date.required_if'  => 'From Date is required for Booked and Blocked.',
            'to_date.required_if'    => 'To Date is required for Booked and Blocked.',
            'to_date.after_or_equal' => 'To Date must be on or after From Date.',
        ]);

        $result = MediaAvailability::apply($media, $data['status'], $data['from_date'] ?? null, $data['to_date'] ?? null, $vendor->id);

        $details = ['Previous status' => $result['previous'], 'New status' => MediaAvailability::LABELS[$data['status']]];
        if (in_array($data['status'], ['booked', 'blocked'], true)) {
            $details['Dates'] = Carbon::parse($data['from_date'])->format('d M Y') . ' – ' . Carbon::parse($data['to_date'])->format('d M Y');
        }

        VendorAudit::vendorDid($vendor, 'Availability Updated', $details, $media);
        VendorAudit::notifyAdmin(
            'availability_updated',
            'Availability updated by ' . $vendor->vendor_name,
            $media->display_code . ': ' . $details['Previous status'] . ' → ' . $details['New status'] . (isset($details['Dates']) ? ' (' . $details['Dates'] . ')' : ''),
            route('admin.vendor.activity', ['vendor_id' => $vendor->id]),
            $vendor->id,
            $media->id
        );

        return back()->with('success', 'Availability updated for ' . $media->display_code . '.');
    }

    /** Remove a booked/blocked range the vendor set itself. */
    public function removeRange(int $id, int $rangeId)
    {
        $vendor = $this->vendor();
        $media  = $this->inventory->findOwned($vendor->id, $id);

        $range = DB::table('media_booked_date')
            ->where('id', $rangeId)->where('media_id', $id)->where('source', 'vendor')->where('is_deleted', 0)
            ->first();

        if (!$range) {
            return back()->with('error', 'Only dates you set yourself can be removed. Bookings are managed by Brand Adda.');
        }

        DB::table('media_booked_date')->where('id', $rangeId)->update(['is_deleted' => 1, 'is_active' => 0, 'updated_at' => now()]);

        $label = ucfirst($range->status_type) . ' ' . Carbon::parse($range->from_date)->format('d M Y') . ' – ' . Carbon::parse($range->to_date)->format('d M Y');
        VendorAudit::vendorDid($vendor, 'Availability Updated', ['Previous status' => $label, 'New status' => 'Removed'], $media);
        VendorAudit::notifyAdmin(
            'availability_updated',
            'Availability updated by ' . $vendor->vendor_name,
            $media->display_code . ': removed ' . $label,
            route('admin.vendor.activity', ['vendor_id' => $vendor->id]),
            $vendor->id,
            $media->id
        );

        return back()->with('success', 'Dates removed.');
    }

    /** Bookings on the vendor's media, read-only. */
    public function bookings(Request $request)
    {
        $vendor = $this->vendor();
        $term   = trim((string) $request->q);
        $status = in_array($request->status, ['running', 'upcoming', 'completed'], true) ? $request->status : '';
        $today  = now()->toDateString();

        // Running = today falls inside the booking; Upcoming = starts later; Completed = ended.
        $byStatus = function ($q, string $s) use ($today) {
            match ($s) {
                'running'   => $q->whereDate('oi.from_date', '<=', $today)->whereDate('oi.to_date', '>=', $today),
                'upcoming'  => $q->whereDate('oi.from_date', '>', $today),
                'completed' => $q->whereDate('oi.to_date', '<', $today),
                default     => $q,
            };
        };

        $base = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('media_management as m', 'm.id', '=', 'oi.media_id')
            ->leftJoin('website_users as u', 'u.id', '=', 'o.user_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->where('m.vendor_id', $vendor->id)
            ->whereIn('o.payment_status', ['PAID', 'ADMIN_BOOKED'])
            ->where('oi.is_deleted', 0)
            ->when($term !== '', function ($q) use ($term) {
                $like = '%' . $term . '%';
                $q->where(function ($w) use ($like) {
                    $w->where('o.order_no', 'like', $like)
                        ->orWhere('m.hoarding_code', 'like', $like)
                        ->orWhere('u.name', 'like', $like);
                });
            });

        // Tab counts follow the search, so each tab says how many it will show.
        $counts = ['all' => (clone $base)->count()];
        foreach (['running', 'upcoming', 'completed'] as $s) {
            $counts[$s] = tap(clone $base, fn ($q) => $byStatus($q, $s))->count();
        }

        $bookings = tap(clone $base, fn ($q) => $byStatus($q, $status))
            ->orderByDesc('oi.from_date')
            ->select([
                'o.order_no', 'o.payment_status', 'o.created_at', 'oi.from_date', 'oi.to_date',
                'm.id as media_id', 'm.media_title', 'm.hoarding_code', 'a.area_name',
                'u.name as user_name', 'u.email as user_email', 'u.mobile_number',
            ])
            ->paginate(15)
            ->withQueryString();

        return view('vendor.inventory.bookings', compact('bookings', 'status', 'counts'));
    }
}

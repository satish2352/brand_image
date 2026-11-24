<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Services\Vendor\VendorInventoryService;
use App\Http\Services\Vendor\VendorOperationsService;
use App\Models\PanelNotification;
use App\Models\VendorEnquiry;
use Illuminate\Support\Facades\DB;

/** Vendor Dashboard (PRD 2.4). */
class DashboardController extends Controller
{
    public function __construct(
        private VendorInventoryService $inventory,
        private VendorOperationsService $operations
    ) {
    }

    public function index()
    {
        $vendor = auth('vendor')->user();

        $latestRejected = DB::table('media_management')
            ->where('vendor_id', $vendor->id)
            ->where('is_deleted', 0)
            ->where('approval_status', 'rejected')
            ->orderByDesc('reviewed_at')
            ->first(['id', 'hoarding_code', 'media_code', 'media_title', 'rejection_reason']);

        // Bookings of this vendor's media (website orders and Admin Booking), by period.
        $today = now()->toDateString();
        $bookings = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('media_management as m', 'm.id', '=', 'oi.media_id')
            ->where('m.vendor_id', $vendor->id)
            ->whereIn('o.payment_status', ['PAID', 'ADMIN_BOOKED'])
            ->where('oi.is_deleted', 0);

        // Bar chart: the vendor's media by category (every status except deleted).
        $byCategory = DB::table('media_management as m')
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
            ->where('m.vendor_id', $vendor->id)
            ->where('m.is_deleted', 0)
            ->selectRaw("COALESCE(c.category_name, 'Other') as name, COUNT(*) as total")
            ->groupBy('name')
            ->orderByDesc('total')
            ->pluck('total', 'name');

        // Line chart: bookings (by start date) and enquiries (by date sent) over the last 6 months.
        $months = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $since  = $months->first()->toDateString();
        $bookingsByMonth = (clone $bookings)->whereDate('oi.from_date', '>=', $since)
            ->selectRaw("DATE_FORMAT(oi.from_date, '%Y-%m') as ym, COUNT(*) as total")->groupBy('ym')->pluck('total', 'ym');
        $enquiriesByMonth = VendorEnquiry::forVendor($vendor->id)->where('created_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total")->groupBy('ym')->pluck('total', 'ym');

        $charts = [
            'categories' => ['labels' => $byCategory->keys()->values(), 'data' => $byCategory->values()->map(fn ($v) => (int) $v)],
            'trend'      => [
                'labels'    => $months->map(fn ($m) => $m->format('M Y'))->values(),
                'bookings'  => $months->map(fn ($m) => (int) ($bookingsByMonth[$m->format('Y-m')] ?? 0))->values(),
                'enquiries' => $months->map(fn ($m) => (int) ($enquiriesByMonth[$m->format('Y-m')] ?? 0))->values(),
            ],
        ];

        return view('vendor.dashboard', [
            'charts'         => $charts,
            'bookingCounts'  => [
                'running'  => (clone $bookings)->whereDate('oi.from_date', '<=', $today)->whereDate('oi.to_date', '>=', $today)->count(),
                'upcoming' => (clone $bookings)->whereDate('oi.from_date', '>', $today)->count(),
            ],
            'enquiries'      => VendorEnquiry::forVendor($vendor->id)->with('media:id,hoarding_code,media_code,media_title')->latest()->limit(4)->get(),
            'newEnquiries'   => VendorEnquiry::forVendor($vendor->id)->whereNull('read_at')->count(),
            'vendor'         => $vendor,
            'status'         => $this->inventory->statusCounts($vendor->id),
            'approval'       => $this->inventory->approvalCounts($vendor->id),
            'upcoming'       => $this->inventory->upcomingAvailability($vendor->id),
            'stages'         => $this->operations->stageCounts($vendor->id),
            'stageLabels'    => VendorOperationsService::STAGE_LABELS,
            'latestRejected' => $latestRejected,
            'notifications'  => PanelNotification::forVendor($vendor->id)->latest()->limit(4)->get(),
        ]);
    }
}

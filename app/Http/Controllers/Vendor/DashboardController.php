<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Services\Vendor\VendorInventoryService;
use App\Http\Services\Vendor\VendorOperationsService;
use App\Models\PanelNotification;
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

        return view('vendor.dashboard', [
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

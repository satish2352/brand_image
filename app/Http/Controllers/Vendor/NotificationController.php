<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\PanelNotification;
use App\Models\VendorActivityLog;
use Illuminate\Http\Request;

/** Notifications & Activity Log for the vendor (PRD 2.11 A). */
class NotificationController extends Controller
{
    private function vendor()
    {
        return auth('vendor')->user();
    }

    public function index(Request $request)
    {
        $vendor = $this->vendor();

        $log = VendorActivityLog::retained()->where('vendor_id', $vendor->id)
            ->when($request->action, fn ($q, $a) => $q->where('action', $a))
            ->when($request->days, fn ($q, $d) => $q->where('created_at', '>=', now()->subDays((int) $d)))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('vendor.notifications.index', [
            'notifications' => PanelNotification::forVendor($vendor->id)->latest()->limit(50)->get(),
            'unread'        => PanelNotification::forVendor($vendor->id)->where('is_read', false)->count(),
            'log'           => $log,
            'actions'       => VendorActivityLog::retained()->where('vendor_id', $vendor->id)->distinct()->orderBy('action')->pluck('action'),
        ]);
    }

    public function readAll()
    {
        PanelNotification::forVendor($this->vendor()->id)->where('is_read', false)->update(['is_read' => true]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function open(int $id)
    {
        $n = PanelNotification::forVendor($this->vendor()->id)->findOrFail($id);
        $n->update(['is_read' => true]);

        return redirect($n->link ?: route('vendor.notifications'));
    }
}

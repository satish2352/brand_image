<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\PanelNotification;
use App\Models\VendorActivityLog;
use Illuminate\Http\Request;

/** Notifications and the Activity Log for the vendor (PRD 2.11 A), each on its own page. */
class NotificationController extends Controller
{
    private function vendor()
    {
        return auth('vendor')->user();
    }

    /** Notifications only: the bell and the sidebar open this page. */
    public function index(Request $request)
    {
        $vendor = $this->vendor();

        // Server-side pagination: 15 per page; a page past the end goes to the last page.
        $notifications = PanelNotification::forVendor($vendor->id)->latest()->latest('id')->paginate(15)->withQueryString();
        if ($notifications->isEmpty() && $notifications->currentPage() > 1) {
            return redirect()->to($notifications->url($notifications->lastPage()));
        }

        return view('vendor.notifications.index', [
            'notifications' => $notifications,
            'unread'        => PanelNotification::forVendor($vendor->id)->where('is_read', false)->count(),
        ]);
    }

    /** Activity Log: everything done on the vendor's account, kept for 6 months. */
    public function activity(Request $request)
    {
        $vendor = $this->vendor();

        $log = VendorActivityLog::retained()->where('vendor_id', $vendor->id)
            ->when($request->action, fn ($q, $a) => $q->where('action', $a))
            ->when($request->days, fn ($q, $d) => $q->where('created_at', '>=', now()->subDays((int) $d)))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('vendor.notifications.activity', [
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

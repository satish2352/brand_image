<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorEnquiry;
use App\Support\VendorAudit;
use Illuminate\Http\Request;

/** Enquiries sent from the vendor's website Contact page. */
class EnquiryController extends Controller
{
    private function vendor()
    {
        return auth('vendor')->user();
    }

    public function index(Request $request)
    {
        $vendor = $this->vendor();
        $status = array_key_exists((string) $request->status, VendorEnquiry::STATUSES) ? $request->status : null;
        $term   = trim((string) $request->q);

        $enquiries = VendorEnquiry::forVendor($vendor->id)
            ->with('media:id,hoarding_code,media_code,media_title')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($term !== '', function ($q) use ($term) {
                $like = '%' . $term . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)
                    ->orWhere('mobile', 'like', $like)->orWhere('company', 'like', $like)->orWhere('message', 'like', $like));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // The enquiry to show in the popup: only when asked for (View / ?open=ID).
        $selected = $request->open
            ? VendorEnquiry::forVendor($vendor->id)->with('media')->find((int) $request->open)
            : null;

        if ($selected && !$selected->read_at) {
            $selected->forceFill(['read_at' => now()])->save();
        }

        $counts = VendorEnquiry::forVendor($vendor->id)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('vendor.enquiries.index', [
            'enquiries' => $enquiries,
            'selected'  => $selected,
            'status'    => $status,
            'counts'    => $counts,
            'total'     => (int) $counts->sum(),
            'unread'    => VendorEnquiry::forVendor($vendor->id)->whereNull('read_at')->count(),
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $vendor  = $this->vendor();
        $enquiry = VendorEnquiry::forVendor($vendor->id)->findOrFail($id);

        $data = $request->validate(['status' => 'required|in:' . implode(',', array_keys(VendorEnquiry::STATUSES))]);

        if ($data['status'] !== $enquiry->status) {
            $previous = VendorEnquiry::STATUSES[$enquiry->status] ?? $enquiry->status;
            $enquiry->update(['status' => $data['status']]);
            VendorAudit::vendorDid($vendor, 'Enquiry Updated', [
                'From'   => $enquiry->name,
                'Status' => $previous . ' → ' . VendorEnquiry::STATUSES[$data['status']],
            ], $enquiry->media);
        }

        return redirect()->route('vendor.enquiries', array_filter([
            'open' => $enquiry->id, 'status' => $request->input('return_status'), 'q' => $request->input('return_q'),
        ]))->with('success', 'Enquiry marked as ' . strtolower(VendorEnquiry::STATUSES[$data['status']]) . '.');
    }
}

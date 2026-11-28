<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Services\Vendor\VendorOperationsService;
use App\Models\MediaOperationPhoto;
use App\Support\VendorAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Operations Module (PRD 2.8) — vendor side: read-only, except that the vendor can
 * share the uploaded photos with the user (WhatsApp / email).
 *
 * Brand Adda (Super Admin, Superadm\OperationsController) uploads the
 * installation / monitoring / end-date photos, shares them with the user and
 * records the removal. The vendor sees the same board and every photo here.
 */
class OperationsController extends Controller
{
    public function __construct(private VendorOperationsService $ops)
    {
    }

    public function index(Request $request)
    {
        $vendor = auth('vendor')->user();
        $stage  = in_array($request->stage, VendorOperationsService::STAGE_ORDER, true) ? $request->stage : null;

        $operations = $this->ops->operations($vendor->id);
        $counts = array_fill_keys(VendorOperationsService::STAGE_ORDER, 0);
        $counts['pending'] = $this->ops->pendingMediaQuery($vendor->id)->count();
        foreach ($operations as $op) {
            $counts[$op->effective_stage]++;
        }

        // Default tab: the first stage with work in it, after Pending Media.
        if (!$stage) {
            $stage = collect(['installation', 'monitoring', 'end_date'])->first(fn ($s) => $counts[$s] > 0) ?? 'installation';
        }

        if ($term = trim((string) $request->q)) {
            $operations = $operations->filter(fn ($op) => stripos(implode(' ', [
                $op->order_no, $op->hoarding_code, $op->media_code, $op->media_title, $op->area_name,
                $op->user_name, $op->user_mobile, $op->user_email, $op->vendor_name ?? '',
            ]), $term) !== false);
        }

        $all = $stage === 'pending' ? collect() : $operations->where('effective_stage', $stage)->values();

        // The list pages 10 at a time; a linked booking (?op=) opens even if it is on another page.
        $perPage = 10;
        $pageNo = max(1, (int) $request->query('page', 1));
        $list = new \Illuminate\Pagination\LengthAwarePaginator(
            $all->forPage($pageNo, $perPage)->values(), $all->count(), $perPage, $pageNo,
            ['path' => $request->url(), 'query' => $request->except('op')]
        );

        $selected = null;
        $photos = [];
        if ($stage !== 'pending') {
            // Only a booking that was clicked (?op=); without one the stage shows its bookings as a table.
            $selected = $request->op ? $all->firstWhere('id', (int) $request->op) : null;
            if ($selected) {
                $photos = $this->ops->photosByStage($this->ops->findOwned($vendor->id, $selected->id));
            }
        }

        return view('vendor.operations.index', [
            'stage'        => $stage,
            'stageLabels'  => VendorOperationsService::STAGE_LABELS,
            'counts'       => $counts,
            'list'         => $list,
            'pendingMedia' => $stage === 'pending' ? $this->ops->pendingMediaPage($vendor->id, $request->q) : collect(), // server-side pages
            'selected'     => $selected,
            'photos'       => $photos,
            'shots'        => MediaOperationPhoto::SHOTS,
            'monitorDate'  => $request->date ?: now()->toDateString(),
        ]);
    }

    /** Share a complete set of 3 photos with the user (or anyone else), by WhatsApp or email. */
    public function share(Request $request, int $operationId)
    {
        $vendor = auth('vendor')->user();
        $op     = $this->ops->findOwned($vendor->id, $operationId);

        $data = $request->validate([
            'stage'      => 'required|in:installation,monitoring,end_date',
            'photo_date' => 'required_if:stage,monitoring|nullable|date',
            'channel'    => 'required|in:email,whatsapp',
            'to_email'   => 'nullable|required_if:channel,email|email|max:255',
            'to_mobile'  => ['nullable', 'required_if:channel,whatsapp', 'regex:/^\+?[0-9 ()-]{10,18}$/'],
        ], [
            'to_email.required_if'  => 'Enter the email address to send the photos to.',
            'to_mobile.required_if' => 'Enter the mobile number to WhatsApp the photos to.',
            'to_mobile.regex'       => 'Enter a valid mobile number.',
        ]);
        $channel = $data['channel'];
        $to      = $channel === 'whatsapp' ? $data['to_mobile'] : $data['to_email'];

        try {
            $this->ops->share($vendor, $op, $data['stage'], $data['photo_date'] ?? null, $channel, $to);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Vendor photo share failed: ' . $e->getMessage());
            return back()->with('error', 'The email could not be sent. Please try again.');
        }

        $detail = $this->ops->detail($vendor->id, $op->id);
        VendorAudit::vendorDid($vendor, 'Photos Shared with User', [
            'Booking number' => $detail->order_no,
            'Stage'          => VendorOperationsService::STAGE_LABELS[$data['stage']],
            'Sent to'        => ($channel === 'whatsapp' ? 'WhatsApp ' : '') . $to,
        ], $op->media, $op->order_id);
        VendorAudit::notifyAdmin('photos_shared', 'Photos shared by ' . $vendor->vendor_name,
            VendorOperationsService::STAGE_LABELS[$data['stage']] . ' · ' . $detail->order_no . ' · ' . $op->media->display_code,
            route('admin.vendor.operations', ['vendor_id' => $vendor->id, 'stage' => $op->fresh()->stage, 'op' => $op->id]), $vendor->id, $op->media_id);

        return redirect()->route('vendor.operations', array_filter([
            'stage' => $this->ops->detail($vendor->id, $op->id)->effective_stage,
            'op'    => $op->id,
            'date'  => $data['stage'] === 'monitoring' ? ($data['photo_date'] ?? null) : null,
            'tab'   => $data['stage'],
        ]))->withFragment('ops-photos')
            ->with('ops_focus', true)
            ->with('success', $channel === 'whatsapp'
                ? 'Photos marked as shared on WhatsApp with ' . $to . '. Send the message in the WhatsApp tab that opened.'
                : 'Photos emailed to ' . $to . '.');
    }
}

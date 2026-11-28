<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use App\Http\Services\Vendor\VendorOperationsService;
use App\Models\MediaOperation;
use App\Models\MediaOperationPhoto;
use App\Models\Vendor;
use App\Support\VendorAudit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Operations Module (PRD 2.8) — run by Super Admin.
 *
 * Brand Adda uploads the installation / monitoring / end-date photos, shares
 * them with the booking user and records the media removal, for every vendor.
 * The vendor panel shows the same board read-only (Vendor\OperationsController).
 */
class OperationsController extends Controller
{
    private const STAGE_ACTIONS = [
        'installation' => 'Installation Photos Uploaded',
        'monitoring'   => 'Monitoring Photos Uploaded',
        'end_date'     => 'End-Date Photos Uploaded',
    ];

    public function __construct(private VendorOperationsService $ops)
    {
    }

    public function index(Request $request)
    {
        $vendorId = (int) $request->vendor_id ?: null;
        $stage    = in_array($request->stage, VendorOperationsService::STAGE_ORDER, true) ? $request->stage : null;

        $operations = $this->ops->operations($vendorId);
        $counts = array_fill_keys(VendorOperationsService::STAGE_ORDER, 0);
        $counts['pending'] = $this->ops->pendingMedia($vendorId)->count();
        foreach ($operations as $op) {
            $counts[$op->effective_stage]++;
        }

        // Default tab: the first stage with work in it, after Pending Media.
        if (!$stage) {
            $stage = collect(['installation', 'monitoring', 'end_date'])->first(fn ($s) => $counts[$s] > 0) ?? 'installation';
        }

        if ($term = trim((string) $request->q)) {
            $operations = $operations->filter(fn ($op) => stripos(
                $op->order_no . ' ' . $op->hoarding_code . ' ' . $op->media_title . ' ' . $op->user_name . ' ' . $op->vendor_name, $term
            ) !== false);
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
            // A linked booking (?op=) if it is in this stage, else the first one listed.
            $selected = ($request->op ? $all->firstWhere('id', (int) $request->op) : null) ?? $list->first();
            if ($selected) {
                $photos = $this->ops->photosByStage(MediaOperation::findOrFail($selected->id));
            }
        }

        return view('superadm.operations.index', [
            'stage'        => $stage,
            'stageLabels'  => VendorOperationsService::STAGE_LABELS,
            'counts'       => $counts,
            'list'         => $list,
            'pendingMedia' => $stage === 'pending' ? $this->ops->pendingMedia($vendorId) : collect(),
            'selected'     => $selected,
            'photos'       => $photos,
            'shots'        => MediaOperationPhoto::SHOTS,
            'monitorDate'  => $request->date ?: now()->toDateString(),
            'vendorId'     => $vendorId,
            'vendors'      => Vendor::where('is_deleted', 0)->orderBy('vendor_name')->get(['id', 'vendor_name']),
        ]);
    }

    public function uploadPhoto(Request $request, int $operationId)
    {
        $op = MediaOperation::findOrFail($operationId);

        $data = $request->validate([
            'stage'      => 'required|in:installation,monitoring,end_date',
            'shot_type'  => 'required|in:' . implode(',', array_keys(MediaOperationPhoto::SHOTS)),
            'photo_date' => 'required_if:stage,monitoring|nullable|date',
            'photo'      => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'photo.required'         => 'Choose a photo to upload.',
            'photo.max'              => 'Each photo must be under 5 MB.',
            'photo_date.required_if' => 'Pick the date the user requested monitoring photos for.',
        ]);

        if ($op->stage === 'completed') {
            return back()->with('error', 'This booking is completed; photos can no longer be changed.');
        }

        $this->ops->uploadPhoto(null, $op, $data['stage'], $data['shot_type'], $data['photo_date'] ?? null, $request->file('photo'));

        $media  = $op->media;
        $detail = $this->ops->detail($op->vendor_id, $op->id);
        $logDetails = ['Booking number' => $detail->order_no, 'Shot' => MediaOperationPhoto::SHOTS[$data['shot_type']]];
        if ($data['stage'] === 'monitoring') {
            $logDetails['Requested date'] = Carbon::parse($data['photo_date'])->format('d M Y');
        }

        VendorAudit::adminDid($op->vendor_id, self::STAGE_ACTIONS[$data['stage']], $logDetails, $media, $op->order_id);
        VendorAudit::notifyVendor($op->vendor_id, $data['stage'] . '_photos',
            VendorOperationsService::STAGE_LABELS[$data['stage']] . ' photo uploaded by Brand Adda',
            $detail->order_no . ' · ' . $media->display_code . ' · ' . MediaOperationPhoto::SHOTS[$data['shot_type']],
            route('vendor.operations', ['stage' => $data['stage'], 'op' => $op->id]), $media->id, $op->order_id);

        return redirect()->route('admin.vendor.operations', array_filter([
            'vendor_id' => $request->input('vendor_id'),
            'stage'     => $returnStage = $request->input('return_stage', $data['stage']),
            'op'        => $op->id,
            'date'      => $data['photo_date'] ?? null,
            'tab'       => $data['stage'] !== $returnStage ? $data['stage'] : null, // e.g. End Date photos added during Monitoring
        ]))->withFragment('ops-photos') // back to the Photos section, not the top of the page
            ->with('ops_focus', true)
            ->with('success', MediaOperationPhoto::SHOTS[$data['shot_type']] . ' uploaded.');
    }

    public function share(Request $request, int $operationId)
    {
        $op     = MediaOperation::findOrFail($operationId);
        $vendor = Vendor::findOrFail($op->vendor_id); // the mail goes out in the media owner's name

        $data = $request->validate([
            'stage'      => 'required|in:installation,monitoring,end_date',
            'photo_date' => 'required_if:stage,monitoring|nullable|date',
            'channel'    => 'nullable|in:email,whatsapp',
            // Any recipient: the booking user's details are pre-filled, Brand Adda may change them.
            'to_email'   => 'nullable|required_if:channel,email|email|max:255',
            'to_mobile'  => ['nullable', 'required_if:channel,whatsapp', 'regex:/^\+?[0-9 ()-]{10,18}$/'],
        ], [
            'to_email.required_if'  => 'Enter the email address to send the photos to.',
            'to_mobile.required_if' => 'Enter the mobile number to WhatsApp the photos to.',
            'to_mobile.regex'       => 'Enter a valid mobile number.',
        ]);
        $channel = $data['channel'] ?? 'email';
        $to = $channel === 'whatsapp' ? ($data['to_mobile'] ?? null) : ($data['to_email'] ?? null);

        try {
            $this->ops->share($vendor, $op, $data['stage'], $data['photo_date'] ?? null, $channel, $to);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Operation photo share failed: ' . $e->getMessage());
            return back()->with('error', 'The email could not be sent. Check the mail settings and try again.');
        }

        $detail   = $this->ops->detail($op->vendor_id, $op->id);
        $sentTo   = $to ?: ($channel === 'whatsapp' ? $detail->user_mobile : $detail->user_email);
        $whatsapp = $channel === 'whatsapp' && $sentTo;
        $emailed  = $channel === 'email' && $sentTo; // a vendor / admin booked-date booking may have no one to email
        $shared   = $whatsapp || $emailed;
        VendorAudit::adminDid($op->vendor_id, $shared ? 'Photos Shared with User' : 'Photos Recorded', [
            'Booking number' => $detail->order_no,
            'Stage'          => VendorOperationsService::STAGE_LABELS[$data['stage']],
            'Sent to'        => $whatsapp ? 'WhatsApp ' . $sentTo : ($emailed ? $sentTo : 'No user email (vendor booking)'),
        ], $op->media, $op->order_id);
        VendorAudit::notifyVendor($op->vendor_id, 'photos_shared',
            VendorOperationsService::STAGE_LABELS[$data['stage']] . ($shared ? ' photos shared with the user' : ' photos recorded by Brand Adda'),
            $detail->order_no . ' · ' . $op->media->display_code,
            route('vendor.operations', ['stage' => $op->fresh()->stage === 'monitoring' ? 'monitoring' : $data['stage'], 'op' => $op->id]),
            $op->media_id, $op->order_id);

        $next = $data['stage'] === 'installation' ? 'monitoring' : $data['stage'];

        return redirect()->route('admin.vendor.operations', array_filter([
            'vendor_id' => $request->input('vendor_id'),
            'stage'     => $op->fresh()->stage === 'monitoring' ? 'monitoring' : $next,
            'op'        => $op->id,
            'date'      => $data['stage'] === 'monitoring' ? ($data['photo_date'] ?? null) : null, // stay on the shared date
        ]))->withFragment('ops-photos')
            ->with('ops_focus', true)
            ->with('success', $whatsapp
                ? 'Photos marked as shared on WhatsApp with ' . $sentTo . '. Send the message in the WhatsApp tab that opened. You can share them again with anyone.'
                : ($emailed ? 'Photos emailed to ' . $sentTo . '. You can share them again with anyone.'
                : 'Photos saved and the booking moved on (this booking has no user email, so nothing was emailed).'));
    }

    public function complete(Request $request, int $operationId)
    {
        $op     = MediaOperation::findOrFail($operationId);
        $vendor = Vendor::findOrFail($op->vendor_id);

        $data = $request->validate([
            'confirm'      => 'accepted',
            'removal_note' => 'nullable|string|max:500',
        ], ['confirm.accepted' => 'Tick the box to confirm the flex / advertisement has been removed.']);
        $stageBefore = $this->ops->detail($op->vendor_id, $op->id)?->effective_stage ?? $op->stage;

        $this->ops->complete($vendor, $op, $data['removal_note'] ?? null);

        $detail = $this->ops->detail($op->vendor_id, $op->id);
        $media  = $op->media;
        VendorAudit::adminDid($op->vendor_id, 'Media Removal Completed', [
            'Booking number'  => $detail->order_no,
            'Completion date' => now()->format('d M Y, h:i A'),
            'Completed from'  => VendorOperationsService::STAGE_LABELS[$stageBefore] ?? $stageBefore,
        ] + ($data['removal_note'] ?? null ? ['Note' => $data['removal_note']] : []), $media, $op->order_id);
        VendorAudit::notifyVendor($op->vendor_id, 'media_removed', 'Media removal completed – ' . $media->display_code,
            $detail->order_no, route('vendor.operations', ['stage' => 'completed', 'op' => $op->id]), $media->id, $op->order_id);

        return redirect()->route('admin.vendor.operations', array_filter([
            'vendor_id' => $request->input('vendor_id'),
            'stage'     => 'completed',
            'op'        => $op->id,
        ]))->with('success', 'Marked as completed. The media is free again after its booking dates.');
    }
}

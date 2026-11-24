<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Services\Vendor\VendorOperationsService;
use App\Models\MediaOperationPhoto;
use App\Support\VendorAudit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/** Operations Module (PRD 2.8). */
class OperationsController extends Controller
{
    private const STAGE_ACTIONS = [
        'installation' => 'Installation Photos Uploaded',
        'monitoring'   => 'Monitoring Photos Uploaded',
        'end_date'     => 'End-Date Photos Uploaded',
    ];

    private const STAGE_NOTICE = [
        'installation' => ['installation_photos', 'Installation photos uploaded'],
        'monitoring'   => ['monitoring_photos', 'Monitoring photos uploaded'],
        'end_date'     => ['end_date_photos', 'End-date photos uploaded'],
    ];

    public function __construct(private VendorOperationsService $ops)
    {
    }

    private function vendor()
    {
        return auth('vendor')->user();
    }

    public function index(Request $request)
    {
        $vendor = $this->vendor();
        $stage  = in_array($request->stage, VendorOperationsService::STAGE_ORDER, true) ? $request->stage : null;

        $operations = $this->ops->operations($vendor->id);
        $counts = array_fill_keys(VendorOperationsService::STAGE_ORDER, 0);
        $counts['pending'] = $this->ops->pendingMedia($vendor->id)->count();
        foreach ($operations as $op) {
            $counts[$op->effective_stage]++;
        }

        // Default tab: the first stage with work in it, after Pending Media.
        if (!$stage) {
            $stage = collect(['installation', 'monitoring', 'end_date'])->first(fn ($s) => $counts[$s] > 0) ?? 'installation';
        }

        if ($term = trim((string) $request->q)) {
            $operations = $operations->filter(fn ($op) => stripos($op->order_no . ' ' . $op->hoarding_code . ' ' . $op->media_title . ' ' . $op->user_name, $term) !== false);
        }

        $list = $stage === 'pending' ? collect() : $operations->where('effective_stage', $stage)->values();

        $selected = null;
        $photos = [];
        if ($stage !== 'pending') {
            $selected = $request->op ? $list->firstWhere('id', (int) $request->op) : $list->first();
            if ($selected) {
                $photos = $this->ops->photosByStage($this->ops->findOwned($vendor->id, $selected->id));
            }
        }

        return view('vendor.operations.index', [
            'stage'        => $stage,
            'stageLabels'  => VendorOperationsService::STAGE_LABELS,
            'counts'       => $counts,
            'list'         => $list,
            'pendingMedia' => $stage === 'pending' ? $this->ops->pendingMedia($vendor->id) : collect(),
            'selected'     => $selected,
            'photos'       => $photos,
            'shots'        => MediaOperationPhoto::SHOTS,
            'monitorDate'  => $request->date ?: now()->toDateString(),
        ]);
    }

    public function uploadPhoto(Request $request, int $operationId)
    {
        $vendor = $this->vendor();
        $op     = $this->ops->findOwned($vendor->id, $operationId);

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

        $this->ops->uploadPhoto($vendor, $op, $data['stage'], $data['shot_type'], $data['photo_date'] ?? null, $request->file('photo'));

        $media = $op->media;
        $detail = $this->ops->detail($vendor->id, $op->id);
        $logDetails = ['Booking number' => $detail->order_no, 'Shot' => MediaOperationPhoto::SHOTS[$data['shot_type']]];
        if ($data['stage'] === 'monitoring') {
            $logDetails['Requested date'] = Carbon::parse($data['photo_date'])->format('d M Y');
        }

        VendorAudit::vendorDid($vendor, self::STAGE_ACTIONS[$data['stage']], $logDetails, $media, $op->order_id);
        [$type, $title] = self::STAGE_NOTICE[$data['stage']];
        VendorAudit::notifyAdmin($type, $title . ' by ' . $vendor->vendor_name,
            $detail->order_no . ' · ' . $media->display_code . ' · ' . MediaOperationPhoto::SHOTS[$data['shot_type']],
            route('admin.vendor.activity', ['vendor_id' => $vendor->id]), $vendor->id, $media->id, $op->order_id);

        return redirect()->route('vendor.operations', ['stage' => $request->input('return_stage', $data['stage']), 'op' => $op->id, 'date' => $data['photo_date'] ?? null])
            ->with('success', MediaOperationPhoto::SHOTS[$data['shot_type']] . ' uploaded.');
    }

    public function share(Request $request, int $operationId)
    {
        $vendor = $this->vendor();
        $op     = $this->ops->findOwned($vendor->id, $operationId);

        $data = $request->validate([
            'stage'      => 'required|in:installation,monitoring,end_date',
            'photo_date' => 'required_if:stage,monitoring|nullable|date',
        ]);

        try {
            $this->ops->share($vendor, $op, $data['stage'], $data['photo_date'] ?? null);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Operation photo share failed: ' . $e->getMessage());
            return back()->with('error', 'The email could not be sent. Check the mail settings and try again.');
        }

        $detail = $this->ops->detail($vendor->id, $op->id);
        VendorAudit::vendorDid($vendor, 'Photos Shared with User', [
            'Booking number' => $detail->order_no,
            'Stage'          => VendorOperationsService::STAGE_LABELS[$data['stage']],
            'Sent to'        => $detail->user_email,
        ], $op->media, $op->order_id);

        $next = $data['stage'] === 'installation' ? 'monitoring' : $data['stage'];

        return redirect()->route('vendor.operations', ['stage' => $op->fresh()->stage === 'monitoring' ? 'monitoring' : $next, 'op' => $op->id])
            ->with('success', 'Photos emailed to ' . $detail->user_email . '.');
    }

    public function complete(Request $request, int $operationId)
    {
        $vendor = $this->vendor();
        $op     = $this->ops->findOwned($vendor->id, $operationId);

        $data = $request->validate([
            'confirm'      => 'accepted',
            'removal_note' => 'nullable|string|max:500',
        ], ['confirm.accepted' => 'Confirm that the photos were shared and the flex has been removed.']);

        $this->ops->complete($vendor, $op, $data['removal_note'] ?? null);

        $detail = $this->ops->detail($vendor->id, $op->id);
        $media  = $op->media;
        VendorAudit::vendorDid($vendor, 'Media Removal Completed', [
            'Booking number'  => $detail->order_no,
            'Completion date' => now()->format('d M Y, h:i A'),
        ], $media, $op->order_id);
        VendorAudit::notifyAdmin('media_removed', 'Media removal completed by ' . $vendor->vendor_name,
            $detail->order_no . ' · ' . $media->display_code, route('admin.vendor.activity', ['vendor_id' => $vendor->id]), $vendor->id, $media->id, $op->order_id);

        return redirect()->route('vendor.operations', ['stage' => 'completed', 'op' => $op->id])
            ->with('success', 'Marked as completed. The media is free again after its booking dates.');
    }
}

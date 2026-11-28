<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Services\Vendor\VendorOperationsService;
use App\Models\MediaOperationPhoto;
use Illuminate\Http\Request;

/**
 * Operations Module (PRD 2.8) — vendor side, read-only.
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
            $operations = $operations->filter(fn ($op) => stripos($op->order_no . ' ' . $op->hoarding_code . ' ' . $op->media_title . ' ' . $op->user_name, $term) !== false);
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
}

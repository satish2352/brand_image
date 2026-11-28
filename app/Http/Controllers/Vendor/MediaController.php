<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Superadm\MediaManagementController;
use App\Http\Services\Superadm\MediaManagementService;
use App\Http\Services\Vendor\VendorInventoryService;
use App\Models\{AreaType, Category, FacingDirection, Highway, Illumination, Landmark, MediaImage, MediaLocationSize};
use App\Support\MediaAvailability;
use App\Support\VendorAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Vendor Inventory Management (PRD 2.2): add, edit, view, delete and
 * activate/deactivate the vendor's own media.
 *
 * The form IS the admin Media Management form (same Blade views, same
 * validation via MediaManagementController::validationFor, same save logic in
 * MediaManagementService). The vendor field is replaced by the signed-in
 * vendor, and vendor_id is overwritten server-side on every write.
 *
 * New media is saved as 'pending' with is_active = 0, which keeps it off the
 * website and out of booking until a Super Admin approves it.
 */
class MediaController extends Controller
{
    /** Fields compared for "Media Updated — changed fields". */
    private const TRACKED = [
        'media_title' => 'Media Title', 'category_id' => 'Category', 'state_id' => 'State',
        'district_id' => 'District', 'city_id' => 'City', 'area_id' => 'Area', 'address' => 'Address',
        'width' => 'Width', 'height' => 'Height', 'illumination_id' => 'Illumination', 'facing' => 'Facing',
        'latitude' => 'Latitude', 'longitude' => 'Longitude', 'price' => 'Price', 'areatype_id' => 'Area Type',
        'highway_id' => 'Highway', 'area_auto' => 'Area (sq.ft)', 'mall_name' => 'Mall Name',
        'media_format' => 'Media Format', 'airport_name' => 'Airport Name', 'zone_type' => 'Zone',
        'media_type' => 'Media Type', 'transit_type' => 'Transit Type', 'branding_type' => 'Branding Type',
        'vehicle_count' => 'Vehicle Count', 'building_name' => 'Building Name', 'wall_length' => 'Wall Length',
    ];

    public function __construct(
        private MediaManagementService $mediaService,
        private VendorInventoryService $inventory
    ) {
    }

    private function vendor()
    {
        return auth('vendor')->user();
    }

    private function formData(): array
    {
        $active = fn ($model, $order = null) => $model::where('is_active', 1)->where('is_deleted', 0)
            ->when($order, fn ($q) => $q->orderBy($order))->get();

        return [
            'categories'    => $active(Category::class),
            'facings'       => $active(FacingDirection::class),
            'illuminations' => $active(Illumination::class),
            'vendors'       => collect(),
            'areatype'      => $active(AreaType::class, 'areatype_name'),
            'highways'      => $active(Highway::class, 'highway_name'),
            'landmarks'     => $active(Landmark::class, 'landmark_name'),
            'lockedVendor'  => $this->vendor(),
            'formLayout'    => 'vendor.media.form-layout',
            'nextCodeUrl'   => route('vendor.media.next-code'),
            'cancelUrl'     => route('vendor.inventory'),
        ];
    }

    private function slugFor(Request $request): string
    {
        $category = Category::findOrFail($request->category_id);

        return Str::slug($category->slug ?? $category->category_name);
    }

    public function create()
    {
        return view('superadm.mediamanagement.create', $this->formData() + [
            'formTitle'   => 'Add Media',
            'formHeading' => 'Add Media',
            'formIntro'   => 'vendor.media.partials.new-media-intro',
            'formAction'  => route('vendor.media.store'),
            'submitLabel' => 'Submit for Approval',
        ]);
    }

    public function store(Request $request)
    {
        $vendor = $this->vendor();
        $request->merge(['vendor_id' => $vendor->id]);

        $slug = $this->slugFor($request);
        [$rules, $messages] = MediaManagementController::validationFor($request, $slug, false);
        $request->validate($rules, $messages);

        try {
            $media = $this->mediaService->store($request, $slug);

            $media->forceFill([
                'is_active'              => 0,
                'approval_status'        => 'pending',
                'rejection_reason'       => null,
                'submitted_by_vendor_id' => $vendor->id,
                'submitted_at'           => now(),
            ])->save();
        } catch (\Throwable $e) {
            Log::error($e);
            return back()->withInput()->with('error', 'The media could not be saved. Please try again.');
        }

        $code = VendorAudit::codeOf($media);
        VendorAudit::vendorDid($vendor, 'Media Added', ['Vendor' => $vendor->vendor_name], $media);
        VendorAudit::vendorDid($vendor, 'Media Submitted for Approval', ['Submission status' => 'Pending Approval'], $media);
        VendorAudit::notifyAdmin('media_added', 'Media added by ' . $vendor->vendor_name,
            $code . ' · ' . $media->media_title, route('admin.vendor.approvals.show', $media->id), $vendor->id, $media->id);
        VendorAudit::notifyAdmin('media_submitted', 'New media submitted for approval',
            $vendor->vendor_name . ' submitted ' . $code . ' · ' . $media->media_title . '.', route('admin.vendor.approvals.show', $media->id), $vendor->id, $media->id);

        // Back to the Media Management list, where the new media shows as Pending Approval.
        return redirect()->route('vendor.inventory')
            ->with('success', $code . ' submitted for approval. It will go live after Brand Adda approves it.');
    }

    public function show(int $id)
    {
        $media = $this->inventory->findOwned($this->vendor()->id, $id);

        $details = DB::table('media_management as m')
            ->leftJoin('category as c', 'c.id', '=', 'm.category_id')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('cities as cty', 'cty.id', '=', 'm.city_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('illuminations as i', 'i.id', '=', 'm.illumination_id')
            ->leftJoin('areatype as at', 'at.id', '=', 'm.areatype_id')
            ->leftJoin('highway as h', 'h.id', '=', 'm.highway_id')
            ->where('m.id', $id)
            ->first([
                'm.*', 'c.category_name', 's.state_name', 'd.district_name', 'cty.city_name', 'a.area_name',
                'i.illumination_name', 'at.areatype_name', 'h.highway_name',
            ]);

        return view('vendor.media.show', [
            'media'        => $media,
            'details'      => $details,
            'images'       => MediaImage::where('media_id', $id)->where('is_deleted', 0)->get(),
            'sizes'        => MediaLocationSize::where('media_id', $id)->get(),
            'availability' => MediaAvailability::forMedia([$id])[$id],
            'ranges'       => MediaAvailability::ranges($id),
            'history'      => \App\Models\VendorActivityLog::retained()->where('media_id', $id)->latest('created_at')->limit(10)->get(),
            'imageBase'    => config('fileConstants.IMAGE_VIEW'),
        ]);
    }

    public function edit(int $id)
    {
        $vendor = $this->vendor();
        $owned  = $this->inventory->findOwned($vendor->id, $id);
        $media  = DB::table('media_management')->where('id', $id)->first();

        $rejected = $owned->approval_status === 'rejected';

        return view('superadm.mediamanagement.edit', $this->formData() + [
            'media'             => $media,
            'encodedId'         => base64_encode($id),
            'selectedLandmarks' => DB::table('media_landmark')->where('media_id', $id)->pluck('landmark_id')->toArray(),
            'locationSizes'     => MediaLocationSize::where('media_id', $id)->get()->keyBy('position'),
            'formTitle'         => 'Edit Media · ' . $owned->display_code,
            'formHeading'       => 'Edit Media',
            'formIntro'         => 'vendor.media.partials.approval-intro',
            'approvalMedia'     => $owned,
            'approvalHistory'   => \App\Models\VendorActivityLog::retained()->where('media_id', $id)
                ->whereIn('action', ['Media Submitted for Approval', 'Media Approved', 'Media Rejected'])
                ->orderBy('created_at')->get(),
            'formAction'        => route('vendor.media.update', $id),
            'cancelUrl'         => route('vendor.media.show', $id),
            'submitLabel'       => $rejected ? 'Resubmit for Approval' : 'Save Changes',
            'extraSubmit'       => $rejected ? ['save_only', 'Save Changes'] : null,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $vendor = $this->vendor();
        $media  = $this->inventory->findOwned($vendor->id, $id);

        // A vendor cannot move media to another vendor, nor rename its site code.
        $request->merge(['vendor_id' => $vendor->id, 'hoarding_code' => $media->hoarding_code]);

        $slug = $this->slugFor($request);
        [$rules, $messages] = MediaManagementController::validationFor($request, $slug, true);
        $request->validate($rules, $messages);

        $before = $media->getAttributes();

        try {
            $this->mediaService->update($id, $request, $slug);
        } catch (\Throwable $e) {
            Log::error($e);
            return back()->withInput()->with('error', 'The changes could not be saved. Please try again.');
        }

        $media->refresh();
        $changes = VendorAudit::diff($before, $media->getAttributes(), self::TRACKED);
        $code = $media->display_code;

        if ($changes) {
            VendorAudit::vendorDid($vendor, 'Media Updated', ['Changed fields' => implode(', ', array_keys($changes))], $media);
            VendorAudit::notifyAdmin('media_updated', 'Media updated by ' . $vendor->vendor_name,
                $code . ': ' . implode(', ', array_keys($changes)), route('admin.vendor.approvals.show', $id), $vendor->id, $id);
        }

        if (isset($changes['Price'])) {
            [$oldRate, $newRate] = $changes['Price'];
            VendorAudit::vendorDid($vendor, 'Media Rate Updated', ['Previous rate' => $oldRate, 'New rate' => $newRate], $media);
            VendorAudit::notifyAdmin('rate_updated', 'Rate updated by ' . $vendor->vendor_name,
                $code . ': ₹' . $oldRate . ' → ₹' . $newRate, route('admin.vendor.approvals.show', $id), $vendor->id, $id);
        }

        if ($media->approval_status === 'rejected' && !$request->boolean('save_only')) {
            $media->forceFill([
                'approval_status' => 'pending',
                'submitted_at'    => now(),
                'is_active'       => 0,
            ])->save();

            VendorAudit::vendorDid($vendor, 'Media Submitted for Approval', ['Submission status' => 'Resubmitted – Pending Approval'], $media);
            VendorAudit::notifyAdmin('media_submitted', 'Media resubmitted for approval',
                $vendor->vendor_name . ' corrected and resubmitted ' . $code . '.', route('admin.vendor.approvals.show', $id), $vendor->id, $id);

            // Submitted for approval again: back to the list, like a new submission.
            return redirect()->route('vendor.inventory')->with('success', $code . ' resubmitted for approval.');
        }

        return redirect()->route('vendor.media.show', $id)->with('success', 'Media updated.');
    }

    public function destroy(int $id)
    {
        $vendor = $this->vendor();
        $media  = $this->inventory->findOwned($vendor->id, $id);

        // Booked or blocked media (today or later) cannot be deleted.
        if ($why = \App\Support\MediaAvailability::deleteBlockers([$id])[$id] ?? null) {
            return back()->with('error', $media->display_code . ' is ' . $why . ', so it cannot be deleted. Remove the booking / block first, or delete it after that date.');
        }

        try {
            $this->mediaService->delete($id);
        } catch (\Throwable $e) {
            Log::error($e);
            return back()->with('error', 'The media could not be deleted.');
        }

        VendorAudit::vendorDid($vendor, 'Media Deleted', [], $media);
        VendorAudit::notifyAdmin('media_deleted', 'Media deleted by ' . $vendor->vendor_name,
            $media->display_code . ' · ' . $media->media_title, route('admin.vendor.activity', ['vendor_id' => $vendor->id]), $vendor->id, $id);

        return redirect()->route('vendor.inventory')->with('success', 'Media deleted.');
    }

    /** Activate / deactivate — only for approved media (PRD 2.2.5 + 2.2.7). */
    public function toggle(int $id)
    {
        $vendor = $this->vendor();
        $media  = $this->inventory->findOwned($vendor->id, $id);

        if ($media->approval_status !== 'approved') {
            return back()->with('error', 'Media can be activated only after Brand Adda approves it.');
        }

        $media->forceFill(['is_active' => $media->is_active ? 0 : 1])->save();
        $state = $media->is_active ? 'Activated' : 'Deactivated';

        VendorAudit::vendorDid($vendor, 'Media Updated', ['Changed fields' => 'Status → ' . $state], $media);
        VendorAudit::notifyAdmin('media_updated', 'Media ' . strtolower($state) . ' by ' . $vendor->vendor_name,
            $media->display_code . ' · ' . $media->media_title, route('admin.vendor.approvals.show', $id), $vendor->id, $id);

        return back()->with('success', 'Media ' . strtolower($state) . '.');
    }

    public function uploadImages(Request $request, int $id)
    {
        $vendor = $this->vendor();
        $media  = $this->inventory->findOwned($vendor->id, $id);

        $request->validate([
            'images'   => 'required|array',
            'images.*' => 'image|mimes:webp,jpg,jpeg,png|max:600',
        ], [
            'images.required' => 'Choose at least one photo.',
            'images.*.image'  => 'Each file must be an image.',
            'images.*.mimes'  => 'Only WebP, JPG, JPEG, and PNG images are allowed.',
            'images.*.max'    => 'Each image must be less than 600 KB.',
        ]);

        $existing = MediaImage::where('media_id', $id)->where('is_deleted', 0)->count();
        if ($existing + count($request->file('images')) > 10) {
            return back()->withErrors(['images' => 'You can upload a maximum of 10 photos per media.']);
        }

        foreach ($request->file('images') as $image) {
            MediaImage::create([
                'media_id'   => $id,
                'images'     => uploadImage($image, config('fileConstants.IMAGE_ADD')),
                'is_active'  => 1,
                'is_deleted' => 0,
            ]);
        }

        VendorAudit::vendorDid($vendor, 'Media Updated', ['Changed fields' => 'Photos (' . count($request->file('images')) . ' added)'], $media);
        VendorAudit::notifyAdmin('media_updated', 'Photos updated by ' . $vendor->vendor_name,
            $media->display_code . ': ' . count($request->file('images')) . ' photo(s) added', route('admin.vendor.approvals.show', $id), $vendor->id, $id);

        return back()->with('success', 'Photos uploaded.');
    }

    public function deleteImage(int $id, int $imageId)
    {
        $vendor = $this->vendor();
        $media  = $this->inventory->findOwned($vendor->id, $id);

        $image = MediaImage::where('id', $imageId)->where('media_id', $id)->where('is_deleted', 0)->firstOrFail();
        removeImage($image->images, config('fileConstants.IMAGE_DELETE'));
        $image->update(['is_active' => 0, 'is_deleted' => 1]);

        VendorAudit::vendorDid($vendor, 'Media Updated', ['Changed fields' => 'Photos (1 removed)'], $media);

        return back()->with('success', 'Photo removed.');
    }

    /** Media code suggestion for the shared form, for this vendor only. */
    public function nextCode(Request $request)
    {
        $request->merge(['vendor_id' => $this->vendor()->id]);

        return app(MediaManagementController::class)->getNextMediaCode($request);
    }
}

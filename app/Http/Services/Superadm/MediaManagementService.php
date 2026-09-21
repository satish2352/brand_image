<?php

namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\MediaManagementRepository;
use Illuminate\Support\Facades\DB;
use App\Support\MediaCode;
use Illuminate\Http\Request;
use App\Models\MediaImage;
use App\Models\MediaLocationSize;
use Illuminate\Support\Facades\Storage;

class MediaManagementService
{
    protected $repo;

    public function __construct(MediaManagementRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getAll($filters = [])
    {
        return $this->repo->getAll($filters);
    }

    public function store(Request $request, string $slug)
    {
        DB::beginTransaction();

        try {
            $mediaData = $request->only([
                'state_id',
                'district_id',
                'city_id',
                'area_id',
                'category_id',
                // 'media_code',
                'media_title',
                'address',
                'width',
                'height',
                'illumination_id',
                'facing_id',
                'facing',
                'latitude',
                'longitude',
                'minimum_booking_days',
                'price',
                // 'vendor_name',
                'vendor_id',
                // 'video_link',
                'panorama_image'

            ]);

            // ONLY HOARDINGS
            if (str_contains($slug, 'hoardings')) {
                $mediaData['media_code'] = $request->media_code;
            } else {
                $mediaData['media_code'] = null;
            }

            // AUTO GENERATE MEDIA CODE
            // $mediaData['media_code'] = $this->generateMediaCode($request->vendor_id);

            /** -------------------------
             * OPTIONAL FIELDS
             * ------------------------*/
            $optionalFields = [
                'mall_name',
                'media_format',
                'airport_name',
                'zone_type',
                'media_type',
                'transit_type',
                'branding_type',
                'vehicle_count',
                'building_name',
                'wall_length',
                'area_auto',
                'radius_id',
                'areatype_id',
                'highway_id',
                // 'area_type',
                'video_link',
                'panorama_image'
            ];
            foreach ($optionalFields as $field) {
                $mediaData[$field] = $request->input($field);
            }

            // Panel-sized categories overwrite what the (hidden) Width/Height and
            // Area inputs posted — see applyLocationSizeDimensions.
            $panels = $this->panelsFromRequest($request);
            $mediaData = $this->applyLocationSizeDimensions($mediaData, $slug, $panels);

            // AUTO-GENERATE THE SITE CODE (HD000001 for a hoarding, BS000001
            // for a bus shelter). Which categories get one, and under which
            // prefix, is MediaCode's call — the bulk import asks it the same
            // question. A category with no scheme is saved without a code
            // rather than handed a number nothing ever shows.
            $prefix = MediaCode::prefixFor($slug);
            $mediaData['hoarding_code'] = $prefix ? MediaCode::next($prefix) : null;
            // foreach ($optionalFields as $field) {
            //     if ($request->has($field)) {
            //         $mediaData[$field] = $request->$field;
            //     }
            // }

            $mediaData['is_active']  = 1;
            $mediaData['is_deleted'] = 0;

            /** -------------------------
             * SAVE MEDIA
             * ------------------------*/
            $media = $this->repo->store($mediaData);

            /** SYNC LANDMARKS (many-to-many) */
            $landmarkIds = array_filter((array) $request->input('landmark_ids', []));
            $media->landmarks()->sync($landmarkIds);

            /** SAVE PER-PANEL SIZES (Bus Shelter's Front / Back / Side) */
            $this->syncLocationSizes($media, $slug, $panels);

            /**  SAVE IMAGES */
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {

                    $fileName = uploadImage(
                        $image,
                        config('fileConstants.IMAGE_ADD')
                    );
                    /** -------------------------
                     * SAVE PANORAMA IMAGE
                     * ------------------------*/
                    if ($request->hasFile('panorama_image')) {

                        $panoramaName = uploadImage(
                            $request->file('panorama_image'),
                            config('fileConstants.IMAGE_ADD')
                        );

                        $media->update([
                            'panorama_image' => $panoramaName
                        ]);
                    }
                    MediaImage::create([
                        'media_id'  => $media->id,
                        'images'    => $fileName,
                        'is_active' => 1,
                        'is_deleted' => 0,
                    ]);
                }
            }

            DB::commit();
            return $media;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function update($id, Request $request, string $slug)
    {
        DB::beginTransaction();

        try {

            // ⭐ FETCH OLD MEDIA FIRST
            $media = $this->repo->find($id);

            $updateData = $request->only([
                'state_id',
                'district_id',
                'city_id',
                'area_id',
                'category_id',
                'media_title',
                'address',
                'width',
                'height',
                'illumination_id',
                'areatype_id',
                'facing',
                'latitude',
                'longitude',
                'minimum_booking_days',
                'price',
                'vendor_id',
                'area_auto',
                'highway_id'
            ]);

            $prefix = MediaCode::prefixFor($slug);

            // MEDIA CODE is a hoardings-only field and stays one: it is a
            // different thing from the site code beside it, and adding a
            // second code scheme is no reason to start writing it elsewhere.
            $updateData['media_code'] = $prefix === 'HD' ? $request->media_code : null;

            // SITE CODE is editable: take the entered value, or mint one when
            // blank — which covers records added before their category had
            // codes and still carry none. Minted under this category's own
            // prefix, so editing a bus shelter cannot hand it an HD.
            if ($prefix !== null) {
                $hoardingCode = trim((string) $request->input('hoarding_code'));

                if ($hoardingCode === '') {
                    $hoardingCode = $media->hoarding_code ?: MediaCode::next($prefix);
                }

                $updateData['hoarding_code'] = $hoardingCode;
            }

            // Panel-sized categories overwrite the posted Width/Height/Area.
            $panels = $this->panelsFromRequest($request);
            $updateData = $this->applyLocationSizeDimensions($updateData, $slug, $panels);

            /** UPDATE BASIC DATA */
            $this->repo->update($id, $updateData);

            /** SYNC LANDMARKS (many-to-many) */
            $landmarkIds = array_filter((array) $request->input('landmark_ids', []));
            $media->landmarks()->sync($landmarkIds);

            /** SYNC PER-PANEL SIZES (Bus Shelter's Front / Back / Side) */
            $this->syncLocationSizes($media, $slug, $panels);

            /** 🔥 PANORAMA UPDATE */
            if ($request->hasFile('panorama_image')) {

                // REMOVE OLD FILE
                if (!empty($media->panorama_image)) {

                    removeImage(
                        $media->panorama_image,
                        config('fileConstants.IMAGE_DELETE')
                    );
                }

                // UPLOAD NEW
                $panoramaName = uploadImage(
                    $request->file('panorama_image'),
                    config('fileConstants.IMAGE_ADD')
                );

                // UPDATE DB
                $this->repo->update($id, [
                    'panorama_image' => $panoramaName
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function toggleStatus($id)
    {
        $this->repo->toggleStatus($id);
    }
    public function delete($id)
    {
        DB::beginTransaction();

        try {

            // 🔹 Get all active images of this media
            $images = MediaImage::where('media_id', $id)
                ->where('is_deleted', 0)
                ->get();

            // 🔹 Delete image files first
            foreach ($images as $img) {
                removeImage(
                    $img->images,
                    config('fileConstants.IMAGE_DELETE')
                );
            }

            // 🔹 Soft delete image records
            MediaImage::where('media_id', $id)->update([
                'is_deleted' => 1,
                'is_active'  => 0
            ]);

            // 🔹 Soft delete media
            $this->repo->softDelete($id);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    private function generateMediaCode(int $vendorId): string
    {
        // Get vendor code
        $vendor = DB::table('vendors')->where('id', $vendorId)->first();

        if (!$vendor) {
            throw new \Exception('Vendor not found');
        }

        $vendorCode = $vendor->vendor_code;

        // Count existing media for this vendor
        $count = DB::table('media_management')
            ->where('vendor_id', $vendorId)
            ->where('is_deleted', 0)
            ->count();

        // Next sequence
        $next = str_pad($count + 1, 2, '0', STR_PAD_LEFT);

        return $vendorCode . '_' . $next;
    }

    /**
     * Categories that are sized panel by panel instead of by one face.
     *
     * A Bus Shelter carries a Front, a Back and a Side, each with its own
     * dimensions, so the Add / Edit form hides Width and Height for it and
     * collects location_sizes[position][width|height] instead.
     */
    private function usesLocationSizes(string $slug): bool
    {
        return str_contains($slug, 'bus-shelter');
    }

    /**
     * The posted panels, normalised to [position => ['width' => ?float,
     * 'height' => ?float]] and limited to the positions the form offers.
     *
     * A panel is only kept when BOTH dimensions are present: a half-filled row
     * means someone started typing and moved on, not a panel of unknown height.
     */
    private function panelsFromRequest(Request $request): array
    {
        $posted = (array) $request->input('location_sizes', []);
        $panels = [];

        foreach (array_keys(MediaLocationSize::POSITIONS) as $position) {
            $width  = $this->numericOrNull($posted[$position]['width']  ?? null);
            $height = $this->numericOrNull($posted[$position]['height'] ?? null);

            if ($width !== null && $height !== null) {
                $panels[$position] = [
                    'width'  => $width,
                    'height' => $height,
                    // Blank means one board, which is what the column defaults
                    // to and what every panel stored before it existed means.
                    'quantity' => $this->quantityOrDefault($posted[$position]['quantity'] ?? null),
                ];
            }
        }

        return $panels;
    }

    private function numericOrNull($value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * A posted panel quantity as a whole number of boards, at least one.
     *
     * Blank, zero or nonsense all mean a single board rather than none: the
     * panel only exists in the first place because a width and a height were
     * given for it.
     */
    private function quantityOrDefault($value): int
    {
        $quantity = is_numeric($value) ? (int) $value : 0;

        return $quantity > 0 ? min($quantity, 99) : MediaLocationSize::DEFAULT_QUANTITY;
    }

    /**
     * For a panel-sized category, replace the dimension columns with what the
     * panels actually say.
     *
     * The Width / Height / Area inputs are hidden rather than removed for these
     * categories, so the browser still posts them as empty strings - and MySQL
     * outside strict mode turns an empty string into a very convincing 0.00.
     * Nulling them keeps "this shelter has no single face" distinct from "this
     * shelter is zero feet wide".
     *
     * area_auto is the panels added together: it is what the public site's
     * Media Size filter reads, so leaving it empty would drop these records out
     * of that filter entirely.
     */
    private function applyLocationSizeDimensions(array $data, string $slug, array $panels): array
    {
        if (!$this->usesLocationSizes($slug)) {
            return $data;
        }

        $data['width']  = null;
        $data['height'] = null;

        // Every board counts, not every position: a Front of 40x20 with a
        // quantity of 3 is 2,400 sq ft of advertising face, not 800.
        $area = 0.0;
        foreach ($panels as $panel) {
            $area += $panel['width'] * $panel['height']
                * ($panel['quantity'] ?? MediaLocationSize::DEFAULT_QUANTITY);
        }

        $data['area_auto'] = $panels ? number_format($area, 2, '.', '') : null;

        return $data;
    }

    /**
     * Store the posted panels against a media record, dropping any that are no
     * longer there - so clearing a panel, or moving a record to a category that
     * has none, never leaves an orphan row behind.
     */
    private function syncLocationSizes($media, string $slug, array $panels): void
    {
        $panels = $this->usesLocationSizes($slug) ? $panels : [];

        $media->locationSizes()
            ->whereNotIn('position', array_keys($panels) ?: [''])
            ->delete();

        foreach ($panels as $position => $dimensions) {
            $media->locationSizes()->updateOrCreate(['position' => $position], $dimensions);
        }
    }

    public function viewDetails($id)
    {
        $rows = $this->repo->getDetailsById($id);
        if ($rows->isEmpty()) {
            return null;
        }
        $media = $rows->first();
        $media->images = $rows
            ->whereNotNull('image_name')
            ->map(function ($row) {
                return [
                    'id'    => $row->image_id,
                    'image' => $row->image_name,
                ];
            })
            ->values();

        // Attach tagged landmark names (many-to-many)
        $media->landmark_names = DB::table('media_landmark as ml')
            ->join('landmark as l', 'l.id', '=', 'ml.landmark_id')
            ->where('ml.media_id', $id)
            ->where('l.is_deleted', 0)
            ->pluck('l.landmark_name')
            ->toArray();

        // Per-panel sizes (Bus Shelter's Front / Back / Side), in the order the
        // form offers them so the details page reads the same way.
        $panels = MediaLocationSize::where('media_id', $id)->get()->keyBy('position');
        $media->location_sizes = collect(MediaLocationSize::POSITIONS)
            ->map(fn($label, $position) => $panels->get($position))
            ->filter()
            ->values();

        return $media;
    }
}

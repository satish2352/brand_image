<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorSlider;
use App\Support\VendorAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Vendor Branding & Website Showcase settings (PRD 2.9). */
class WebsiteController extends Controller
{
    private function vendor()
    {
        return auth('vendor')->user();
    }

    public function index()
    {
        $vendor = $this->vendor();

        return view('vendor.website.index', [
            'vendor' => $vendor,
            'slides' => VendorSlider::where('vendor_id', $vendor->id)->orderBy('sort_order')->orderBy('id')->get(),
            'maxUploadMb' => (int) min($this->iniBytes('upload_max_filesize'), $this->iniBytes('post_max_size')) / 1048576,
        ]);
    }

    public function updateLogo(Request $request)
    {
        $vendor = $this->vendor();

        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
        ], ['logo.max' => 'The logo must be under 2 MB.']);

        $path = $request->file('logo')->store('upload/vendors/' . $vendor->id, 'public');
        if ($vendor->logo) {
            Storage::disk('public')->delete($vendor->logo);
        }
        $vendor->forceFill(['logo' => $path])->save();

        VendorAudit::vendorDid($vendor, 'Branding Updated', ['Changed fields' => 'Logo']);

        return back()->with('success', 'Logo updated.');
    }

    public function removeLogo()
    {
        $vendor = $this->vendor();
        if ($vendor->logo) {
            Storage::disk('public')->delete($vendor->logo);
            $vendor->forceFill(['logo' => null])->save();
        }

        return back()->with('success', 'Logo removed.');
    }

    public function storeSlide(Request $request)
    {
        $vendor = $this->vendor();

        $data = $request->validate([
            'media_type' => 'required|in:image,video',
            'title'      => 'nullable|string|max:120',
            'file'       => $request->media_type === 'video'
                ? 'required|file|mimetypes:video/mp4,video/webm|max:51200'
                : 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'file.max'       => $request->media_type === 'video' ? 'Videos must be under 50 MB.' : 'Images must be under 4 MB.',
            'file.mimetypes' => 'Videos must be MP4 or WebM.',
        ]);

        VendorSlider::create([
            'vendor_id'  => $vendor->id,
            'media_type' => $data['media_type'],
            'title'      => $data['title'] ?? null,
            'file'       => $request->file('file')->store('upload/vendors/' . $vendor->id . '/slider', 'public'),
            'sort_order' => (int) VendorSlider::where('vendor_id', $vendor->id)->max('sort_order') + 1,
            'is_active'  => true,
        ]);

        VendorAudit::vendorDid($vendor, 'Website Slider Updated', ['Changed fields' => ucfirst($data['media_type']) . ' added']);

        return back()->with('success', ucfirst($data['media_type']) . ' added to the slider.');
    }

    public function updateSlide(Request $request, int $id)
    {
        $slide = VendorSlider::where('vendor_id', $this->vendor()->id)->findOrFail($id);

        if ($request->has('toggle')) {
            $slide->update(['is_active' => !$slide->is_active]);
        }
        if ($request->has('title')) {
            $slide->update(['title' => mb_substr((string) $request->title, 0, 120) ?: null]);
        }
        if (in_array($request->move, ['up', 'down'], true)) {
            $this->move($slide, $request->move);
        }

        return back()->with('success', 'Slider updated.');
    }

    /**
     * Edit a slide: caption, and optionally a new image or video in place of
     * the current file. The slide keeps its position and on/off state.
     */
    public function editSlide(Request $request, int $id)
    {
        $vendor = $this->vendor();
        $slide  = VendorSlider::where('vendor_id', $vendor->id)->findOrFail($id);

        $type = $request->input('media_type', $slide->media_type);

        // If validation sends us back, reopen this slide's edit panel.
        session()->flash('edit_slide', $slide->id);

        $data = $request->validate([
            'media_type' => 'required|in:image,video',
            'title'      => 'nullable|string|max:120',
            'file'       => $type === 'video'
                ? 'nullable|file|mimetypes:video/mp4,video/webm|max:51200'
                : 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'file.max'       => $type === 'video' ? 'Videos must be under 50 MB.' : 'Images must be under 4 MB.',
            'file.mimetypes' => 'Videos must be MP4 or WebM.',
            'file.image'     => 'Choose an image file (JPG, PNG or WEBP).',
        ]);

        // Switching between image and video needs the new file to go with it.
        if ($data['media_type'] !== $slide->media_type && !$request->hasFile('file')) {
            return back()->withErrors(['file' => 'Choose the new ' . $data['media_type'] . ' file to change this slide\'s type.'])
                ->with('edit_slide', $slide->id);
        }

        $changed = [];
        $update  = ['title' => ($data['title'] ?? null) ?: null];
        if ($update['title'] !== $slide->title) {
            $changed[] = 'Caption';
        }

        if ($request->hasFile('file')) {
            $update['file'] = $request->file('file')->store('upload/vendors/' . $vendor->id . '/slider', 'public');
            $update['media_type'] = $data['media_type'];
            Storage::disk('public')->delete($slide->file);
            $changed[] = ucfirst($data['media_type']) . ' replaced';
        }

        $slide->update($update);
        session()->forget('edit_slide');

        if ($changed) {
            VendorAudit::vendorDid($vendor, 'Website Slider Updated', ['Changed fields' => 'Slide ' . $slide->sort_order . ': ' . implode(', ', $changed)]);
        }

        return back()->with('success', $changed ? 'Slide updated.' : 'No changes to save.');
    }

    public function destroySlide(int $id)
    {
        $vendor = $this->vendor();
        $slide = VendorSlider::where('vendor_id', $vendor->id)->findOrFail($id);
        Storage::disk('public')->delete($slide->file);
        $slide->delete();

        VendorAudit::vendorDid($vendor, 'Website Slider Updated', ['Changed fields' => ucfirst($slide->media_type) . ' removed']);

        return back()->with('success', 'Slide removed.');
    }

    private function move(VendorSlider $slide, string $direction): void
    {
        $slides = VendorSlider::where('vendor_id', $slide->vendor_id)->orderBy('sort_order')->orderBy('id')->get()->values();
        $index = $slides->search(fn ($s) => $s->id === $slide->id);
        $swap = $direction === 'up' ? $index - 1 : $index + 1;

        if ($swap < 0 || $swap >= $slides->count()) {
            return;
        }

        $ordered = $slides->all();
        [$ordered[$index], $ordered[$swap]] = [$ordered[$swap], $ordered[$index]];
        foreach ($ordered as $i => $s) {
            $s->update(['sort_order' => $i + 1]);
        }
    }

    private function iniBytes(string $key): int
    {
        $value = trim((string) ini_get($key));
        $num = (int) $value;
        return match (strtolower(substr($value, -1))) {
            'g' => $num * 1073741824,
            'm' => $num * 1048576,
            'k' => $num * 1024,
            default => $num,
        };
    }
}

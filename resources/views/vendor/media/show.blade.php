@extends('vendor.layout')
@section('title', $media->display_code)
@section('crumb')
    <a href="{{ route('vendor.inventory') }}">Media Management</a> / {{ $media->display_code }}
@endsection
@section('heading', $media->media_title ?: $media->display_code)

@section('actions')
    @php
        // Back to the page the vendor came from (keeps list filters / page),
        // unless that was another media page (e.g. Edit) — then the list.
        $prev = url()->previous();
        $backUrl = str_starts_with($prev, url('vendor-panel')) && ! str_contains($prev, '/vendor-panel/media/') && $prev !== url()->current()
            ? $prev
            : route('vendor.inventory');
    @endphp
    <a href="{{ $backUrl }}" class="vp-btn vp-btn-g"><i class="fa-solid fa-arrow-left"></i>Back</a>
    <a href="{{ route('vendor.media.edit', $media->id) }}" class="vp-btn vp-btn-g"><i class="fa-solid fa-pen"></i>Edit</a>
    @if ($media->approval_status === 'approved')
        <button type="button" class="vp-btn vp-btn-o js-availability" data-url="{{ route('vendor.availability.form', $media->id) }}"><i class="fa-regular fa-calendar"></i>Update Availability</button>
    @endif
@endsection

@section('content')
    @php
        $approvalLabel = \App\Http\Services\Vendor\VendorInventoryService::APPROVAL_LABELS[$media->approval_status] ?? $media->approval_status;
        $rows = [
            'Sector / Hoarding Code' => $details->hoarding_code ?: '—',
            'Media Code' => $details->media_code ?: '—',
            'Category' => $details->category_name,
            'State' => $details->state_name,
            'District' => $details->district_name,
            'City / Town' => $details->city_name,
            'Area' => $details->area_name,
            'Area Type' => $details->areatype_name ?: '—',
            'Highway' => $details->highway_name ?: '—',
            'Address' => $details->address ?: '—',
            'Size' => $sizes->isNotEmpty()
                ? $sizes->map(fn($s) => ucfirst($s->position) . ' ' . (float) $s->width . ' × ' . (float) $s->height . ' ft')->implode(', ')
                : ((float) $details->width . ' × ' . (float) $details->height . ' ft'),
            'Illumination' => $details->illumination_name ?: '—',
            'Facing' => $details->facing ?: '—',
            'Latitude / Longitude' => $details->latitude . ', ' . $details->longitude,
            'Monthly Price' => '₹ ' . number_format((float) $details->price),
        ];
    @endphp

    @if ($media->approval_status === 'rejected')
        <div class="vp-alert vp-alert-error" role="alert">
            <strong>Rejected by Brand Adda.</strong> Reason: “{{ $media->rejection_reason }}”
            <a href="{{ route('vendor.media.edit', $media->id) }}" style="font-weight:700;margin-left:6px">Edit &amp; resubmit</a>
        </div>
    @elseif ($media->approval_status === 'pending')
        <div class="vp-alert vp-alert-info">This media is waiting for Brand Adda approval. It is not shown on the website or open for booking yet.</div>
    @endif

    <div style="display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start">
        <div style="flex:999 1 560px;min-width:0;display:flex;flex-direction:column;gap:22px">
            <section class="vp-card vp-card-pad">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px">
                    <h2 class="vp-h2">Photos</h2>
                    <span class="vp-muted">{{ $images->count() }} of 10 · JPG / PNG / WEBP under 600 KB</span>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px">
                    @foreach ($images as $img)
                        <div style="position:relative">
                            <img src="{{ \App\Support\MediaImageUrl::for($img->images) }}" alt="Media photo {{ $loop->iteration }}" class="vp-thumb" style="width:100%;height:120px">
                            @if ($loop->first)<span class="vp-badge" style="position:absolute;left:8px;top:8px;background:#0F172A;color:#fff">Cover</span>@endif
                            <form method="POST" action="{{ route('vendor.media.images.delete', [$media->id, $img->id]) }}" data-confirm="Remove this photo?" data-confirm-button="Yes, remove"
                                style="position:absolute;right:6px;top:6px;margin:0">
                                @csrf
                                <button type="submit" class="vp-ib danger" style="width:32px;height:32px" aria-label="Remove photo"><i class="fa-solid fa-xmark"></i></button>
                            </form>
                        </div>
                    @endforeach
                    @if ($images->count() < 10)
                        <form method="POST" action="{{ route('vendor.media.images.upload', $media->id) }}" enctype="multipart/form-data" style="margin:0">
                            @csrf
                            <label class="vp-drop" style="height:120px;margin:0">
                                <i class="fa-solid fa-arrow-up-from-bracket" style="font-size:20px"></i>Upload Photo
                                <input type="file" name="images[]" accept="image/png,image/jpeg,image/webp" multiple class="sr-only js-photo-upload">
                            </label>
                        </form>
                    @endif
                </div>
                {{-- Photo upload errors show here, under the photos, not in the page-top alert. --}}
                @php
                    $photoErrors = collect($errors->getMessages())
                        ->filter(fn ($m, $k) => $k === 'images' || str_starts_with($k, 'images.'))
                        ->flatten()->unique()->values();
                @endphp
                <div id="photoErrors" class="vp-field-error" role="alert" style="color:#B91C1C;font-size:13px;font-weight:600;margin-top:12px;{{ $photoErrors->isEmpty() ? 'display:none' : '' }}">
                    @foreach ($photoErrors as $msg)<div><i class="fa-solid fa-circle-exclamation"></i> {{ $msg }}</div>@endforeach
                </div>
            </section>

            {{-- 360° image (panorama_image), shown with the site's Pannellum viewer (public/panorama.html). --}}
            @php
                $panoramaUrl = $media->panorama_image ? \App\Support\MediaImageUrl::for($media->panorama_image) : null;
                $panoramaViewer = $panoramaUrl ? url('panorama.html') . '?img=' . urlencode($panoramaUrl) : null;
            @endphp
            <section class="vp-card vp-card-pad">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px">
                    <h2 class="vp-h2" style="margin:0">360° Image</h2>
                    @if ($panoramaViewer)
                        <a href="{{ $panoramaViewer }}" target="_blank" rel="noopener" class="vp-btn vp-btn-g vp-btn-sm"><i class="fa-solid fa-expand"></i>Full screen</a>
                    @endif
                </div>
                @if ($panoramaViewer)
                    <iframe src="{{ $panoramaViewer }}" title="360° view of {{ $media->display_code }}" loading="lazy" allowfullscreen
                        style="display:block;width:100%;height:340px;border:0;border-radius:12px;background:#0F172A"></iframe>
                    <p class="vp-muted" style="margin:10px 0 0;font-size:13px">Drag to look around. To change it, <a href="{{ route('vendor.media.edit', $media->id) }}">edit this media</a>.</p>
                @else
                    <p class="vp-muted" style="margin:0">No 360° image yet. <a href="{{ route('vendor.media.edit', $media->id) }}">Add one</a> (image size must be less than 5 MB).</p>
                @endif
            </section>

            <section class="vp-card vp-card-pad">
                <h2 class="vp-h2" style="margin-bottom:16px">Media Details</h2>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px 24px">
                    @foreach ($rows as $k => $v)
                        <div style="min-width:0">
                            <div class="vp-muted" style="font-size:12px;font-weight:600">{{ $k }}</div>
                            {{-- Long codes (MAH_NAS_..._01) have no spaces: let them break after _ or / instead of spilling into the next column. --}}
                            <div style="font-size:15px;font-weight:700;margin-top:4px;overflow-wrap:anywhere">{!! preg_replace('~([_/])~', '$1<wbr>', e($v)) !!}</div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <aside style="flex:1 1 300px;display:flex;flex-direction:column;gap:20px">
            <section class="vp-card vp-card-pad" style="display:flex;flex-direction:column;gap:14px">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span class="vp-muted" style="font-size:14px">Approval</span>
                    <span class="vp-badge vp-badge-{{ $media->approval_status }}">{{ $approvalLabel }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span class="vp-muted" style="font-size:14px">Status today</span>
                    <span class="vp-badge" style="background:{{ $availability['bg'] }};color:{{ $availability['fg'] }}">{{ $availability['label'] }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span class="vp-muted" style="font-size:14px">Active on website</span>
                    <form method="POST" action="{{ route('vendor.media.toggle', $media->id) }}" style="margin:0">
                        @csrf
                        <button type="submit" role="switch" aria-checked="{{ $media->is_active ? 'true' : 'false' }}" aria-label="Active"
                            class="vp-switch {{ $media->is_active ? 'on' : '' }}" @disabled($media->approval_status !== 'approved')><span></span></button>
                    </form>
                </div>
                @if ($ranges->isNotEmpty())
                    <div style="border-top:1px solid #F1F5F9;padding-top:12px">
                        <div style="font-size:13px;font-weight:700;margin-bottom:6px">Upcoming dates</div>
                        @foreach ($ranges as $r)
                            <div style="font-size:13px;color:#334155">{{ ucfirst($r->status_type) }} · {{ \Carbon\Carbon::parse($r->from_date)->format('d M Y') }} – {{ \Carbon\Carbon::parse($r->to_date)->format('d M Y') }}</div>
                        @endforeach
                    </div>
                @endif
                <form method="POST" action="{{ route('vendor.media.destroy', $media->id) }}" data-confirm="Delete {{ $media->display_code }}? This cannot be undone." data-confirm-button="Yes, delete" style="margin:0">
                    @csrf
                    <button type="submit" class="vp-btn vp-btn-r" style="width:100%"><i class="fa-regular fa-trash-can"></i>Delete Media</button>
                </form>
            </section>

            <section class="vp-card vp-card-pad">
                <h2 class="vp-h2" style="margin-bottom:12px">History</h2>
                @forelse ($history as $h)
                    <div style="padding:8px 0;border-top:1px solid #F1F5F9">
                        <div style="font-size:13px;font-weight:700">{{ $h->action }}</div>
                        <div class="vp-muted" style="font-size:12px">{{ $h->created_at->format('d M Y, h:i A') }} · {{ $h->actor_type === 'admin' ? 'Brand Adda' : 'You' }}</div>
                        @if ($h->details)
                            <div style="font-size:12px;color:#334155;margin-top:2px">
                                @foreach ($h->details as $dk => $dv){{ $dk }}: {{ is_array($dv) ? implode(' → ', $dv) : $dv }}@if (!$loop->last) · @endif @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="vp-muted" style="margin:0">No activity recorded yet.</p>
                @endforelse
            </section>
        </aside>
    </div>

    @include('vendor.inventory.partials.availability-modal')
@endsection

{{-- If every error is a photo error it is already shown under Photos: skip the page-top alert. --}}
@if ($errors->any() && collect($errors->keys())->every(fn ($k) => $k === 'images' || str_starts_with($k, 'images.')))
    @section('form_errors_inline', '1')
@endif

@section('scripts')
    <script>
        // Check photo size / type before uploading, so the vendor sees the problem at once under Photos.
        document.querySelectorAll('.js-photo-upload').forEach(function (input) {
            input.addEventListener('change', function () {
                var box = document.getElementById('photoErrors');
                var maxBytes = 600 * 1024, okTypes = ['image/jpeg', 'image/png', 'image/webp'], msgs = [];
                Array.prototype.forEach.call(input.files, function (f) {
                    if (okTypes.indexOf(f.type) === -1) msgs.push(f.name + ': only WebP, JPG, JPEG, and PNG images are allowed.');
                    else if (f.size > maxBytes) msgs.push(f.name + ' is ' + Math.round(f.size / 1024) + ' KB. Each image must be less than 600 KB.');
                });
                if (msgs.length) {
                    box.innerHTML = '';
                    msgs.forEach(function (m) {
                        var d = document.createElement('div');
                        d.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> ';
                        d.appendChild(document.createTextNode(m));
                        box.appendChild(d);
                    });
                    box.style.display = '';
                    input.value = ''; // let the same file be picked again after fixing it
                    return;
                }
                box.style.display = 'none';
                input.form.submit();
            });
        });
    </script>
@endsection

@extends('vendor.layout')
@section('title', 'Website & Slider')
@section('crumb', 'Vendor Panel / Website & Slider')
@section('heading', 'Website Branding & Slider')

@section('actions')
    <a href="{{ $vendor->website_url }}" target="_blank" rel="noopener" class="vp-btn vp-btn-g"><i class="fa-solid fa-arrow-up-right-from-square"></i>Preview Website</a>
@endsection

@section('content')
    <div style="display:flex;flex-wrap:wrap;gap:24px;align-items:stretch">
        <section class="vp-card vp-card-pad" style="flex:1 1 420px">
            <h2 class="vp-h2" style="margin-bottom:4px">Branding</h2>
            <p class="vp-muted" style="margin:0 0 18px">Shown on your website, inventory exports and reports, with “Powered by Brand Adda”.</p>
            <div style="display:flex;flex-wrap:wrap;gap:20px;align-items:center">
                <div style="width:110px;height:110px;border-radius:16px;background:#FFF7ED;border:2px dashed #FDBA74;display:grid;place-items:center;font-weight:800;font-size:28px;color:#9A3412;overflow:hidden">
                    @if ($vendor->logo_url)
                        <img src="{{ $vendor->logo_url }}" alt="{{ $vendor->vendor_name }} logo" style="width:100%;height:100%;object-fit:contain;background:#fff">
                    @else
                        {{ $vendor->initials }}
                    @endif
                </div>
                <div style="display:flex;flex-direction:column;gap:10px">
                    <form method="POST" action="{{ route('vendor.website.logo') }}" enctype="multipart/form-data" style="margin:0">
                        @csrf
                        <label class="vp-btn vp-btn-g" style="margin:0">Upload Logo
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="sr-only" onchange="this.form.submit()"></label>
                    </form>
                    @if ($vendor->logo)
                        <form method="POST" action="{{ route('vendor.website.logo.remove') }}" style="margin:0" data-confirm="Remove your logo?">
                            @csrf<button type="submit" class="vp-btn vp-btn-r vp-btn-sm">Remove logo</button></form>
                    @endif
                    <span class="vp-muted" style="font-size:12px">PNG / JPG / WEBP, square, under 2 MB</span>
                </div>
            </div>
            <div style="margin-top:18px">
                <span class="vp-label">Company Name</span>
                <div class="vp-input" style="display:flex;align-items:center;background:#F8FAFC">{{ $vendor->vendor_name }}</div>
                <div class="vp-muted" style="font-size:12px;margin-top:6px">Managed by Brand Adda. Ask your account manager to change it.</div>
            </div>
        </section>

        <section class="vp-card vp-card-pad" style="flex:1 1 420px;display:flex;flex-direction:column;gap:14px">
            <h2 class="vp-h2">Website Link</h2>
            <div style="display:flex;gap:10px;align-items:center">
                <div id="siteUrl" class="vp-input" style="display:flex;align-items:center;background:#F8FAFC;font-weight:600;overflow:hidden">{{ $vendor->website_url }}</div>
                <button type="button" class="vp-btn vp-btn-n" onclick="navigator.clipboard.writeText(document.getElementById('siteUrl').innerText.trim()).then(()=>{this.innerText='Copied'})">Copy</button>
            </div>
            <div style="display:flex;align-items:center;gap:10px;font-size:14px;flex-wrap:wrap">
                <span class="vp-badge vp-badge-approved">Live 24/7</span>
                <span class="vp-muted" style="font-size:14px">Visitors verify with an OTP sent to your registered email before viewing.</span>
            </div>
            <div style="padding:14px 16px;border-radius:10px;background:#F8FAFC;font-size:13px;line-height:1.6;color:#334155">
                Shows only your approved, active inventory and its location map. No login, cart, campaign, booking, About Us, Contact, footer or timer.
            </div>
        </section>
    </div>

    <section class="vp-card vp-card-pad">
        <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:flex-start;margin-bottom:18px">
            <div>
                <h2 class="vp-h2">Home Slider</h2>
                <div class="vp-muted" style="margin-top:4px">Images (JPG / PNG / WEBP, 1920 × 700, under 4 MB) and videos (MP4 / WebM, under 50 MB{{ $maxUploadMb < 50 ? '; this server accepts up to ' . $maxUploadMb . ' MB per upload' : '' }}).</div>
            </div>
        </div>

        <form method="POST" action="{{ route('vendor.website.slides.store') }}" enctype="multipart/form-data"
            style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;align-items:end;padding:16px;border-radius:12px;background:#F8FAFC;margin-bottom:18px">
            @csrf
            <div><label class="vp-label" for="s-type">Type</label>
                <select id="s-type" name="media_type" class="vp-input"><option value="image">Image</option><option value="video">Video</option></select></div>
            <div><label class="vp-label" for="s-title">Caption (optional)</label><input id="s-title" name="title" class="vp-input" maxlength="120"></div>
            <div><label class="vp-label" for="s-file">File</label><input id="s-file" type="file" name="file" class="vp-input" style="padding-top:8px" required accept="image/*,video/mp4,video/webm"></div>
            <div><button type="submit" class="vp-btn vp-btn-o" style="width:100%"><i class="fa-solid fa-plus"></i>Add to Slider</button></div>
        </form>

        <div style="display:flex;flex-direction:column;gap:10px">
            @forelse ($slides as $s)
                @php $editing = (int) session('edit_slide') === $s->id; @endphp
                <div style="border:1px solid {{ $editing ? '#FDBA74' : '#E5E7EB' }};border-radius:12px">
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:16px;padding:12px">
                    <span style="width:28px;font-size:14px;font-weight:800">{{ $loop->iteration }}</span>
                    @if ($s->media_type === 'video')
                        <video src="{{ $s->url }}" muted style="width:160px;height:64px;object-fit:cover;border-radius:10px;background:#0F172A"></video>
                    @else
                        <img src="{{ $s->url }}" alt="" class="vp-thumb" style="width:160px;height:64px">
                    @endif
                    <div style="flex:1;min-width:200px">
                        <div style="font-size:14px;font-weight:700">{{ $s->title ?: 'No caption' }}</div>
                        <div class="vp-muted" style="font-size:12px;margin-top:2px">{{ $s->is_active ? 'Shown on website' : 'Hidden' }} · updated {{ $s->updated_at->format('d M Y') }}</div>
                    </div>
                    <span class="vp-badge" style="{{ $s->media_type === 'video' ? 'background:#DBEAFE;color:#1E40AF' : 'background:#F1F5F9;color:#334155' }}">{{ ucfirst($s->media_type) }}</span>
                    <button type="button" class="vp-ib js-edit-slide" data-target="edit-slide-{{ $s->id }}" aria-expanded="{{ $editing ? 'true' : 'false' }}" aria-controls="edit-slide-{{ $s->id }}" aria-label="Edit slide {{ $loop->iteration }}" title="Edit"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST" action="{{ route('vendor.website.slides.update', $s->id) }}" style="margin:0">@csrf<input type="hidden" name="toggle" value="1">
                        <button type="submit" role="switch" aria-checked="{{ $s->is_active ? 'true' : 'false' }}" aria-label="Show on website" class="vp-switch {{ $s->is_active ? 'on' : '' }}"><span></span></button></form>
                    <form method="POST" action="{{ route('vendor.website.slides.update', $s->id) }}" style="margin:0">@csrf<input type="hidden" name="move" value="up">
                        <button type="submit" class="vp-ib" aria-label="Move up" @disabled($loop->first)><i class="fa-solid fa-arrow-up"></i></button></form>
                    <form method="POST" action="{{ route('vendor.website.slides.update', $s->id) }}" style="margin:0">@csrf<input type="hidden" name="move" value="down">
                        <button type="submit" class="vp-ib" aria-label="Move down" @disabled($loop->last)><i class="fa-solid fa-arrow-down"></i></button></form>
                    <form method="POST" action="{{ route('vendor.website.slides.destroy', $s->id) }}" style="margin:0" data-confirm="Remove this slide?" data-confirm-button="Yes, remove">@csrf
                        <button type="submit" class="vp-ib danger" aria-label="Delete slide"><i class="fa-regular fa-trash-can"></i></button></form>
                </div>

                <form id="edit-slide-{{ $s->id }}" method="POST" action="{{ route('vendor.website.slides.edit', $s->id) }}" enctype="multipart/form-data"
                    style="display:{{ $editing ? 'grid' : 'none' }};grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;align-items:end;padding:16px;margin:0;border-top:1px solid #F1F5F9;background:#FFFBF5;border-radius:0 0 12px 12px">
                    @csrf
                    <div><label class="vp-label" for="et-{{ $s->id }}">Type</label>
                        <select id="et-{{ $s->id }}" name="media_type" class="vp-input">
                            <option value="image" @selected($s->media_type === 'image')>Image</option>
                            <option value="video" @selected($s->media_type === 'video')>Video</option>
                        </select></div>
                    <div><label class="vp-label" for="ec-{{ $s->id }}">Caption (optional)</label>
                        <input id="ec-{{ $s->id }}" name="title" class="vp-input" value="{{ $editing ? old('title', $s->title) : $s->title }}" maxlength="120" placeholder="No caption"></div>
                    <div><label class="vp-label" for="ef-{{ $s->id }}">Replace file (optional)</label>
                        <input id="ef-{{ $s->id }}" type="file" name="file" class="vp-input js-slide-file" style="padding-top:8px" accept="image/*,video/mp4,video/webm" data-preview="ep-{{ $s->id }}">
                        @if ($editing && $errors->has('file'))<div style="color:#B91C1C;font-size:12px;margin-top:6px">{{ $errors->first('file') }}</div>@endif
                    </div>
                    <div id="ep-{{ $s->id }}" class="vp-muted" style="font-size:12px">Leave empty to keep the current {{ $s->media_type }}.</div>
                    <div style="display:flex;gap:10px;justify-content:flex-end;grid-column:1/-1">
                        <button type="button" class="vp-btn vp-btn-g js-edit-slide" data-target="edit-slide-{{ $s->id }}">Cancel</button>
                        <button type="submit" class="vp-btn vp-btn-o">Save Changes</button>
                    </div>
                </form>
                </div>
            @empty
                <p class="vp-muted" style="margin:0">No slides yet. Your website shows a plain banner with your company name until you add one.</p>
            @endforelse
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        // Open / close a slide's edit panel.
        $(document).on('click', '.js-edit-slide', function () {
            const panel = document.getElementById(this.dataset.target);
            const open = panel.style.display === 'none';
            panel.style.display = open ? 'grid' : 'none';
            $('.js-edit-slide[data-target="' + this.dataset.target + '"][aria-expanded]').attr('aria-expanded', open ? 'true' : 'false');
            if (open) panel.querySelector('input[name="title"]').focus();
        });

        // Preview the replacement file and match the Type to it.
        $(document).on('change', '.js-slide-file', function () {
            const box = document.getElementById(this.dataset.preview);
            const file = this.files[0];
            if (!file) { box.textContent = 'Leave empty to keep the current file.'; return; }
            const isVideo = file.type.startsWith('video/');
            $(this).closest('form').find('select[name="media_type"]').val(isVideo ? 'video' : 'image');
            const url = URL.createObjectURL(file);
            box.innerHTML = isVideo
                ? '<video src="' + url + '" muted style="width:160px;height:64px;object-fit:cover;border-radius:10px;background:#0F172A"></video>'
                : '<img src="' + url + '" alt="New file preview" style="width:160px;height:64px;object-fit:cover;border-radius:10px">';
        });
    </script>
@endsection

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
                Shows only your approved, active inventory, its location map and a Contact page with an enquiry form. No login, cart, campaign, booking, footer or timer.
            </div>
        </section>
    </div>

    {{-- ================= COLOURS ================= --}}
    @php
        use App\Support\ThemeColor;
        $colorFields = [
            'panel_color' => ['Vendor Panel Sidebar', 'Background of the left menu in your Vendor Panel. Only you see this.', $vendor->panel_theme, 'panel'],
            'site_color'  => ['Website Brand Colour', 'Used on your public website: slider, page banners and dark buttons.', $vendor->site_theme, 'site'],
        ];
    @endphp
    <section class="vp-card vp-card-pad">
        <h2 class="vp-h2" style="margin-bottom:4px">Colours</h2>
        <p class="vp-muted" style="margin:0 0 18px">Pick a preset or any custom colour. Text colour adjusts automatically so it stays readable.</p>

        <form method="POST" action="{{ route('vendor.website.colors') }}" id="colorForm">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(360px,1fr));gap:24px">
                @foreach ($colorFields as $field => [$title, $hint, $current, $key])
                    <fieldset style="border:1px solid #E5E7EB;border-radius:12px;padding:18px;margin:0">
                        <legend style="font-size:15px;font-weight:800;padding:0 6px;width:auto;margin:0">{{ $title }}</legend>
                        <p class="vp-muted" style="margin:0 0 12px;font-size:13px">{{ $hint }}</p>

                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px" role="radiogroup" aria-label="{{ $title }} presets">
                            @foreach (ThemeColor::PRESETS as $hex => $name)
                                <button type="button" class="vp-swatch js-swatch" data-target="{{ $field }}" data-color="{{ $hex }}" title="{{ $name }}" aria-label="{{ $name }}"
                                    style="background:{{ $hex }};{{ $current === $hex ? 'outline:3px solid #F97316;outline-offset:2px' : '' }}"></button>
                            @endforeach
                        </div>

                        <div style="display:flex;gap:10px;align-items:center;margin-bottom:14px">
                            <label for="{{ $field }}" class="vp-muted" style="font-size:13px;margin:0">Custom</label>
                            <input type="color" id="{{ $field }}" name="{{ $field }}" value="{{ $current }}" class="js-color" data-preview="pv-{{ $key }}"
                                style="width:52px;height:40px;border:1px solid #CBD5E1;border-radius:8px;padding:2px;cursor:pointer;background:#fff">
                            <code class="js-hex" data-for="{{ $field }}" style="font-size:13px">{{ $current }}</code>
                            @if (($key === 'panel' && $vendor->panel_color) || ($key === 'site' && $vendor->site_color))
                                <button type="submit" name="reset" value="{{ $key }}" class="vp-btn vp-btn-g vp-btn-sm" style="margin-left:auto">Reset to default</button>
                            @endif
                        </div>

                        {{-- Live preview --}}
                        @if ($key === 'panel')
                            <div id="pv-panel" class="js-preview" data-kind="panel" style="display:flex;border-radius:10px;overflow:hidden;border:1px solid #E5E7EB;height:150px">
                                <div class="pv-bg" style="width:150px;padding:14px 10px;display:flex;flex-direction:column;gap:6px;background:{{ $current }}">
                                    <div class="pv-chip" style="height:26px;border-radius:6px;background:{{ ThemeColor::raised($current) }}"></div>
                                    <div style="height:22px;border-radius:6px;background:#F97316;color:#0F172A;font-size:11px;font-weight:700;padding:4px 8px">Dashboard</div>
                                    <div class="pv-text" style="font-size:11px;font-weight:600;padding:2px 8px;color:{{ ThemeColor::muted($current) }}">Inventory</div>
                                    <div class="pv-text" style="font-size:11px;font-weight:600;padding:2px 8px;color:{{ ThemeColor::muted($current) }}">Operations</div>
                                </div>
                                <div style="flex:1;background:#F1F5F9;padding:14px"><div style="height:18px;width:60%;border-radius:4px;background:#E2E8F0"></div></div>
                            </div>
                        @else
                            <div id="pv-site" class="js-preview" data-kind="site" style="border-radius:10px;overflow:hidden;border:1px solid #E5E7EB;height:150px;display:flex;flex-direction:column">
                                <div style="height:26px;background:#fff;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;padding:0 10px;font-size:11px;font-weight:800">{{ $vendor->vendor_name }}</div>
                                <div class="pv-bg" style="flex:1;padding:14px;display:flex;flex-direction:column;justify-content:center;gap:8px;background:{{ $current }}">
                                    <div class="pv-text" style="font-size:16px;font-weight:800;color:{{ ThemeColor::text($current) }}">Our outdoor media</div>
                                    <div style="display:flex;gap:6px">
                                        <span style="font-size:10px;font-weight:700;padding:4px 8px;border-radius:6px;background:#F97316;color:#0F172A">View details</span>
                                        <span class="pv-btn" style="font-size:10px;font-weight:700;padding:4px 8px;border-radius:6px;background:{{ ThemeColor::raised($current) }};color:{{ ThemeColor::text($current) }}">Search</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </fieldset>
                @endforeach
            </div>
            <div style="display:flex;justify-content:flex-end;margin-top:18px">
                <button type="submit" class="vp-btn vp-btn-o"><i class="fa-solid fa-palette"></i>Save Colours</button>
            </div>
        </form>
    </section>

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

        // ---- Colours: live preview (same rules as App\Support\ThemeColor) ----
        function lum(hex) {
            const c = [1, 3, 5].map(i => parseInt(hex.substr(i, 2), 16) / 255)
                .map(v => v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4));
            return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
        }
        function shade(hex, amt) {
            const t = amt >= 0 ? 255 : 0; amt = Math.abs(amt);
            return '#' + [1, 3, 5].map(i => {
                const c = parseInt(hex.substr(i, 2), 16);
                return Math.round(c + (t - c) * amt).toString(16).padStart(2, '0');
            }).join('').toUpperCase();
        }
        function themeOf(hex) {
            const light = lum(hex) > 0.18;
            return { text: light ? '#0F172A' : '#FFFFFF', muted: light ? '#475569' : '#CBD5E1', raised: light ? shade(hex, -0.06) : shade(hex, 0.1) };
        }
        function applyColor(input) {
            const hex = input.value.toUpperCase();
            const t = themeOf(hex);
            const pv = document.getElementById(input.dataset.preview);
            pv.querySelector('.pv-bg').style.background = hex;
            pv.querySelectorAll('.pv-text').forEach(el => el.style.color = pv.dataset.kind === 'panel' ? t.muted : t.text);
            const chip = pv.querySelector('.pv-chip'); if (chip) chip.style.background = t.raised;
            const btn = pv.querySelector('.pv-btn'); if (btn) { btn.style.background = t.raised; btn.style.color = t.text; }
            document.querySelector('.js-hex[data-for="' + input.id + '"]').textContent = hex;
            if (input.id === 'panel_color') {
                // Preview on the real sidebar too (saved only with "Save Colours").
                const side = document.querySelector('.vp-side'), light = lum(hex) > 0.18;
                side.style.setProperty('--vp-side-bg', hex);
                side.style.setProperty('--vp-side-text', t.text);
                side.style.setProperty('--vp-side-muted', t.muted);
                side.style.setProperty('--vp-side-muted2', light ? '#475569' : '#94A3B8');
                side.style.setProperty('--vp-side-raised', t.raised);
                side.style.setProperty('--vp-side-tag', light ? '#9A3412' : '#FDBA74');
                const logo = side.querySelector('.vp-side-logo');
                logo.src = light ? logo.dataset.logoLightBg : logo.dataset.logoDarkBg;
            }
            document.querySelectorAll('.js-swatch[data-target="' + input.id + '"]').forEach(s => {
                s.style.outline = s.dataset.color === hex ? '3px solid #F97316' : 'none';
                s.style.outlineOffset = '2px';
                s.setAttribute('aria-pressed', s.dataset.color === hex ? 'true' : 'false');
            });
        }
        document.querySelectorAll('.js-color').forEach(input => input.addEventListener('input', () => applyColor(input)));
        document.querySelectorAll('.js-swatch').forEach(sw => sw.addEventListener('click', () => {
            const input = document.getElementById(sw.dataset.target);
            input.value = sw.dataset.color.toLowerCase();
            applyColor(input);
        }));
    </script>
@endsection

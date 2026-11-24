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
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:16px;padding:12px;border:1px solid #E5E7EB;border-radius:12px">
                    <span style="width:28px;font-size:14px;font-weight:800">{{ $loop->iteration }}</span>
                    @if ($s->media_type === 'video')
                        <video src="{{ $s->url }}" muted style="width:160px;height:64px;object-fit:cover;border-radius:10px;background:#0F172A"></video>
                    @else
                        <img src="{{ $s->url }}" alt="" class="vp-thumb" style="width:160px;height:64px">
                    @endif
                    <form method="POST" action="{{ route('vendor.website.slides.update', $s->id) }}" style="flex:1;min-width:200px;margin:0;display:flex;gap:8px">
                        @csrf
                        <label for="cap-{{ $s->id }}" class="sr-only">Caption</label>
                        <input id="cap-{{ $s->id }}" name="title" class="vp-input" value="{{ $s->title }}" placeholder="No caption" maxlength="120">
                        <button type="submit" class="vp-btn vp-btn-g vp-btn-sm">Save</button>
                    </form>
                    <span class="vp-badge" style="{{ $s->media_type === 'video' ? 'background:#DBEAFE;color:#1E40AF' : 'background:#F1F5F9;color:#334155' }}">{{ ucfirst($s->media_type) }}</span>
                    <form method="POST" action="{{ route('vendor.website.slides.update', $s->id) }}" style="margin:0">@csrf<input type="hidden" name="toggle" value="1">
                        <button type="submit" role="switch" aria-checked="{{ $s->is_active ? 'true' : 'false' }}" aria-label="Show on website" class="vp-switch {{ $s->is_active ? 'on' : '' }}"><span></span></button></form>
                    <form method="POST" action="{{ route('vendor.website.slides.update', $s->id) }}" style="margin:0">@csrf<input type="hidden" name="move" value="up">
                        <button type="submit" class="vp-ib" aria-label="Move up" @disabled($loop->first)><i class="fa-solid fa-arrow-up"></i></button></form>
                    <form method="POST" action="{{ route('vendor.website.slides.update', $s->id) }}" style="margin:0">@csrf<input type="hidden" name="move" value="down">
                        <button type="submit" class="vp-ib" aria-label="Move down" @disabled($loop->last)><i class="fa-solid fa-arrow-down"></i></button></form>
                    <form method="POST" action="{{ route('vendor.website.slides.destroy', $s->id) }}" style="margin:0" data-confirm="Remove this slide?" data-confirm-button="Yes, remove">@csrf
                        <button type="submit" class="vp-ib danger" aria-label="Delete slide"><i class="fa-regular fa-trash-can"></i></button></form>
                </div>
            @empty
                <p class="vp-muted" style="margin:0">No slides yet. Your website shows a plain banner with your company name until you add one.</p>
            @endforelse
        </div>
    </section>
@endsection

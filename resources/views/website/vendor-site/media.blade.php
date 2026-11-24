@extends('website.vendor-site.layout')
@section('title', collect([$media->hoarding_code ?: $media->media_code, $media->media_title])->filter()->implode(' · ') ?: 'Media')

@section('nav')
    @include('website.vendor-site.partials.nav', ['active' => 'media'])
@endsection

@section('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum/build/pannellum.css">
    <style>
        .vs-k { font-size: 12px; font-weight: 600; color: #475569; }
        .vs-v { font-size: 15px; font-weight: 700; margin-top: 4px; }
        .vs-thumb { height: 96px; border-radius: 12px; overflow: hidden; border: 3px solid transparent; padding: 0; cursor: pointer; background: #F1F5F9; }
        .vs-thumb.active { border-color: #F97316; }
        .vs-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    </style>
@endsection

@section('content')
    @php
        $code = $media->hoarding_code ?: $media->media_code;
        $detailRows = [
            'Hoarding Code' => $code ?: '—',
            'Size' => $media->width && $media->height ? (float) $media->width . ' × ' . (float) $media->height . ' ft' . ($media->area_auto ? ' (' . (float) $media->area_auto . ' sq.ft)' : '') : '—',
            'Illumination' => $media->illumination_name ?: '—',
            'Facing' => $media->facing ?: '—',
            'District / City' => trim(($media->district_name ?? '') . ' / ' . ($media->city_name ?? ''), ' /'),
            'Area' => $media->area_name ?: '—',
            'Area Type' => $media->areatype_name ?: '—',
            'Landmark' => $landmarks->implode(', ') ?: '—',
        ];
    @endphp
    <main class="vs-wrap" style="padding-top:24px;padding-bottom:56px;display:flex;flex-direction:column;gap:24px">
        <div style="font-size:14px;color:#475569"><a href="{{ route('vendor-site.show', $vendor->slug) }}#inventory" style="font-weight:700">Inventory</a> / {{ $media->media_title }}</div>

        <div style="display:flex;flex-wrap:wrap;gap:28px;align-items:flex-start">
            <div style="flex:999 1 600px;min-width:0;display:flex;flex-direction:column;gap:12px">
                <div style="position:relative">
                    @if ($images->isNotEmpty())
                        <img id="mainPhoto" src="{{ \App\Support\MediaImageUrl::for($images->first()->images) }}" alt="{{ $media->media_title }}" style="width:100%;height:460px;object-fit:cover;border-radius:12px;display:block">
                    @else
                        <div class="vs-ph" style="height:460px;border-radius:12px">No photo yet</div>
                    @endif
                    @if ($media->panorama_image)
                        <button type="button" id="open360" class="vs-btn vs-btn-n" style="position:absolute;right:16px;bottom:16px"><i class="fa-solid fa-street-view"></i>View 360°</button>
                    @endif
                </div>
                @if ($images->count() > 1)
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px">
                        @foreach ($images as $img)
                            <button type="button" class="vs-thumb {{ $loop->first ? 'active' : '' }}" data-src="{{ \App\Support\MediaImageUrl::for($img->images) }}" aria-label="Photo {{ $loop->iteration }}">
                                <img src="{{ \App\Support\MediaImageUrl::for($img->images) }}" alt="" loading="lazy"></button>
                        @endforeach
                    </div>
                @endif
                @if ($media->panorama_image)
                    <div id="pano" style="display:none;height:420px;border-radius:12px;overflow:hidden"></div>
                @endif
            </div>

            <aside style="flex:1 1 380px;background:#fff;border:1px solid #E5E7EB;border-radius:16px;padding:24px;display:flex;flex-direction:column;gap:14px">
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <span class="vs-badge" style="background:#F1F5F9;color:#0F172A">{{ $media->category_name }}</span>
                    <span class="vs-badge" style="background:{{ $availability['bg'] }};color:{{ $availability['fg'] }}">{{ $availability['label'] }}{{ $availability['to'] ? ' till ' . \Carbon\Carbon::parse($availability['to'])->format('d M Y') : '' }}</span>
                </div>
                <h1 style="margin:0;font-size:28px;line-height:1.2;font-weight:800">{{ $media->media_title }}</h1>
                @if ($media->address)<div style="font-size:14px;color:#334155;line-height:1.6">{{ $media->address }}</div>@endif
                <div style="display:flex;align-items:baseline;gap:6px;padding-top:12px;border-top:1px solid #F1F5F9">
                    <span style="font-size:30px;font-weight:800">₹ {{ number_format((float) $media->price) }}</span><span style="font-size:14px;color:#475569">/ month</span></div>
                @if ($availableFrom)
                    <div style="padding:12px 14px;border-radius:10px;background:#DCFCE7;color:#166534;font-size:14px;font-weight:700">Available from {{ $availableFrom->format('d M Y') }}</div>
                @endif
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <button type="button" id="shareLink" class="vs-btn vs-btn-o" style="flex:1"><i class="fa-solid fa-share-nodes"></i>Share Link</button>
                    <a href="https://www.google.com/maps?q={{ $media->latitude }},{{ $media->longitude }}" target="_blank" rel="noopener" class="vs-btn vs-btn-g" style="flex:1">Open in Maps</a>
                </div>
                <a href="{{ route('vendor-site.contact', ['vendorSlug' => $vendor->slug, 'media' => $media->id]) }}" class="vs-btn vs-btn-n" style="width:100%">
                    <i class="fa-regular fa-paper-plane"></i>Enquire about this site</a>
            </aside>
        </div>

        <section style="background:#fff;border:1px solid #E5E7EB;border-radius:16px;padding:24px">
            <h2 style="margin:0 0 18px;font-size:20px;font-weight:800">Media Details</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px 24px">
                @foreach ($detailRows as $k => $v)
                    <div><div class="vs-k">{{ $k }}</div><div class="vs-v">{{ $v }}</div></div>
                @endforeach
            </div>
        </section>

        <section style="background:#fff;border:1px solid #E5E7EB;border-radius:16px;overflow:hidden">
            <div style="padding:18px 24px;font-size:20px;font-weight:800">Location</div>
            <div id="mediaMap" style="height:320px" role="region" aria-label="Map showing {{ $media->media_title }}"></div>
        </section>
    </main>
@endsection

@section('scripts')
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    @if ($media->panorama_image)<script src="https://cdn.jsdelivr.net/npm/pannellum/build/pannellum.js"></script>@endif
    <script>
        document.querySelectorAll('.vs-thumb').forEach(function (b) {
            b.addEventListener('click', function () {
                document.getElementById('mainPhoto').src = b.dataset.src;
                document.querySelectorAll('.vs-thumb').forEach(function (x) { x.classList.toggle('active', x === b); });
            });
        });

        const lat = {{ (float) $media->latitude }}, lng = {{ (float) $media->longitude }};
        const map = L.map('mediaMap', { scrollWheelZoom: false }).setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
        L.circleMarker([lat, lng], { radius: 10, color: '#fff', weight: 3, fillColor: '#F97316', fillOpacity: 1 }).addTo(map);

        document.getElementById('shareLink').addEventListener('click', function () {
            const btn = this;
            const url = window.location.href.split('#')[0];
            if (navigator.share) {
                navigator.share({ title: document.title, url: url }).catch(function () {});
            } else {
                navigator.clipboard.writeText(url).then(function () { btn.innerHTML = '<i class="fa-solid fa-check"></i>Link copied'; });
            }
        });

        @if ($media->panorama_image)
            document.getElementById('open360').addEventListener('click', function () {
                const pano = document.getElementById('pano');
                pano.style.display = 'block';
                pannellum.viewer('pano', { type: 'equirectangular', panorama: @json(\App\Support\MediaImageUrl::for($media->panorama_image)), autoLoad: true });
                pano.scrollIntoView({ behavior: 'smooth' });
            });
        @endif
    </script>
@endsection

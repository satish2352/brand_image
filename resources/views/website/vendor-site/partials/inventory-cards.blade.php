{{-- Media cards of the vendor website's inventory list. Rendered for the first page with the
     page, and on its own for each further page the list lazy-loads as it is scrolled. --}}
@php $ribbon = ['available' => '#0F172A', 'booked' => '#F97316', 'blocked' => '#DC2626', 'ask' => '#DC2626']; @endphp
@foreach ($items as $m)
    @php
        $detailsUrl = route('vendor-site.media', [$vendor->slug, $m->id]);
        $hasSize = (float) $m->width > 0 && (float) $m->height > 0;
        $st = $m->availability['status'];
        $hasPin = (float) $m->latitude && (float) $m->longitude;
        $panoramaUrl = !empty($m->panorama_image)
            ? url('panorama.html') . '?img=' . urlencode(\App\Support\MediaImageUrl::for($m->panorama_image))
            : null;
    @endphp
    <article class="vs-item" data-id="{{ $m->id }}" data-href="{{ $detailsUrl }}">
        <div class="vs-item-photo">
            @if (isset($covers[$m->id]))
                <img src="{{ \App\Support\MediaImageUrl::for($covers[$m->id]) }}" alt="{{ $m->media_title }}" loading="lazy">
            @else
                <div class="vs-ph" style="height:100%">No photo yet</div>
            @endif
            <span class="vs-ribbon {{ strlen($m->availability['label']) > 10 ? 'is-long' : '' }}" style="background:{{ $ribbon[$st] ?? '#0F172A' }}">{{ $m->availability['label'] }}</span>
            @if ($panoramaUrl)<span class="vs-360-badge" title="360° view available"><i class="fa-solid fa-street-view" aria-hidden="true"></i> 360°</span>@endif
        </div>
        <div class="vs-item-body">
            <h3 class="vs-item-title"><a href="{{ $detailsUrl }}" title="{{ $m->media_title }}">{{ $m->media_title ?: $m->category_name }}</a></h3>
            <div class="vs-item-code">{{ $m->hoarding_code ?: $m->media_code }} · {{ $m->category_name }}</div>
            <div class="vs-item-meta">
                @if ($hasSize)
                    <span><strong>Size :</strong> {{ number_format((float) $m->width, 2) }} x {{ number_format((float) $m->height, 2) }} ft</span>
                    <span><strong>Area :</strong> {{ number_format((float) $m->width * (float) $m->height, 2) }} SQFT</span>
                @endif
                @if ($m->illumination_name)<span><strong>Lighting :</strong> {{ $m->illumination_name }}</span>@endif
            </div>
            <div class="vs-item-meta" style="font-size:14px;color:#475569"><span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ collect([$m->area_name, $m->city_name])->filter()->unique()->implode(', ') ?: '—' }}</span></div>
            <div class="vs-item-price">₹ {{ number_format((float) $m->price, 2) }} <small>/ month</small></div>
            @if ($hasPin || $panoramaUrl)
                <div class="vs-item-links">
                    @if ($hasPin)
                        <button type="button" class="vs-onmap" data-map="{{ $m->id }}">
                            <img src="{{ asset('assets/img/105.png') }}" alt=""><span>View on Map</span></button>
                    @endif
                    @if ($panoramaUrl)
                        <a href="{{ $panoramaUrl }}" target="_blank" rel="noopener" class="vs-onmap vs-360">
                            <img src="{{ asset('assets/img/360view.png') }}" alt=""><span>360° View</span></a>
                    @endif
                </div>
            @endif
            <div class="vs-item-actions">
                <a href="{{ $detailsUrl }}" class="vs-read">Read More →</a>
                <a href="{{ route('vendor-site.contact', ['vendorSlug' => $vendor->slug, 'media' => $m->id]) }}#enquiry" class="vs-pill">Enquire Now</a>
            </div>
        </div>
    </article>
@endforeach

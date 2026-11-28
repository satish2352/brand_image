{{-- ================= MEDIA LOCATION INSIGHTS (panel body) =================
     Rendered by Website\MediaInsightsController@show and injected into
     #media-insights on the media details page (and the vendor website's media page)
     by AJAX. Kept outside website.* on purpose: the site-wide search-session composer
     that runs for website.* views has no business running for a fragment. $insights
     comes from MediaInsightsService::forDisplay() — MySQL only, never an API call.

     Written for the general public: plain titles, one short line per card, one
     disclaimer at the bottom, and only what is known (cards without data and
     weak sector matches are left out). Admins ($insightsAdmin) also see the empty
     cards, the status of every value and the Refresh button / budget line; the
     click handler lives in insights/assets, since injected scripts do not run.
     Own classes only (ins-*): the vendor website has no Bootstrap. Hooks used by
     the scripts / vendor-site CSS: .ins-tile, .ins-tile--wide, .ins-value--na,
     .ins-admin, .ins-more, #insightsShowAll, #insightsRefreshBtn, #insightsMsg. --}}
@php
    $p = $insights['points'];
    $places = $insights['places'];          // null = never fetched, [] = none found
    $radius = $insights['radius_m'] ?: 1000;
    $admin = (bool) $insightsAdmin;
    $statusText = [
        'estimated' => 'Estimated', 'incomplete' => 'Estimated · partial data', 'inferred' => 'Inferred',
        'rule_based' => 'Rule-based', 'available' => 'Available', 'tomtom' => 'TomTom', 'unavailable' => 'Not Available',
    ];
    $dist = fn($m) => $m === null ? '—' : ($m < 1000 ? $m . ' m' : number_format($m / 1000, 1) . ' km');
    $num = fn($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    $level = fn($v) => $v >= 7 ? ['High', 'ins-lvl--high'] : ($v >= 4 ? ['Medium', 'ins-lvl--mid'] : ['Low', 'ins-lvl--low']);

    // Score cards, in plain words. Cards without data are shown to admins only.
    $cards = [
        'visibility' => ['How visible', 'fas fa-eye', '#F97316', 'How easily people can see it: size, lighting and the road it faces.'],
        'premium'    => ['Area rating', 'fas fa-star', '#7C3AED', 'How busy and premium the area around it is.'],
        'traffic'    => ['Road traffic', 'fas fa-car-side', '#0EA5E9', 'Traffic on the nearest road right now.'],
        'footfall'   => ['People walking by', 'fas fa-walking', '#64748B', 'Pedestrian counts are not available yet.'],
    ];
    $hasValue = fn($k) => in_array($k, ['visibility', 'premium'], true) ? $p[$k]['value'] !== null : (bool) ($p[$k]['value'] ?? null);

    $audienceColour = ['Students' => '#2563EB', 'Family' => '#F97316', 'Corporate' => '#0F766E', 'Premium' => '#7C3AED', 'Rural' => '#65A30D', 'Other' => '#64748B'];
    $audienceName = ['Students' => 'Students', 'Family' => 'Families & shoppers', 'Corporate' => 'Office goers', 'Premium' => 'Premium buyers', 'Rural' => 'Rural community', 'Other' => 'General public'];

    // "Best suited for": good matches only for the public (all of them for admins).
    $sectorName = ['Political Campaign' => 'Political campaigns', 'Automobile' => 'Automobile brands', 'FMCG' => 'FMCG / daily-use products',
        'Retail Launch' => 'Retail & product launches', 'Real Estate' => 'Real estate projects'];
    $sectorIcon = ['Political Campaign' => 'fas fa-bullhorn', 'Automobile' => 'fas fa-car', 'FMCG' => 'fas fa-shopping-basket',
        'Retail Launch' => 'fas fa-store', 'Real Estate' => 'fas fa-building'];
    $recs = collect($p['recommendation']['value'] ?? []);
    $goodRecs = $admin ? $recs : $recs->filter(fn($r) => $r['fit'] !== 'Low fit')->values();
    if (!$admin && $goodRecs->isEmpty() && $recs->isNotEmpty()) {
        $goodRecs = $recs->take(1);   // always suggest at least the best one
    }
    $fitWord = ['Strong fit' => ['Great match', 'ins-fit--strong'], 'Moderate fit' => ['Good match', 'ins-fit--mid'], 'Low fit' => ['Weak match', 'ins-fit--low']];

    // Icon for a nearby place, from its category.
    $placeIcon = function ($cat) {
        $c = mb_strtolower((string) $cat);
        foreach ([
            'fas fa-shopping-bag' => ['mall', 'shop', 'market', 'store', 'supermarket'],
            'fas fa-graduation-cap' => ['school', 'college', 'university', 'academy', 'institute', 'education'],
            'fas fa-tree' => ['park', 'garden', 'playground'],
            'fas fa-hospital' => ['hospital', 'clinic', 'health', 'pharmacy'],
            'fas fa-place-of-worship' => ['temple', 'church', 'mosque', 'worship', 'religion'],
            'fas fa-university' => ['bank', 'atm', 'financial'],
            'fas fa-utensils' => ['restaurant', 'cafe', 'food', 'catering'],
            'fas fa-hotel' => ['hotel', 'resort', 'accommodation'],
            'fas fa-bus' => ['bus', 'station', 'transport', 'railway'],
            'fas fa-briefcase' => ['office', 'company', 'corporate', 'commercial'],
            'fas fa-film' => ['cinema', 'theatre', 'entertainment'],
        ] as $icon => $words) {
            foreach ($words as $w) {
                if (str_contains($c, $w)) return $icon;
            }
        }
        return 'fas fa-map-marker-alt';
    };
    $firstPlaces = 6;
@endphp

{{-- ================= HEADER ================= --}}
<div class="ins-head">
    <div>
        <h4 class="ins-title">Location Insights</h4>
        <p class="ins-sub">A quick look at this hoarding's location.</p>
    </div>
    @if ($insights['calculated_at'] || $insights['fetched_at'])
        <div class="ins-stamp"><i class="far fa-clock" aria-hidden="true"></i>
            Updated {{ ($insights['calculated_at'] ?? $insights['fetched_at'])->format('d M Y') }}</div>
    @endif
</div>

@if ($admin)
    <div class="ins-admin">
        <div>
            <strong>Admin:</strong>
            @if ($insightsQuota && !$insightsQuota['configured'])
                {{ ucfirst($insightsQuota['provider']) }} API key is not configured on the server.
            @elseif ($insightsQuota)
                {{ ucfirst($insightsQuota['provider']) }} credits used {{ $insightsQuota['period'] === 'day' ? 'today' : 'this month' }}:
                {{ $insightsQuota['used'] }} / {{ $insightsQuota['limit'] }} ({{ $insightsQuota['remaining'] }} left).
                @if ($insightsQuota['paused'])
                    <span class="ins-text-danger">Paused after the provider refused a request — saved data is shown.</span>
                @elseif ($insightsQuota['remaining'] < $insightsQuota['max_cost'])
                    <span class="ins-text-danger">Limit reached — saved data is shown.</span>
                @endif
            @endif
            @unless ($insights['has_location'])
                <span class="ins-text-danger">This media has no latitude/longitude.</span>
            @endunless
            @if ($insights['expired'])<span class="ins-text-danger">Nearby places are due for refresh.</span>@endif
            <div id="insightsMsg" class="mt-1" role="status" aria-live="polite"></div>
        </div>
        <button type="button" class="ins-refresh-btn" id="insightsRefreshBtn"
            data-url="{{ route('media.insights.refresh', base64_encode($mediaId)) }}">
            <i class="fas fa-sync-alt"></i> Refresh Insights
        </button>
    </div>
@endif

<div class="ins-grid">
    {{-- ================= SCORE CARDS (one row, as many as have data) ================= --}}
    <div class="ins-kpis">
    @foreach ($cards as $k => [$title, $icon, $colour, $line])
        @continue(!$admin && !$hasValue($k))
        @php $pt = $p[$k]; @endphp
        <div class="ins-tile ins-kpi">
            <div class="ins-kpi-name"><span class="ins-ico" style="--c: {{ $colour }}"><i class="{{ $icon }}" aria-hidden="true"></i></span>{{ $title }}</div>
            @if (in_array($k, ['visibility', 'premium'], true) && $pt['value'] !== null)
                @php [$word, $cls] = $level($pt['value']); @endphp
                <div class="ins-value">{{ $num($pt['value']) }}<small>/10</small><span class="ins-lvl {{ $cls }}">{{ $word }}</span></div>
                <div class="ins-meter" role="img" aria-label="{{ $num($pt['value']) }} out of 10">
                    @for ($i = 1; $i <= 10; $i++)<span class="{{ $pt['value'] >= $i - 0.5 ? 'on' : '' }}" style="--c: {{ $colour }}"></span>@endfor
                </div>
            @elseif ($k === 'traffic' && $pt['value'])
                <div class="ins-value ins-value--text">{{ str_replace(' traffic', '', $pt['value']) }}</div>
                @if (!empty($pt['details']['Speed now']))<div class="ins-meta">Average speed {{ $pt['details']['Speed now'] }}</div>@endif
            @else
                <div class="ins-value ins-value--na">Not Available</div>
            @endif
            <p class="ins-note">{{ $line }}</p>
            @if ($admin)<p class="ins-admin-note">{{ $statusText[$pt['status']] ?? $pt['status'] }} · {{ $pt['label'] }}. {{ $pt['note'] }}</p>@endif
        </div>
    @endforeach
    </div>

    {{-- ================= WHO SEES IT ================= --}}
    @if ($admin || $p['audience']['value'])
        <div class="ins-tile ins-half">
            <div class="ins-kpi-name"><span class="ins-ico" style="--c: #2563EB"><i class="fas fa-users" aria-hidden="true"></i></span>Who sees it</div>
            @if ($p['audience']['value'])
                <ul class="ins-shares">
                    @foreach ($p['audience']['value'] as $a)
                        @php $c = $audienceColour[$a['type']] ?? '#64748B'; @endphp
                        <li>
                            <div class="ins-share-row">
                                <span><i class="ins-dot" style="background: {{ $c }}"></i>{{ $audienceName[$a['type']] ?? $a['type'] }}</span>
                                @if (!empty($a['share']))<b>{{ $a['share'] }}%</b>@endif
                            </div>
                            @if (!empty($a['share']))
                                <div class="ins-track"><span style="width: {{ $a['share'] }}%; background: {{ $c }}"></span></div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="ins-value ins-value--na">Not Available</div>
            @endif
            <p class="ins-note">Based on the kinds of places around this hoarding.</p>
        </div>
    @endif

    {{-- ================= BEST SUITED FOR ================= --}}
    @if ($admin || $goodRecs->isNotEmpty())
        <div class="ins-tile ins-half">
            <div class="ins-kpi-name"><span class="ins-ico" style="--c: #16A34A"><i class="fas fa-bullseye" aria-hidden="true"></i></span>Best suited for</div>
            @forelse ($goodRecs as $r)
                @php [$fw, $fc] = $fitWord[$r['fit']] ?? ['', 'ins-fit--low']; @endphp
                <div class="ins-sector">
                    <span class="ins-sector-name"><i class="{{ $sectorIcon[$r['sector']] ?? 'fas fa-check' }}" aria-hidden="true"></i>{{ $sectorName[$r['sector']] ?? ($r['label'] ?? $r['sector']) }}</span>
                    <span class="ins-fit {{ $fc }}">{{ $fw }}</span>
                </div>
            @empty
                <div class="ins-value ins-value--na">Not Available</div>
            @endforelse
            <p class="ins-note">Suggested from the area and the hoarding's details.</p>
        </div>
    @endif

    {{-- ================= WHAT'S NEARBY ================= --}}
    <div class="ins-tile ins-tile--wide">
        <div class="ins-label">
            <span class="ins-kpi-name"><span class="ins-ico" style="--c: #DC2626"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></span>
                <span>What's nearby <span class="ins-sub-inline">within {{ $radius >= 1000 ? $num($radius / 1000) . ' km' : $radius . ' m' }}</span></span></span>
        </div>

        @if ($places === null)
            <div class="ins-value ins-value--na">Not Available</div>
            <p class="ins-note">Nearby places have not been fetched for this location yet.</p>
        @elseif (count($places) === 0)
            <div class="ins-empty">No nearby places found within {{ $radius }} m of this media.</div>
        @else
            <ul class="ins-places">
                @foreach ($places as $i => $pl)
                    <li class="{{ $i >= $firstPlaces ? 'ins-more d-none' : '' }}" @if (!empty($pl['address'])) title="{{ $pl['address'] }}" @endif>
                        <span class="ins-place-ico"><i class="{{ $placeIcon($pl['category'] ?? '') }}" aria-hidden="true"></i></span>
                        <span class="ins-place-main">
                            <span class="ins-place-name">{{ $pl['title'] ?? 'Unnamed ' . mb_strtolower($pl['category'] ?? 'place') }}</span>
                            <span class="ins-place-cat">{{ $pl['category'] ?? '' }}</span>
                        </span>
                        <span class="ins-place-dist">{{ $dist($pl['distance_m'] ?? null) }}</span>
                    </li>
                @endforeach
            </ul>
            @if (count($places) > $firstPlaces)
                <button type="button" class="ins-link" id="insightsShowAll">Show all {{ count($places) }} places <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
            @endif
        @endif

        @if ($p['landmarks']['tagged'])
            <div class="ins-tagged">
                <span class="ins-tagged-label">Also close to</span>
                @foreach ($p['landmarks']['tagged'] as $t)
                    <span class="ins-chip">{{ $t }}</span>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- One disclaimer for the whole section, instead of a label on every card. --}}
<p class="ins-foot"><i class="fas fa-info-circle" aria-hidden="true"></i>
    Scores are estimates worked out from the hoarding's details and the places around it
    @if (is_array($places) && count($places)) (map data: OpenStreetMap; distances in a straight line)@endif.
    They are not measured audience numbers.</p>

{{-- The provider attribution line ("Powered by Geoapify") is not shown on request.
     $insights['attribution'] still carries it if a provider's plan needs it back. --}}

{{-- ================= MEDIA LOCATION INSIGHTS (panel body) =================
     Rendered by Website\MediaInsightsController@show and injected into
     #media-insights on the media details page (and the vendor website's media page)
     by AJAX. Kept outside website.* on purpose: the site-wide search-session composer
     that runs for website.* views has no business running for a fragment. $insights
     comes from MediaInsightsService::forDisplay() — MySQL only, never an API call.
     Every value carries its status:
       estimated / incomplete  calculated from our data, labelled "Estimated"
       inferred / rule_based   derived by documented rules, labelled as such
       unavailable             no reliable source — shows "Not Available"
     $insightsAdmin shows the Refresh button and budget line (admins only);
     its click handler lives in insights/assets, since injected scripts do not run.
     Own classes only (ins-*): the vendor website has no Bootstrap. Hooks used by
     the scripts / vendor-site CSS: .ins-tile, .ins-tile--wide, .ins-value--na,
     .ins-sector, .ins-admin, .ins-more, #insightsShowAll, #insightsRefreshBtn, #insightsMsg. --}}
@php
    $p = $insights['points'];
    $places = $insights['places'];          // null = never fetched, [] = none found
    $radius = $insights['radius_m'] ?: 1000;
    $badge = [
        'estimated'   => ['Estimated', 'ins-badge--est'],
        'incomplete'  => ['Estimated · partial data', 'ins-badge--part'],
        'inferred'    => ['Inferred', 'ins-badge--est'],
        'rule_based'  => ['Rule-based', 'ins-badge--est'],
        'available'   => ['Available', 'ins-badge--ok'],
        'tomtom'      => ['Live · TomTom', 'ins-badge--ok'],
        'unavailable' => ['Not Available', 'ins-badge--na'],
    ];
    $b = fn($status) => $badge[$status] ?? $badge['unavailable'];
    $dist = fn($m) => $m === null ? '—' : ($m < 1000 ? $m . ' m' : number_format($m / 1000, 2) . ' km');
    $num = fn($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    // A plain word beside a /10 score.
    $level = fn($v) => $v >= 7 ? ['High', 'ins-lvl--high'] : ($v >= 4 ? ['Medium', 'ins-lvl--mid'] : ['Low', 'ins-lvl--low']);
    $audienceColour = ['Students' => '#2563EB', 'Family' => '#F97316', 'Corporate' => '#0F766E', 'Premium' => '#7C3AED', 'Rural' => '#65A30D', 'Other' => '#64748B'];
    $fitClass = ['Strong fit' => 'ins-fit--strong', 'Moderate fit' => 'ins-fit--mid', 'Low fit' => 'ins-fit--low'];
    $kpis = [
        'traffic'    => ['fas fa-car-side', '#0EA5E9'],
        'visibility' => ['fas fa-eye', '#F97316'],
        'premium'    => ['fas fa-gem', '#7C3AED'],
        'footfall'   => ['fas fa-walking', '#64748B'],
    ];
@endphp

{{-- ================= HEADER ================= --}}
<div class="ins-head">
    <div>
        <h4 class="ins-title">Location Insights</h4>
        <p class="ins-sub">Estimates and rule-based indicators to help compare locations — not measured audience data.</p>
    </div>
    @if ($insights['calculated_at'] || $insights['fetched_at'])
        <div class="ins-stamp">
            <i class="far fa-clock" aria-hidden="true"></i>
            Updated {{ ($insights['calculated_at'] ?? $insights['fetched_at'])->format('d M Y, h:i A') }}
            @if ($insights['expired'])<span class="ins-badge ins-badge--part">Due for refresh</span>@endif
        </div>
    @endif
</div>

@if ($insightsAdmin)
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
            <div id="insightsMsg" class="mt-1" role="status" aria-live="polite"></div>
        </div>
        <button type="button" class="ins-refresh-btn" id="insightsRefreshBtn"
            data-url="{{ route('media.insights.refresh', base64_encode($mediaId)) }}">
            <i class="fas fa-sync-alt"></i> Refresh Insights
        </button>
    </div>
@endif

<div class="ins-grid">
    {{-- ================= KPI CARDS ================= --}}
    @foreach ($kpis as $k => [$icon, $colour])
        @php $pt = $p[$k]; $hasScore = in_array($k, ['visibility', 'premium'], true); @endphp
        <div class="ins-tile ins-kpi">
            <div class="ins-label">
                <span class="ins-kpi-name"><span class="ins-ico" style="--c: {{ $colour }}"><i class="{{ $icon }}" aria-hidden="true"></i></span>{{ $pt['label'] }}</span>
                <span class="ins-badge {{ $b($pt['status'])[1] }}">{{ $b($pt['status'])[0] }}</span>
            </div>
            @if ($hasScore && $pt['value'] !== null)
                @php [$word, $cls] = $level($pt['value']); @endphp
                <div class="ins-value">{{ $num($pt['value']) }} <small>/ 10</small> <span class="ins-lvl {{ $cls }}">{{ $word }}</span></div>
                <div class="ins-meter" role="img" aria-label="{{ $num($pt['value']) }} out of 10">
                    @for ($i = 1; $i <= 10; $i++)<span class="{{ $pt['value'] >= $i - 0.5 ? 'on' : '' }}" style="--c: {{ $colour }}"></span>@endfor
                </div>
            @elseif ($k === 'traffic' && $pt['value'])
                <div class="ins-value ins-value--text">{{ $pt['value'] }}</div>
                <dl class="ins-facts">
                    @foreach ($pt['details'] as $name => $val)<div><dt>{{ $name }}</dt><dd>{{ $val }}</dd></div>@endforeach
                </dl>
            @else
                <div class="ins-value ins-value--na">Not Available</div>
            @endif
            <p class="ins-note">{{ $pt['note'] }}</p>
        </div>
    @endforeach

    {{-- ================= AUDIENCE ================= --}}
    <div class="ins-tile ins-half">
        <div class="ins-label">
            <span class="ins-kpi-name"><span class="ins-ico" style="--c: #2563EB"><i class="fas fa-users" aria-hidden="true"></i></span>{{ $p['audience']['label'] }}</span>
            <span class="ins-badge {{ $b($p['audience']['status'])[1] }}">{{ $b($p['audience']['status'])[0] }}</span>
        </div>
        @if ($p['audience']['value'])
            <ul class="ins-shares">
                @foreach ($p['audience']['value'] as $a)
                    @php $c = $audienceColour[$a['type']] ?? '#64748B'; @endphp
                    <li title="{{ implode(', ', $a['examples'] ?? []) }}">
                        <div class="ins-share-row">
                            <span><i class="ins-dot" style="background: {{ $c }}"></i>{{ $a['type'] }}</span>
                            <b>{{ !empty($a['share']) ? $a['share'] . '%' : 'From area type' }}</b>
                        </div>
                        @if (!empty($a['share']))
                            <div class="ins-track"><span style="width: {{ $a['share'] }}%; background: {{ $c }}"></span></div>
                        @endif
                        @if (!empty($a['examples']))<div class="ins-eg">e.g. {{ implode(', ', $a['examples']) }}</div>@endif
                    </li>
                @endforeach
            </ul>
        @else
            <div class="ins-value ins-value--na">Not Available</div>
        @endif
        <p class="ins-note">{{ $p['audience']['note'] }}</p>
    </div>

    {{-- ================= RECOMMENDATIONS ================= --}}
    <div class="ins-tile ins-half">
        <div class="ins-label">
            <span class="ins-kpi-name"><span class="ins-ico" style="--c: #16A34A"><i class="fas fa-bullseye" aria-hidden="true"></i></span>{{ $p['recommendation']['label'] }}</span>
            <span class="ins-badge {{ $b($p['recommendation']['status'])[1] }}">{{ $b($p['recommendation']['status'])[0] }}</span>
        </div>
        @forelse ($p['recommendation']['value'] as $r)
            <div class="ins-sector" title="Based on {{ $r['basis'] }}">
                <div class="ins-sector-main">
                    <span class="ins-sector-name">{{ $r['label'] ?? $r['sector'] }}</span>
                    @isset($r['score'])<div class="ins-track ins-track--thin"><span class="{{ $fitClass[$r['fit']] ?? 'ins-fit--low' }}" style="width: {{ max(4, $r['score']) }}%"></span></div>@endisset
                </div>
                <span class="ins-fit {{ $fitClass[$r['fit']] ?? 'ins-fit--low' }}">{{ $r['fit'] }}</span>
            </div>
        @empty
            <div class="ins-value ins-value--na">Not Available</div>
        @endforelse
        <p class="ins-note">{{ $p['recommendation']['note'] }}</p>
    </div>

    {{-- ================= NEARBY LANDMARKS ================= --}}
    <div class="ins-tile ins-tile--wide">
        <div class="ins-label">
            <span class="ins-kpi-name"><span class="ins-ico" style="--c: #DC2626"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></span>
                <span>Nearby Landmarks <span class="ins-sub-inline">within {{ $radius }} m{{ is_array($places) && count($places) ? ' · ' . count($places) . ' found' : '' }}</span></span></span>
            <span class="ins-badge {{ $b($p['landmarks']['status'])[1] }}">{{ $b($p['landmarks']['status'])[0] }}</span>
        </div>

        @if ($places === null)
            <div class="ins-value ins-value--na">Not Available</div>
            <p class="ins-note">Nearby places have not been fetched for this location yet.</p>
        @elseif (count($places) === 0)
            <div class="ins-empty">No nearby places found within {{ $radius }} m of this media.</div>
        @else
            @php $firstRows = 10; @endphp
            <div class="ins-table-wrap">
                <table class="ins-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th class="ins-hide-sm">Category</th>
                            <th class="ins-hide-sm">Address</th>
                            <th class="ins-right">Distance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($places as $i => $pl)
                            <tr class="{{ $i >= $firstRows ? 'ins-more d-none' : '' }}">
                                <td class="ins-strong">{{ $pl['title'] ?? 'Unnamed ' . mb_strtolower($pl['category'] ?? 'place') }}
                                    <span class="ins-cat ins-show-sm">{{ $pl['category'] ?? '—' }}</span></td>
                                <td class="ins-hide-sm"><span class="ins-cat">{{ $pl['category'] ?? '—' }}</span></td>
                                <td class="ins-hide-sm ins-muted">{{ $pl['address'] ?? '—' }}</td>
                                <td class="ins-right ins-nowrap"><span class="ins-dist">{{ $dist($pl['distance_m'] ?? null) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if (count($places) > $firstRows)
                <button type="button" class="ins-link" id="insightsShowAll">Show all {{ count($places) }} places <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
            @endif
            <p class="ins-note">
                Source: {{ str_replace('Geoapify Places (OpenStreetMap data)', 'OpenStreetMap data', $p['landmarks']['note']) }}. Distances are straight-line from this media's location.
            </p>
        @endif

        @if ($p['landmarks']['tagged'])
            <div class="ins-tagged">
                <span class="ins-tagged-label">Landmarks tagged by Brand Adda</span>
                @foreach ($p['landmarks']['tagged'] as $t)
                    <span class="ins-chip"><i class="fas fa-tag" aria-hidden="true"></i> {{ $t }}</span>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- The provider attribution line ("Powered by Geoapify") is not shown on request.
     $insights['attribution'] still carries it if a provider's plan needs it back. --}}

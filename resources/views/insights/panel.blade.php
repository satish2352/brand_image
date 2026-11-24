{{-- ================= MEDIA LOCATION INSIGHTS (panel body) =================
     Rendered by Website\MediaInsightsController@show and injected into
     #media-insights on the media details page by AJAX. Kept outside
     website.* on purpose: the site-wide search-session composer that runs
     for website.* views has no business running for a fragment. $insights comes from
     MediaInsightsService::forDisplay() — MySQL only, never an API call.
     Every value carries its status:
       estimated / incomplete  calculated from our data, labelled "Estimated"
       inferred / rule_based   derived by documented rules, labelled as such
       unavailable             no reliable source — shows "Not Available"
     $insightsAdmin shows the Refresh button and budget line (admins only);
     its click handler lives on the page, since injected scripts do not run. --}}
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
        'tomtom'      => ['TomTom', 'ins-badge--ok'],
        'unavailable' => ['Not Available', 'ins-badge--na'],
    ];
    $b = fn($status) => $badge[$status] ?? $badge['unavailable'];
    $dist = fn($m) => $m === null ? '—' : ($m < 1000 ? $m . ' m' : number_format($m / 1000, 2) . ' km');
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h4 class="mb-1">Location Insights</h4>
        <div class="ins-note mt-0">
            Estimates and rule-based indicators to help compare locations — not measured audience data.
            @if ($insights['fetched_at'])
                Nearby places: {{ $insights['source'] }}, fetched {{ $insights['fetched_at']->format('d M Y') }}@if ($insights['expired']) (due for refresh)@endif.
            @endif
            @if ($insights['calculated_at'])
                Last updated {{ $insights['calculated_at']->format('d M Y, h:i A') }}.
            @endif
        </div>
    </div>
</div>

@if ($insightsAdmin)
    <div class="ins-admin d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <strong>Admin:</strong>
            @if ($insightsQuota && !$insightsQuota['configured'])
                {{ ucfirst($insightsQuota['provider']) }} API key is not configured on the server.
            @elseif ($insightsQuota)
                {{ ucfirst($insightsQuota['provider']) }} credits used {{ $insightsQuota['period'] === 'day' ? 'today' : 'this month' }}:
                {{ $insightsQuota['used'] }} / {{ $insightsQuota['limit'] }} ({{ $insightsQuota['remaining'] }} left).
                @if ($insightsQuota['paused'])
                    <span class="text-danger">Paused after the provider refused a request — saved data is shown.</span>
                @elseif ($insightsQuota['remaining'] < $insightsQuota['max_cost'])
                    <span class="text-danger">Limit reached — saved data is shown.</span>
                @endif
            @endif
            @unless ($insights['has_location'])
                <span class="text-danger">This media has no latitude/longitude.</span>
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
    {{-- The points shown, in order; Nearby Landmarks is a full-width table.
         Traffic is TomTom Traffic Flow from the database (a level, not a vehicle
         count); footfall has no verified source and always reads "Not Available".
         (Impressions and ROI / Value are still calculated but not shown.) --}}
    @foreach (['traffic', 'visibility', 'premium', 'audience', 'landmarks', 'recommendation', 'footfall'] as $k)
        @if ($k === 'traffic')
            <div class="ins-tile">
                <div class="ins-label">{{ $p['traffic']['label'] }}
                    <span class="ins-badge {{ $b($p['traffic']['status'])[1] }}">{{ $b($p['traffic']['status'])[0] }}</span>
                </div>
                @if ($p['traffic']['value'])
                    <div class="ins-value">{{ $p['traffic']['value'] }}</div>
                    @foreach ($p['traffic']['details'] as $name => $val)
                        <div class="ins-sector"><span>{{ $name }}</span><span>{{ $val }}</span></div>
                    @endforeach
                @else
                    <div class="ins-value ins-value--na">Not Available</div>
                @endif
                <div class="ins-note">{{ $p['traffic']['note'] }}</div>
            </div>
        @elseif ($k === 'footfall')
            <div class="ins-tile">
                <div class="ins-label">{{ $p[$k]['label'] }}
                    <span class="ins-badge {{ $b($p[$k]['status'])[1] }}">{{ $b($p[$k]['status'])[0] }}</span>
                </div>
                <div class="ins-value ins-value--na">Not Available</div>
                <div class="ins-note">{{ $p[$k]['note'] }}</div>
            </div>
        @elseif (in_array($k, ['visibility', 'premium'], true))
            <div class="ins-tile">
                <div class="ins-label">{{ $p[$k]['label'] }}
                    <span class="ins-badge {{ $b($p[$k]['status'])[1] }}">{{ $b($p[$k]['status'])[0] }}</span>
                </div>
                @if ($p[$k]['value'] !== null)
                    <div class="ins-value">{{ rtrim(rtrim(number_format($p[$k]['value'], 1), '0'), '.') }} <small>/ 10</small></div>
                    <div class="ins-bar"><span style="width: {{ $p[$k]['value'] * 10 }}%"></span></div>
                @else
                    <div class="ins-value ins-value--na">Not Available</div>
                @endif
                <div class="ins-note">{{ $p[$k]['note'] }}</div>
            </div>
        @elseif ($k === 'audience')
            <div class="ins-tile">
                <div class="ins-label">{{ $p['audience']['label'] }}
                    <span class="ins-badge {{ $b($p['audience']['status'])[1] }}">{{ $b($p['audience']['status'])[0] }}</span>
                </div>
                @forelse ($p['audience']['value'] as $a)
                    <span class="ins-chip" title="{{ implode(', ', $a['examples'] ?? []) }}">
                        {{ $a['type'] }}@if (!empty($a['share'])) · {{ $a['share'] }}%@endif
                    </span>
                @empty
                    <div class="ins-value ins-value--na">Not Available</div>
                @endforelse
                <div class="ins-note">{{ $p['audience']['note'] }}</div>
            </div>
        @elseif ($k === 'recommendation')
            <div class="ins-tile">
                <div class="ins-label">{{ $p['recommendation']['label'] }}
                    <span class="ins-badge {{ $b($p['recommendation']['status'])[1] }}">{{ $b($p['recommendation']['status'])[0] }}</span>
                </div>
                @forelse ($p['recommendation']['value'] as $r)
                    <div class="ins-sector" title="Based on {{ $r['basis'] }}">
                        <span>{{ $r['label'] ?? $r['sector'] }}</span>
                        <span class="ins-badge {{ $r['fit'] === 'Strong fit' ? 'ins-badge--ok' : ($r['fit'] === 'Moderate fit' ? 'ins-badge--est' : 'ins-badge--na') }}">{{ $r['fit'] }}</span>
                    </div>
                @empty
                    <div class="ins-value ins-value--na">Not Available</div>
                @endforelse
                <div class="ins-note">{{ $p['recommendation']['note'] }}</div>
            </div>
        @else
    {{-- nearby places / landmarks --}}
    <div class="ins-tile ins-tile--wide">
        <div class="ins-label">
            <span>Nearby Landmarks <span class="ins-note d-inline">(within {{ $radius }} m of this media)</span></span>
            <span class="ins-badge {{ $b($p['landmarks']['status'])[1] }}">{{ $b($p['landmarks']['status'])[0] }}</span>
        </div>

        @if ($places === null)
            <div class="ins-value ins-value--na">Not Available</div>
            <div class="ins-note">Nearby places have not been fetched for this location yet.</div>
        @elseif (count($places) === 0)
            <div class="ins-empty">No nearby places found within {{ $radius }} m of this media.</div>
        @else
            @php $firstRows = 10; @endphp
            <div class="table-responsive">
                <table class="ins-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th class="d-none d-md-table-cell">Address</th>
                            <th class="text-end">Distance</th>
                            <th class="d-none d-lg-table-cell">Lat, Lng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($places as $i => $pl)
                            <tr class="{{ $i >= $firstRows ? 'ins-more d-none' : '' }}">
                                <td>{{ $pl['title'] ?? 'Unnamed ' . mb_strtolower($pl['category'] ?? 'place') }}</td>
                                <td>{{ $pl['category'] ?? '—' }}</td>
                                <td class="d-none d-md-table-cell ins-muted">{{ $pl['address'] ?? '—' }}</td>
                                <td class="text-end text-nowrap">{{ $dist($pl['distance_m'] ?? null) }}</td>
                                <td class="d-none d-lg-table-cell ins-muted text-nowrap">
                                    @if (isset($pl['lat'], $pl['lng']))
                                        {{ number_format($pl['lat'], 5) }}, {{ number_format($pl['lng'], 5) }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if (count($places) > $firstRows)
                <button type="button" class="ins-link" id="insightsShowAll">Show all {{ count($places) }} places</button>
            @endif
            <div class="ins-note">
                Source: {{ $p['landmarks']['note'] }}. Distances are straight-line from this media's coordinates.
            </div>
        @endif

        @if ($p['landmarks']['tagged'])
            <div class="mt-3">
                <div class="ins-label mb-1">Landmarks tagged by Brand Adda</div>
                @foreach ($p['landmarks']['tagged'] as $t)
                    <span class="ins-chip">{{ $t }}</span>
                @endforeach
            </div>
        @endif
    </div>
        @endif
    @endforeach
</div>

@if (!empty($insights['attribution']))
    <div class="ins-attribution">
        <a href="{{ $insights['attribution']['url'] }}" target="_blank" rel="noopener">{{ $insights['attribution']['text'] }}</a>
    </div>
@endif

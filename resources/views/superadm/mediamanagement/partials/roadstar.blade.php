{{-- ================= ROADSTAR AUDIENCE =================
     $roadstar comes from RoadStarSyncService::forDisplay() — MySQL only; the
     buttons call our own admin routes (media/roadstar/*), never RoadStar
     directly, so no RoadStar credential reaches the browser. Only fields
     RoadStar's /sitedata returns are shown. --}}
@php
    $rs = $roadstar ?? ['found' => false];
    $rsEncodedId = !empty($rs['found']) ? base64_encode((string) $rs['media_id']) : null;
    $rsAudience = $rs['audience'] ?? null;
    $rsStatusLabels = [
        'pending'       => ['Not synced yet', 'secondary'],
        'queued'        => ['Queued', 'info'],
        'success'       => ['Synced', 'success'],
        'no_data'       => ['No data for period', 'warning'],
        'not_available' => ['Not registered in RoadStar', 'warning'],
        'failed'        => ['Failed', 'danger'],
    ];
    [$rsStatusText, $rsStatusClass] = $rsStatusLabels[$rs['sync_status'] ?? ''] ?? ['Not mapped', 'secondary'];
@endphp

@if (!empty($rs['found']))
    <div class="info-card" id="roadstarCard"
         data-status-url="{{ route('media.roadstar.status', $rsEncodedId) }}"
         data-map-url="{{ route('media.roadstar.map', $rsEncodedId) }}"
         data-register-url="{{ route('media.roadstar.register', $rsEncodedId) }}"
         data-sync-url="{{ route('media.roadstar.sync', $rsEncodedId) }}"
         data-test-url="{{ route('media.roadstar.test') }}">
        <div class="info-card-header">RoadStar Audience</div>
        <div class="info-card-body">

            @unless ($rs['configured'])
                <div class="alert alert-warning py-2">RoadStar is not configured on this server.</div>
            @endunless

            <div class="info-row">
                <div class="info-col">
                    <div class="info-label">RoadStar Site ID</div>
                    <div class="info-value">{{ $rs['roadstar_site_id'] ?: '-' }}</div>
                </div>
                <div class="info-col">
                    <div class="info-label">Sync Status</div>
                    <div class="info-value"><span class="badge bg-{{ $rsStatusClass }} badge-{{ $rsStatusClass }}">{{ $rsStatusText }}</span></div>
                </div>
            </div>

            <div class="info-row">
                <div class="info-col">
                    <div class="info-label">Last Synced</div>
                    <div class="info-value">{{ $rs['last_synced_at'] ?? '-' }}</div>
                </div>
                <div class="info-col">
                    <div class="info-label">Next Sync Due</div>
                    <div class="info-value">{{ $rs['roadstar_site_id'] ? ($rs['next_sync_due_at'] ?? 'Next scheduled run') : '-' }}</div>
                </div>
            </div>

            @if ($rs['sync_error'])
                <div class="small text-danger mb-3">{{ $rs['sync_error'] }}</div>
            @endif

            {{-- Mapping / actions --}}
            <div class="row g-2 align-items-end mb-3">
                <div class="col-md-5">
                    <label class="info-label" for="roadstarSiteId">RoadStar Site ID (e.g. {{ $rs['suggested_site_id'] }})</label>
                    <input type="text" id="roadstarSiteId" class="form-control" maxlength="100"
                           value="{{ $rs['roadstar_site_id'] }}" placeholder="{{ $rs['suggested_site_id'] }}">
                </div>
                <div class="col-md-7">
                    <button type="button" class="btn btn-outline-secondary btn-sm roadstar-action" data-action="map">Save Mapping</button>
                    @if (!$rs['roadstar_site_id'] && $rs['can_register'])
                        <button type="button" class="btn btn-outline-primary btn-sm roadstar-action" data-action="register"
                                title="Adds this hoarding to RoadStar (POST /addsite) with its latitude/longitude">Register in RoadStar</button>
                    @endif
                    @if ($rs['roadstar_site_id'])
                        <button type="button" class="btn btn-primary btn-sm roadstar-action" data-action="sync">Sync RoadStar Data</button>
                    @endif
                    <button type="button" class="btn btn-outline-info btn-sm roadstar-action" data-action="test">Test Connection</button>
                </div>
            </div>

            {{-- Latest stored audience (shared with the website Media Details page) --}}
            @if ($rsAudience)
                <hr>
                @include('roadstar.audience', ['audience' => $rsAudience])
            @elseif ($rs['roadstar_site_id'])
                <p class="text-muted mb-0">No RoadStar audience data stored yet.</p>
            @endif
        </div>
    </div>
@endif

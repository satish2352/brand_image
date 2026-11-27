@extends('superadm.layout.master')

@section('content')
    @php
        $svcColour = ['geoapify' => '#7C3AED', 'tomtom' => '#00929c', 'tomtom_traffic' => '#F97316'];
        $outcomeStyle = [
            'success' => ['#DCFCE7', '#166534'], 'cache_hit' => ['#E0F2FE', '#075985'], 'failed' => ['#FEE2E2', '#991B1B'],
            'quota_blocked' => ['#FEF3C7', '#92400E'], 'quota_exceeded' => ['#FEE2E2', '#991B1B'], 'disabled' => ['#F1F5F9', '#334155'],
            'not_configured' => ['#F1F5F9', '#334155'], 'missing_location' => ['#F1F5F9', '#334155'],
        ];
        $sourceLabel = ['scheduler' => 'Hourly run', 'viewer' => 'Page view', 'admin' => 'Super Admin', 'admin_batch' => 'Run now', 'job' => 'Hourly run'];
        $on = fn ($k) => $settings[$k] ? 'checked' : '';
    @endphp
    <style>
        .am-card { background: #fff; border-radius: 12px; box-shadow: 0 8px 20px rgba(0, 0, 0, .06); margin-bottom: 24px; overflow: hidden; }
        .am-head { background: #00929c; color: #fff; padding: 12px 20px; font-weight: 600; font-size: 16px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; }
        .am-body { padding: 20px; }
        .am-muted { color: #6b7280; font-size: 13px; }
        .am-svc { background: #fff; border-radius: 12px; box-shadow: 0 8px 20px rgba(0, 0, 0, .06); padding: 18px 20px; height: 100%; border-top: 4px solid var(--c); display: flex; flex-direction: column; gap: 10px; }
        .am-svc h5 { margin: 0; font-size: 16px; font-weight: 700; color: #111827; }
        .am-pill { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .am-big { font-size: 30px; font-weight: 700; line-height: 1; color: #111827; }
        .am-big small { font-size: 14px; color: #6b7280; font-weight: 500; }
        .am-bar { height: 8px; border-radius: 999px; background: #EEF2F7; overflow: hidden; }
        .am-bar i { display: block; height: 100%; border-radius: 999px; }
        .am-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; text-align: center; }
        .am-stats div { background: #F8FAFC; border-radius: 8px; padding: 6px 2px; font-size: 11px; color: #6b7280; }
        .am-stats b { display: block; font-size: 16px; color: #111827; }
        .am-label { font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px; display: block; }
        .am-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 0; border-top: 1px solid #F1F5F9; }
        .am-row:first-child { border-top: 0; padding-top: 0; }
        .am-row strong { font-size: 14px; color: #111827; } .am-row .am-muted { font-size: 12px; }
        .am-switch { position: relative; width: 46px; height: 26px; flex: none; }
        .am-switch input { opacity: 0; width: 0; height: 0; position: absolute; }
        .am-switch span { position: absolute; inset: 0; border-radius: 999px; background: #CBD5E1; cursor: pointer; transition: background .15s; }
        .am-switch span::before { content: ''; position: absolute; width: 20px; height: 20px; left: 3px; top: 3px; border-radius: 50%; background: #fff; transition: transform .15s; box-shadow: 0 1px 3px rgba(0,0,0,.25); }
        .am-switch input:checked + span { background: #00929c; }
        .am-switch input:checked + span::before { transform: translateX(20px); }
        .am-switch input:focus-visible + span { outline: 3px solid #F97316; outline-offset: 2px; }
        .am-limit { display: grid; grid-template-columns: 1fr 120px; gap: 10px; align-items: center; padding: 8px 0; }
        .am-btn { background: #00929c; border: 1px solid #00929c; color: #fff !important; font-weight: 600; }
        .am-btn:hover { background: #007a82; border-color: #007a82; }
        .am-table thead th { background: #f8fafc; color: #374151; font-weight: 600; font-size: 13px; border-bottom: 1px solid #e5e7eb; border-top: 0; white-space: nowrap; }
        .am-table td { vertical-align: middle; border-top: 1px solid #f1f5f9; font-size: 13px; }
        .am-pager .page-item.active .page-link { background: #00929c; border-color: #00929c; color: #fff; }
        .am-pager .page-link { color: #00929c; }
        .am-reset { display: inline-flex !important; width: auto !important; align-items: center; gap: 4px; padding: 4px 12px; border: 1px solid #CBD5E1; border-radius: 4px; color: #475569 !important; background: #fff; font-size: 13px; font-weight: 600; text-decoration: none !important; }
        .am-reset:hover { background: #F1F5F9; }
        .am-run { display: grid; grid-template-columns: 1fr 90px auto; gap: 8px; align-items: end; padding: 12px 0; border-top: 1px solid #F1F5F9; }
        .am-run:first-of-type { border-top: 0; padding-top: 0; }
        .am-plbox { background: #F8FAFC; border: 1px solid #E5E7EB; border-radius: 10px; padding: 12px 14px; height: 100%; }
        .am-plbox > span { display: block; font-size: 12px; color: #6b7280; } .am-plbox b { display: block; font-size: 26px; color: #111827; line-height: 1.2; } .am-plbox small { color: #6b7280; }
        .am-plbox.bad { background: #FEF2F2; border-color: #FECACA; } .am-plbox.bad b { color: #B91C1C; }
        @media (max-width: 767px) { .am-stats { grid-template-columns: repeat(2, 1fr); } .am-run { grid-template-columns: 1fr 1fr; } .am-run > :first-child { grid-column: 1 / -1; } }
    </style>

    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-3" style="gap:10px">
            <div>
                <h4 class="mb-1" style="font-weight:600">API Request Manager</h4>
                <div class="am-muted">Geoapify and TomTom requests for Location Insights: what was used today, the limits and switches, and every request.</div>
            </div>
            @if ($updated)<div class="am-muted">Settings last saved {{ \Carbon\Carbon::parse($updated)->format('d M Y, h:i A') }}</div>@endif
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        {{-- ================= TODAY, PER SERVICE ================= --}}
        <div class="row">
            @foreach ($cards as $c)
                @php
                    $pct = $c['limit'] ? min(100, round($c['used'] / $c['limit'] * 100)) : 100;
                    $barColour = $pct >= 90 ? '#DC2626' : ($pct >= 70 ? '#D97706' : $svcColour[$c['key']]);
                    $isPlaces = $c['key'] !== 'tomtom_traffic';
                @endphp
                <div class="col-lg-4 mb-4">
                    <div class="am-svc" style="--c: {{ $svcColour[$c['key']] }}">
                        <div class="d-flex justify-content-between align-items-start" style="gap:8px">
                            <div>
                                <h5>{{ $c['name'] }}</h5>
                                <div class="am-muted" style="font-size:12px">
                                    {{ $c['key'] === 'tomtom_traffic' ? 'Road speed and congestion' : 'Nearby places and landmarks' }}
                                    @if ($isPlaces && $active === $c['key']) · <b style="color:#111827">used for places</b>@endif
                                </div>
                            </div>
                            @if (!$c['configured'])
                                <span class="am-pill" style="background:#FEE2E2;color:#991B1B">Key not set</span>
                            @elseif (!$c['enabled'])
                                <span class="am-pill" style="background:#F1F5F9;color:#334155">Off</span>
                            @elseif ($isPlaces && $active !== $c['key'])
                                <span class="am-pill" style="background:#F1F5F9;color:#334155">Standby</span>
                            @else
                                <span class="am-pill" style="background:#DCFCE7;color:#166534">On</span>
                            @endif
                        </div>
                        @php $free = \App\Support\ApiControl::FREE_LIMITS[$c['key']]; @endphp
                        <div style="background:#F0FDFA;border:1px solid #CCFBF1;border-radius:8px;padding:6px 10px;font-size:13px;color:#0F766E">
                            <b>Free plan:</b> {{ number_format($free['limit']) }} {{ $free['unit'] }} / day
                            <div class="am-muted" style="font-size:11px">{{ ucfirst($free['note']) }}</div>
                        </div>
                        @if ($c['limit'] > $free['limit'])
                            <div style="font-size:12px;color:#B45309">Your limit ({{ number_format($c['limit']) }}) is above the free plan. Requests past {{ number_format($free['limit']) }} may be billed or refused.</div>
                        @endif
                        <div class="am-big">{{ number_format($c['used']) }} <small>/ {{ number_format($c['limit']) }} {{ $c['key'] === 'geoapify' ? 'credits' : 'requests' }} today</small></div>
                        <div class="am-bar" role="img" aria-label="{{ $pct }}% of today's limit used"><i style="width:{{ $pct }}%;background:{{ $barColour }}"></i></div>
                        <div class="d-flex justify-content-between am-muted" style="font-size:12px">
                            <span>Remaining <b style="color:#111827">{{ number_format($c['remaining']) }}</b></span>
                            <span>Due {{ $isPlaces && $active !== $c['key'] ? '—' : number_format($c['due']) }}</span>
                        </div>
                        <div class="am-stats">
                            <div><b>{{ $c['success'] }}</b>Success</div>
                            <div><b>{{ $c['saved'] }}</b>Saved data</div>
                            <div><b style="{{ $c['failed'] ? 'color:#B91C1C' : '' }}">{{ $c['failed'] }}</b>Failed</div>
                            <div><b>{{ $c['blocked'] }}</b>Stopped</div>
                        </div>
                        <div class="am-muted" style="font-size:12px">Last request: {{ $c['last_at'] ? $c['last_at']->format('d M Y, h:i A') . ' · ' . $c['last_at']->diffForHumans() : 'none yet' }}</div>
                        @if ($c['paused'])
                            <form method="POST" action="{{ route('media.insights.usage.clear-pause') }}" class="d-flex align-items-center justify-content-between" style="gap:8px;background:#FEF3C7;border-radius:8px;padding:8px 10px">
                                @csrf <input type="hidden" name="service" value="{{ $c['key'] }}">
                                <span style="font-size:12px;color:#92400E">Paused for a few minutes: the provider refused a request (429).</span>
                                <button class="btn btn-sm btn-outline-dark">Lift pause</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- TomTom Places and Traffic share one plan --}}
        @php $sp = $shared['limit'] ? min(100, round($shared['used'] / $shared['limit'] * 100)) : 100; @endphp
        <div class="am-card">
            <div class="am-body d-flex flex-wrap align-items-center" style="gap:18px">
                <div style="flex:1 1 260px">
                    <strong style="color:#111827">TomTom plan today</strong>
                    <div class="am-muted">Places and Traffic use the same key, so together they stay under the plan.</div>
                    <div style="font-size:12px;color:#0F766E;margin-top:4px"><b>Free plan:</b> {{ number_format(\App\Support\ApiControl::FREE_LIMITS['tomtom']['limit']) }} requests / day, Places and Traffic together</div>
                </div>
                <div style="flex:2 1 360px">
                    <div class="d-flex justify-content-between am-muted mb-1" style="font-size:12px">
                        <span><b style="color:#111827">{{ number_format($shared['used']) }}</b> of {{ number_format($shared['limit']) }} requests</span>
                        <span>{{ number_format($shared['remaining']) }} left</span>
                    </div>
                    <div class="am-bar"><i style="width:{{ $sp }}%;background:{{ $sp >= 90 ? '#DC2626' : '#00929c' }}"></i></div>
                </div>
            </div>
        </div>

        {{-- ================= PLANNER ================= --}}
        @php $pn = $plan['now']; @endphp
        <div class="am-card" id="amPlanner">
            <div class="am-head">Free plan planner <span style="font-weight:400;font-size:13px">How many hoardings the free requests can keep fresh</span></div>
            <div class="am-body">
                <div class="row">
                    <div class="col-lg-5 mb-3">
                        <div class="am-muted mb-2">Try a number of hoardings and a refresh cycle. Each hoarding is fetched once per cycle, about the same number every day.</div>
                        <div class="d-flex flex-wrap mb-2" style="gap:6px">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-n="{{ $located }}">Today: {{ number_format($located) }}</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-n="7000">7,000</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-n="10000">10,000</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-n="15000">15,000</button>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-2"><label class="am-label" for="plN">Hoardings</label><input id="plN" type="number" min="1" class="form-control" value="{{ $located }}"></div>
                            <div class="col-6 mb-2"><label class="am-label" for="plD">Refresh every (days)</label><input id="plD" type="number" min="1" max="365" class="form-control" value="{{ $settings['traffic_refresh_days'] }}"></div>
                            <div class="col-6 mb-2"><label class="am-label" for="plP">Places per hoarding</label><select id="plP" class="form-control">
                                @foreach ($plan['choices'] as $c)<option value="{{ $c }}" @selected($settings['geoapify_places'] === $c)>{{ $c }} ({{ (int) ceil($c / 20) }} credit{{ $c > 20 ? 's' : '' }})</option>@endforeach</select></div>
                            <div class="col-6 mb-2"><label class="am-label" for="plF">Places come from</label><select id="plF" class="form-control">
                                <option value="geoapify" @selected($settings['places_provider'] === 'geoapify')>Geoapify</option>
                                <option value="tomtom" @selected($settings['places_provider'] === 'tomtom')>TomTom</option></select></div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="row" style="row-gap:12px">
                            <div class="col-sm-4"><div class="am-plbox"><span>Hoardings per day</span><b id="plPer">{{ number_format($pn['per_day']) }}</b><small>each one once in <span id="plDd">{{ $settings['traffic_refresh_days'] }}</span> days</small></div></div>
                            <div class="col-sm-4"><div class="am-plbox" id="plGeoBox"><span>Geoapify per day</span><b id="plGeo">{{ number_format($pn['geoapify_credits']) }}</b><small>credits of {{ number_format($plan['free']['geoapify']['limit']) }} free</small></div></div>
                            <div class="col-sm-4"><div class="am-plbox" id="plTtBox"><span>TomTom per day</span><b id="plTt">{{ number_format($pn['tomtom_requests']) }}</b><small>requests of {{ number_format($plan['free']['tomtom']['limit']) }} free</small></div></div>
                        </div>
                        <div id="plVerdict" class="mt-3" style="border-radius:10px;padding:12px 14px;font-size:14px"></div>
                        <div class="am-muted mt-2" style="font-size:12px">
                            Worst case: every Geoapify answer is full. Keep some room for pages opened by visitors and for Super Admin runs.
                            @if ($plan['room']['places'] !== null)
                                Today the hourly runs may still fetch <b>{{ number_format($plan['room']['places']) }}</b> for places and <b>{{ number_format($plan['room']['traffic']) }}</b> for traffic.
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- ================= CONTROLS ================= --}}
            <div class="col-xl-7">
                <form method="POST" action="{{ route('media.insights.usage.save') }}" class="am-card">
                    @csrf
                    <div class="am-head">Controls <span style="font-weight:400;font-size:13px">Saved settings apply to the next request</span></div>
                    <div class="am-body">
                        <div class="row">
                            <div class="col-md-6">
                                <span class="am-label">Switches</span>
                                @foreach ([
                                    ['geoapify_enabled', 'Geoapify Places', 'Off: no Geoapify requests; saved places are shown.'],
                                    ['tomtom_enabled', 'TomTom Places', 'Off: no TomTom place requests.'],
                                    ['tomtom_traffic_enabled', 'TomTom Traffic', 'Off: no traffic requests; saved traffic is shown.'],
                                    ['places_auto', 'Hourly places run', 'Fetches places for due hoardings every hour.'],
                                    ['traffic_auto', 'Hourly traffic run', 'Fetches traffic for due hoardings every hour.'],
                                    ['fetch_on_view', 'Fetch when a page is opened', 'A hoarding with no places yet is fetched when someone opens it.'],
                                    ['spread_evenly', 'Spread evenly over the cycle', 'Hourly runs fetch about hoardings ÷ refresh days each day, not everything at once.'],
                                ] as [$k, $t, $help])
                                    <div class="am-row">
                                        <div><strong>{{ $t }}</strong><div class="am-muted">{{ $help }}</div></div>
                                        <label class="am-switch"><input type="checkbox" name="{{ $k }}" value="1" {{ $on($k) }} aria-label="{{ $t }}"><span></span></label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="col-md-6">
                                <label class="am-label" for="pp">Places come from</label>
                                <select id="pp" name="places_provider" class="form-control mb-3">
                                    <option value="geoapify" @selected($settings['places_provider'] === 'geoapify')>Geoapify</option>
                                    <option value="tomtom" @selected($settings['places_provider'] === 'tomtom')>TomTom</option>
                                </select>
                                <label class="am-label" for="gp">Geoapify places per hoarding</label>
                                <select id="gp" name="geoapify_places" class="form-control mb-3">
                                    @foreach ($plan['choices'] as $c)<option value="{{ $c }}" @selected($settings['geoapify_places'] === $c)>{{ $c }} places · up to {{ (int) ceil($c / 20) }} credit{{ $c > 20 ? 's' : '' }}</option>@endforeach
                                </select>
                                <span class="am-label">Daily limits</span>
                                @foreach ([
                                    ['geoapify_daily_limit', 'Geoapify (credits)', 'Free plan: 3,000'],
                                    ['tomtom_daily_limit', 'TomTom Places', 'Free plan: 2,500 (shared)'],
                                    ['tomtom_traffic_daily_limit', 'TomTom Traffic', 'Free plan: 2,500 (shared)'],
                                    ['tomtom_plan_daily_limit', 'TomTom plan, both together', 'Free plan: 2,500'],
                                ] as [$k, $t, $help])
                                    <div class="am-limit">
                                        <label for="{{ $k }}" style="margin:0;font-size:13px;color:#111827">{{ $t }}@if ($help)<div class="am-muted" style="font-size:11px">{{ $help }}</div>@endif</label>
                                        <input id="{{ $k }}" name="{{ $k }}" type="number" min="0" class="form-control" value="{{ old($k, $settings[$k]) }}" required>
                                    </div>
                                @endforeach
                                <span class="am-label mt-2">Reuse saved data</span>
                                <div class="am-limit"><label for="pcd" style="margin:0;font-size:13px;color:#111827">Places, for (days)</label>
                                    <input id="pcd" name="places_cache_days" type="number" min="1" max="365" class="form-control" value="{{ old('places_cache_days', $settings['places_cache_days']) }}" required></div>
                                <div class="am-limit"><label for="trd" style="margin:0;font-size:13px;color:#111827">Traffic, refresh every (days)</label>
                                    <input id="trd" name="traffic_refresh_days" type="number" min="1" max="365" class="form-control" value="{{ old('traffic_refresh_days', $settings['traffic_refresh_days']) }}" required></div>
                            </div>
                        </div>
                        <div class="text-right mt-3"><button class="btn am-btn"><i class="mdi mdi-content-save"></i> Save Settings</button></div>
                    </div>
                </form>
            </div>

            {{-- ================= RUN NOW ================= --}}
            <div class="col-xl-5">
                <div class="am-card">
                    <div class="am-head">Run now</div>
                    <div class="am-body">
                        <form method="POST" action="{{ route('media.insights.usage.run-places') }}" class="am-run">
                            @csrf
                            <div><strong style="font-size:14px">Places for due hoardings</strong><div class="am-muted" style="font-size:12px">{{ number_format(collect($cards)->firstWhere('key', $active)['due']) }} due · uses {{ ucfirst($active) }}</div></div>
                            <div><label class="am-label" for="np">How many</label><input id="np" name="count" type="number" min="1" max="{{ $runMax }}" value="10" class="form-control"></div>
                            <button class="btn am-btn" onclick="this.disabled=true;this.innerText='Running…';this.form.submit()">Run</button>
                        </form>
                        <form method="POST" action="{{ route('media.insights.usage.run-traffic') }}" class="am-run">
                            @csrf
                            <div><strong style="font-size:14px">Traffic for due hoardings</strong><div class="am-muted" style="font-size:12px">{{ number_format($cards[2]['due']) }} due · TomTom Traffic</div></div>
                            <div><label class="am-label" for="nt">How many</label><input id="nt" name="count" type="number" min="1" max="{{ $runMax }}" value="10" class="form-control"></div>
                            <button class="btn am-btn" onclick="this.disabled=true;this.innerText='Running…';this.form.submit()">Run</button>
                        </form>
                        <form method="POST" action="{{ route('media.insights.usage.run-one') }}" class="am-run" style="grid-template-columns:1fr 1fr auto">
                            @csrf
                            <div><label class="am-label" for="om">One hoarding</label><input id="om" name="media" class="form-control" placeholder="Code or ID, e.g. HD000034" value="{{ old('media') }}" required></div>
                            <div><label class="am-label" for="ow">Refresh</label><select id="ow" name="what" class="form-control">
                                <option value="both">Places and traffic</option><option value="places">Places only</option><option value="traffic">Traffic only</option></select></div>
                            <button class="btn am-btn" onclick="this.disabled=true;this.innerText='Running…';this.form.submit()">Run</button>
                        </form>
                        <p class="am-muted mb-0 mt-2" style="font-size:12px">At most {{ $runMax }} hoardings per run. Limits and switches still apply, and a fresh saved result costs nothing.</p>
                    </div>
                </div>
                <div class="am-card">
                    <div class="am-head">Coverage</div>
                    <div class="am-body">
                        @foreach ([['Active media', $coverage['media']], ['With nearby places', $coverage['places']], ['With traffic', $coverage['traffic']]] as [$t, $v])
                            <div class="am-row"><span style="font-size:14px">{{ $t }}</span><strong>{{ number_format($v) }}</strong></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= LAST 14 DAYS ================= --}}
        <div class="am-card">
            <div class="am-head">Requests, last 14 days <span style="font-weight:400;font-size:13px">Billed requests (Geoapify: credits)</span></div>
            <div class="am-body"><div style="height:260px"><canvas id="amChart" aria-label="Requests per day for each service" role="img"></canvas></div></div>
        </div>

        {{-- ================= REQUEST LOG ================= --}}
        <div class="am-card" id="amLog" style="scroll-margin-top:110px">
            <div class="am-head">Request log <span style="font-weight:400;font-size:13px">{{ number_format($logs->total()) }} {{ $logs->total() === 1 ? 'entry' : 'entries' }}{{ array_filter($filters) ? ' (filtered)' : '' }}</span></div>
            <form method="GET" action="{{ route('media.insights.usage') }}#amLog" class="am-body row" style="padding-bottom:6px">
                <div class="col-md-4 col-xl-2 mb-2"><label class="am-label" for="fs">Service</label>
                    <select id="fs" name="service" class="form-control"><option value="">All services</option>
                        @foreach (\App\Support\ApiControl::SERVICES as $k => $t)<option value="{{ $k }}" @selected(($filters['service'] ?? '') === $k)>{{ $t }}</option>@endforeach</select></div>
                <div class="col-md-4 col-xl-2 mb-2"><label class="am-label" for="fo">Outcome</label>
                    <select id="fo" name="outcome" class="form-control"><option value="">All outcomes</option>
                        @foreach ($outcomes as $k => $t)<option value="{{ $k }}" @selected(($filters['outcome'] ?? '') === $k)>{{ $t }}</option>@endforeach</select></div>
                <div class="col-md-4 col-xl-2 mb-2"><label class="am-label" for="fsrc">Started by</label>
                    <select id="fsrc" name="source" class="form-control"><option value="">All</option>
                        @foreach ($sources as $s)<option value="{{ $s }}" @selected(($filters['source'] ?? '') === $s)>{{ $sourceLabel[$s] ?? ucfirst($s) }}</option>@endforeach</select></div>
                <div class="col-md-4 col-xl-2 mb-2"><label class="am-label" for="ff">From date</label><input id="ff" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control"></div>
                <div class="col-md-4 col-xl-2 mb-2"><label class="am-label" for="ft">To date</label><input id="ft" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control"></div>
                <div class="col-md-4 col-xl-2 mb-2"><label class="am-label" for="fm">Media code</label><input id="fm" name="media" value="{{ $filters['media'] ?? '' }}" class="form-control" placeholder="e.g. HD000034"></div>
                <div class="col-12 mb-2 d-flex justify-content-end align-items-center" style="gap:8px">
                    @if (array_filter($filters))<a href="{{ route('media.insights.usage') }}#amLog" class="am-reset"><i class="mdi mdi-close"></i> Reset</a>@endif
                    <button class="btn am-btn btn-sm"><i class="mdi mdi-filter-variant"></i> Apply Filters</button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table am-table mb-0">
                    <thead>
                        <tr><th style="width:60px">Sr. No.</th><th>Date / Time</th><th>Service</th><th>Media</th><th>Started by</th><th>Outcome</th><th>HTTP</th><th>Results</th><th>Hits</th><th>Time</th><th>Note</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            @php [$obg, $ofg] = $outcomeStyle[$log->status] ?? ['#F1F5F9', '#334155']; @endphp
                            <tr>
                                <td class="am-muted">{{ $logs->firstItem() + $loop->index }}</td>
                                <td class="text-nowrap">{{ $log->requested_at?->format('d M Y') }}<div class="am-muted" style="font-size:12px">{{ $log->requested_at?->format('h:i:s A') }}</div></td>
                                <td class="text-nowrap"><span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:{{ $svcColour[$log->provider] ?? '#94A3B8' }};margin-right:6px"></span>{{ \App\Support\ApiControl::SERVICES[$log->provider] ?? ucfirst($log->provider) }}</td>
                                <td class="text-nowrap">
                                    @if ($log->media_id)
                                        <a href="{{ route('website.media-details', base64_encode($log->media_id)) }}" target="_blank" rel="noopener" style="color:#00929c;font-weight:600">{{ $codes[$log->media_id] ?? '#' . $log->media_id }}</a>
                                    @else<span class="am-muted">—</span>@endif
                                </td>
                                <td class="text-nowrap">{{ $sourceLabel[$log->source] ?? ucfirst((string) $log->source) }}</td>
                                <td class="text-nowrap"><span class="am-pill" style="background:{{ $obg }};color:{{ $ofg }}">{{ $outcomes[$log->status] ?? str_replace('_', ' ', ucfirst($log->status)) }}</span></td>
                                <td>{{ $log->http_status ?? '—' }}</td>
                                <td>{{ $log->result_count ?? '—' }}</td>
                                <td><b>{{ $log->credits_consumed }}</b></td>
                                <td class="text-nowrap">{{ $log->duration_ms !== null ? number_format($log->duration_ms) . ' ms' : '—' }}</td>
                                <td style="min-width:200px;max-width:340px;font-size:12px;color:#475569">{{ $log->error_message ?: '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-center am-muted" style="padding:40px">No requests match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center am-pager" style="gap:12px;padding:14px 20px">
                <form method="GET" action="{{ route('media.insights.usage') }}#amLog" class="d-flex align-items-center am-muted" style="gap:6px">
                    @foreach ($filters as $k => $v)@if ($v !== null && $v !== '')<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif @endforeach
                    Show <select name="per_page" class="form-control form-control-sm" style="width:auto" onchange="this.form.submit()">
                        @foreach ([10, 25, 50, 100] as $p)<option value="{{ $p }}" @selected($perPage === $p)>{{ $p }}</option>@endforeach</select> per page
                </form>
                <span class="am-muted">@if ($logs->total())Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }}@endif</span>
                {{ $logs->fragment('amLog')->onEachSide(1)->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>

    <script>
        (function () {
            const GEO = {{ $plan['free']['geoapify']['limit'] }}, TT = {{ $plan['free']['tomtom']['limit'] }};
            const $ = id => document.getElementById(id), fmt = n => n.toLocaleString('en-IN');
            function calc() {
                const n = Math.max(1, +$('plN').value || 1), d = Math.max(1, +$('plD').value || 1), cost = Math.ceil(+$('plP').value / 20), from = $('plF').value;
                const per = Math.ceil(n / d), geo = from === 'geoapify' ? per * cost : 0, tt = per * (from === 'tomtom' ? 2 : 1);
                const maxPer = Math.min(from === 'geoapify' ? Math.floor(GEO / cost) : Infinity, from === 'tomtom' ? Math.floor(TT / 2) : TT);
                const minDays = Math.max(1, Math.ceil(n / maxPer)), geoOk = geo <= GEO, ttOk = tt <= TT;
                $('plPer').textContent = fmt(per); $('plDd').textContent = d; $('plGeo').textContent = fmt(geo); $('plTt').textContent = fmt(tt);
                $('plGeoBox').classList.toggle('bad', !geoOk); $('plTtBox').classList.toggle('bad', !ttOk);
                const v = $('plVerdict');
                const head = Math.min(from === 'geoapify' ? GEO - geo : Infinity, TT - tt);
                if (geoOk && ttOk && head >= Math.min(from === 'geoapify' ? GEO : Infinity, TT) * 0.1) {
                    v.style.cssText += ';background:#ECFDF5;color:#065F46';
                    v.innerHTML = '<b>Fits the free plans.</b> ' + fmt(n) + ' hoardings, each refreshed every ' + d + ' days. Room left per day: about ' + fmt(head) + '.';
                } else if (geoOk && ttOk) {
                    v.style.cssText += ';background:#FFFBEB;color:#92400E';
                    v.innerHTML = '<b>Fits, but only just.</b> Only about ' + fmt(head) + ' requests a day are left for visitors and retries.'
                        + (from === 'tomtom' ? ' Taking places from <b>Geoapify</b> leaves TomTom for traffic only.' : ' Try ' + (minDays + 1) + ' days, or fewer places per hoarding.');
                } else {
                    const tips = [];
                    tips.push('refresh every <b>' + minDays + ' days</b> or more');
                    if (from === 'geoapify' && !geoOk) {
                        const fitCost = Math.floor(GEO / per);
                        if (fitCost >= 1) tips.push('ask for <b>' + (fitCost * 20) + ' places</b> per hoarding');
                    }
                    if (from === 'tomtom' && !ttOk) tips.push('take places from <b>Geoapify</b> (TomTom then only serves traffic)');
                    v.style.cssText += ';background:#FEF2F2;color:#991B1B';
                    v.innerHTML = '<b>Over the free plan.</b> To stay free: ' + tips.join(', or ') + '.';
                }
            }
            ['plN', 'plD', 'plP', 'plF'].forEach(id => $(id).addEventListener('input', calc));
            document.querySelectorAll('#amPlanner [data-n]').forEach(b => b.addEventListener('click', () => { $('plN').value = b.dataset.n; calc(); }));
            calc();
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            if (!window.Chart) return;
            const d = @json($chart), names = @json(\App\Support\ApiControl::SERVICES), colours = @json($svcColour);
            new Chart(document.getElementById('amChart'), {
                type: 'bar',
                data: { labels: d.labels, datasets: Object.keys(names).map(function (k) { return { label: names[k], data: d[k], backgroundColor: colours[k], borderRadius: 4, maxBarThickness: 26 }; }) },
                options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } },
                    scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } } }
            });
        })();
    </script>
@endsection

@extends('vendor.layout')
@section('title', 'Dashboard')
@section('crumb', 'Vendor Panel / Dashboard')
@section('heading', 'Dashboard')

@section('styles')
    <style>
        .db { display: flex; flex-direction: column; gap: 22px; }
        .db-grid { display: grid; gap: 22px; align-items: stretch; }
        .db-grid-2 { grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); }
        .db-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .db-grid-half { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        @media (max-width: 1200px) { .db-grid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 960px) { .db-grid-2, .db-grid-3, .db-grid-half { grid-template-columns: 1fr; } }

        /* ---------- welcome banner ---------- */
        .db-hero { position: relative; overflow: hidden; border-radius: 18px; padding: 26px 28px; color: #0F172A; border: 1px solid #FED7AA;
            background: linear-gradient(115deg, #FFF7ED 0%, #FFFFFF 55%, #F0F9FF 100%); display: flex; flex-wrap: wrap; gap: 20px; align-items: center; justify-content: space-between; }
        .db-hero::after { content: ''; position: absolute; right: -70px; top: -90px; width: 280px; height: 280px; border-radius: 50%;
            background: radial-gradient(circle, rgba(249,115,22,.16), rgba(249,115,22,0) 70%); pointer-events: none; }
        .db-hero-id { display: flex; gap: 18px; align-items: center; position: relative; z-index: 1; }
        .db-logo { width: 68px; height: 68px; border-radius: 16px; background: #fff; display: grid; place-items: center; overflow: hidden; flex: none;
            font-weight: 800; font-size: 22px; color: #9A3412; border: 1px solid #FED7AA; box-shadow: 0 6px 16px rgba(249,115,22,.12); }
        .db-logo img { width: 100%; height: 100%; object-fit: contain; }
        .db-hello { font-size: 13px; color: #C2410C; font-weight: 600; letter-spacing: .02em; }
        .db-name { font-size: 24px; font-weight: 800; margin: 2px 0 8px; color: #0F172A; }
        .db-chips { display: flex; flex-wrap: wrap; gap: 8px; }
        .db-chip { display: inline-flex; align-items: center; gap: 7px; padding: 5px 11px; border-radius: 999px; background: #fff;
            border: 1px solid #E5E7EB; font-size: 12px; text-decoration: none; }
        .vp-body .db-chip { color: #334155; }
        .vp-body a.db-chip:hover { color: #C2410C; border-color: #FDBA74; }
        .db-chip i { color: #F97316; }
        .db-hero-actions { display: flex; flex-wrap: wrap; gap: 10px; position: relative; z-index: 1; }
        .db-hero-id { min-width: 0; } .db-hero-id > div:last-child { min-width: 0; }
        .db-chip { min-width: 0; overflow-wrap: anywhere; }
        @media (max-width: 640px) {
            .db-hero { padding: 18px 16px; gap: 16px; }
            .db-hero-id { align-items: flex-start; gap: 14px; }
            .db-logo { width: 54px; height: 54px; border-radius: 14px; }
            .db-name { font-size: 21px; }
            .db-hero-actions { display: grid; grid-template-columns: 1fr 1fr; width: 100%; }
            .db-hero-actions .vp-btn { padding: 0 8px; }
            .db-hero-actions .vp-btn:first-child { grid-column: 1 / -1; }
        }

        /* ---------- KPI cards ---------- */
        .db-kpis { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 16px; }
        @media (max-width: 1280px) { .db-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 720px) { .db-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        .db-kpi { position: relative; display: flex; flex-direction: column; gap: 10px; padding: 18px 18px 16px; text-decoration: none !important; color: #0F172A;
            transition: transform .15s, box-shadow .15s; overflow: hidden; }
        .db-kpi::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--fg); }
        .vp-body .db-kpi, .vp-body .db-kpi:hover { color: #0F172A; }
        .db-kpi:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(15,23,42,.08); }
        .db-kpi-top { display: flex; justify-content: space-between; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; color: #475569; }
        .db-ico { width: 40px; height: 40px; border-radius: 11px; display: grid; place-items: center; flex: none; font-size: 17px; background: var(--bg); color: var(--fg); }
        .db-kpi-value { font-size: 32px; font-weight: 800; line-height: 1; }
        .db-kpi-sub { font-size: 12px; color: #64748B; display: flex; justify-content: space-between; gap: 8px; }
        .db-bar { height: 6px; border-radius: 999px; background: #F1F5F9; overflow: hidden; }
        .db-bar span { display: block; height: 100%; border-radius: 999px; background: var(--fg); }

        /* ---------- cards ---------- */
        .db-card { padding: 20px 22px; display: flex; flex-direction: column; min-width: 0; }
        .db-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 14px; }
        .db-card-head h2 { margin: 0; font-size: 16px; font-weight: 800; color: #0F172A; }
        .db-card-head p { margin: 3px 0 0; font-size: 12px; color: #64748B; }
        .db-link { font-size: 13px; font-weight: 700; white-space: nowrap; }
        .db-chart { position: relative; flex: 1; min-height: 260px; }
        .db-empty { flex: 1; display: grid; place-items: center; text-align: center; color: #94A3B8; font-size: 13px; padding: 30px 0; }
        .db-empty i { font-size: 28px; display: block; margin-bottom: 8px; color: #CBD5E1; }

        /* doughnut + legend */
        .db-donut-wrap { display: flex; flex-wrap: wrap; gap: 18px; align-items: center; flex: 1; }
        .db-donut { position: relative; width: 210px; height: 210px; margin: 0 auto; }
        .db-donut-center { position: absolute; inset: 0; display: grid; place-items: center; text-align: center; pointer-events: none; }
        .db-donut-center b { display: block; font-size: 30px; font-weight: 800; line-height: 1; }
        .db-donut-center span { font-size: 12px; color: #64748B; }
        .db-legend { flex: 1 1 170px; display: flex; flex-direction: column; gap: 10px; }
        .db-legend-row { display: flex; align-items: center; gap: 10px; font-size: 13px; }
        .db-legend-row i { width: 10px; height: 10px; border-radius: 3px; flex: none; }
        .db-legend-row span { flex: 1; color: #334155; }
        .db-legend-row b { font-weight: 800; }
        .db-legend-row em { font-style: normal; color: #94A3B8; font-size: 12px; min-width: 38px; text-align: right; }
        .db-occ { margin-top: 6px; padding: 12px 14px; border-radius: 12px; background: #F8FAFC; display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
        .db-occ b { font-size: 20px; color: #1D4ED8; }

        /* approval */
        .db-appr { display: flex; flex-direction: column; gap: 10px; }
        .db-appr a { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 12px; text-decoration: none !important; border: 1px solid #F1F5F9; color: #0F172A; }
        .db-appr a:hover { border-color: #E2E8F0; background: #F8FAFC; }
        .db-appr .db-ico { width: 36px; height: 36px; font-size: 15px; }
        .db-appr span:not(.db-ico) { flex: 1; font-size: 14px; font-weight: 600; }
        .db-appr b { font-size: 20px; font-weight: 800; }
        .db-reject { margin-top: 12px; padding: 12px 14px; border-radius: 12px; background: #FEF2F2; border: 1px solid #FECACA; font-size: 13px; color: #7F1D1D; line-height: 1.5; }

        /* operations pipeline */
        .db-pipe { display: flex; flex-direction: column; }
        .db-pipe a { position: relative; display: flex; align-items: center; gap: 12px; padding: 9px 0; text-decoration: none !important; color: #0F172A; }
        .db-pipe a:not(:last-child)::after { content: ''; position: absolute; left: 15px; top: 40px; bottom: -9px; width: 2px; background: #E2E8F0; }
        .db-step { width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; flex: none; font-size: 13px; font-weight: 800; position: relative; z-index: 1; }
        .db-pipe span.l { flex: 1; font-size: 14px; font-weight: 600; }
        .db-pipe small { display: block; font-size: 12px; color: #94A3B8; font-weight: 500; }
        .db-pipe b { min-width: 36px; text-align: center; padding: 4px 10px; border-radius: 999px; background: #F1F5F9; font-size: 14px; }

        /* lists */
        .db-row { display: flex; gap: 12px; align-items: center; padding: 12px 0; border-top: 1px solid #F1F5F9; }
        .db-row:first-child { border-top: 0; padding-top: 4px; }
        .db-row-main { flex: 1; min-width: 0; }
        .db-row-title { font-size: 14px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .db-row-sub { font-size: 12px; color: #64748B; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .db-avatar { width: 38px; height: 38px; border-radius: 50%; display: grid; place-items: center; flex: none; font-weight: 800; font-size: 13px; background: #FFF7ED; color: #C2410C; }
    </style>
@endsection

@section('content')
    @php
        $total = max(0, (int) $status['total']);
        $pct = fn ($n) => $total > 0 ? round($n * 100 / $total) : 0;
        // Total Media counts every media of the vendor (approved, pending and rejected), like
        // Media Management; the status tiles after it cover approved media, which can be booked.
        $totalHint = collect(['approved' => 'approved', 'pending' => 'pending', 'rejected' => 'rejected'])
            ->filter(fn ($l, $k) => ($approval[$k] ?? 0) > 0)
            ->map(fn ($l, $k) => $approval[$k] . ' ' . $l)
            ->implode(' · ') ?: 'No media yet';
        $tiles = [
            // label, value, hint, colour, tint, icon, link
            ['Total Media', $approval['all'], $totalHint, '#EA580C', '#FFF7ED', 'fa-solid fa-rectangle-ad', route('vendor.inventory')],
            // Waiting for Brand Adda's approval; its bar is its share of all media (8th item = own percentage).
            ['Pending Approval', $approval['pending'], 'Awaiting Brand Adda', '#C2410C', '#FFEDD5', 'fa-solid fa-hourglass-half',
                route('vendor.inventory', ['approval' => 'pending']), $approval['all'] ? round($approval['pending'] * 100 / $approval['all']) : 0],
            ['Available', $status['available'], 'Open for booking', '#16A34A', '#DCFCE7', 'fa-solid fa-circle-check', route('vendor.availability')],
            ['Booked', $status['booked'], 'Active booking', '#2563EB', '#DBEAFE', 'fa-solid fa-calendar-check', route('vendor.bookings')],
            ['Blocked', $status['blocked'], 'Held for dates', '#DC2626', '#FEE2E2', 'fa-solid fa-ban', route('vendor.availability')],
            ['Ask for Availability', $status['ask'], 'Confirm on enquiry', '#D97706', '#FEF3C7', 'fa-solid fa-circle-question', route('vendor.availability')],
        ];
        $statusChart = [
            'labels' => ['Available', 'Booked', 'Blocked', 'Ask for Availability'],
            'data'   => [(int) $status['available'], (int) $status['booked'], (int) $status['blocked'], (int) $status['ask']],
            'colors' => ['#16A34A', '#2563EB', '#DC2626', '#D97706'],
        ];
        $stageStyle = [
            'pending'      => ['No action yet', '#F1F5F9', '#475569'],
            'installation' => ['Photos due', '#FFEDD5', '#C2410C'],
            'monitoring'   => ['Running', '#DBEAFE', '#1D4ED8'],
            'end_date'     => ['End date reached', '#FEF3C7', '#B45309'],
            'completed'    => ['Removed', '#DCFCE7', '#15803D'],
        ];
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    @endphp

    <div class="db">
        {{-- ================= WELCOME ================= --}}
        <section class="db-hero">
            <div class="db-hero-id">
                <div class="db-logo">
                    @if ($vendor->logo_url)
                        <img src="{{ $vendor->logo_url }}" alt="{{ $vendor->display_name }} logo">
                    @else
                        {{ $vendor->initials }}
                    @endif
                </div>
                <div>
                    <div class="db-hello">{{ $greeting }} · {{ now()->format('l, d M Y') }}</div>
                    <div class="db-name">{{ $vendor->display_name }}</div>
                    <div class="db-chips">
                        <span class="db-chip"><i class="fa-regular fa-id-badge"></i>{{ $vendor->vendor_code }}</span>
                        <a class="db-chip" href="{{ $vendor->website_url }}" target="_blank" rel="noopener"><i class="fa-solid fa-globe"></i>{{ preg_replace('#^https?://#', '', $vendor->website_url) }}</a>
                        <span class="db-chip"><i class="fa-regular fa-calendar"></i>{{ $bookingCounts['running'] }} running · {{ $bookingCounts['upcoming'] }} upcoming bookings</span>
                    </div>
                </div>
            </div>
            <div class="db-hero-actions">
                <a href="{{ route('vendor.media.create') }}" class="vp-btn vp-btn-o"><i class="fa-solid fa-plus"></i>Add Media</a>
                <a href="{{ route('vendor.availability') }}" class="vp-btn vp-btn-g"><i class="fa-regular fa-calendar"></i>Update Availability</a>
                <a href="{{ route('vendor.inventory.export') }}" class="vp-btn vp-btn-g"><i class="fa-solid fa-download"></i>Export</a>
            </div>
        </section>

        {{-- ================= KPIs ================= --}}
        <section class="db-kpis" aria-label="Inventory summary">
            @foreach ($tiles as $tile)
                @php
                    [$label, $value, $sub, $fg, $bg, $icon, $href] = $tile;
                    $share = $tile[7] ?? ($loop->first ? null : $pct($value)); // own % if given, else share of approved media
                @endphp
                <a href="{{ $href }}" class="vp-card db-kpi" style="--fg:{{ $fg }};--bg:{{ $bg }}">
                    <div class="db-kpi-top"><span>{{ $label }}</span><span class="db-ico" aria-hidden="true"><i class="{{ $icon }}"></i></span></div>
                    <div class="db-kpi-value">{{ $value }}</div>
                    <div class="db-bar" aria-hidden="true"><span style="width:{{ $loop->first ? ($approval['all'] ? 100 : 0) : $share }}%"></span></div>
                    <div class="db-kpi-sub"><span>{{ $sub }}</span><span>{{ $loop->first ? '' : $share . '%' }}</span></div>
                </a>
            @endforeach
        </section>

        {{-- ================= CHARTS: status (doughnut) + trend (line) ================= --}}
        <div class="db-grid db-grid-2">
            <section class="vp-card db-card">
                <div class="db-card-head"><div><h2>Inventory Status</h2><p>Today's status of your approved media</p></div>
                    <a class="db-link" href="{{ route('vendor.availability') }}">Availability</a></div>
                @if ($total > 0)
                    <div class="db-donut-wrap">
                        <div class="db-donut">
                            <canvas id="chStatus" aria-label="Inventory status chart" role="img"></canvas>
                            <div class="db-donut-center"><div><b>{{ $total }}</b><span>media</span></div></div>
                        </div>
                        <div class="db-legend">
                            @foreach ($statusChart['labels'] as $i => $l)
                                <div class="db-legend-row"><i style="background:{{ $statusChart['colors'][$i] }}"></i><span>{{ $l }}</span>
                                    <b>{{ $statusChart['data'][$i] }}</b><em>{{ $pct($statusChart['data'][$i]) }}%</em></div>
                            @endforeach
                            <div class="db-occ"><span>Occupancy today</span><b>{{ $pct($status['booked']) }}%</b></div>
                        </div>
                    </div>
                @else
                    <div class="db-empty"><div><i class="fa-solid fa-chart-pie"></i>No approved media yet.</div></div>
                @endif
            </section>

            <section class="vp-card db-card">
                <div class="db-card-head"><div><h2>Bookings &amp; Enquiries</h2><p>Last 6 months · bookings by start month, enquiries by date sent</p></div>
                    <a class="db-link" href="{{ route('vendor.bookings') }}">Bookings</a></div>
                <div class="db-chart"><canvas id="chTrend" aria-label="Bookings and enquiries over the last 6 months" role="img"></canvas></div>
            </section>
        </div>

        {{-- ================= category (bar) + approval + operations ================= --}}
        <div class="db-grid db-grid-3">
            <section class="vp-card db-card">
                <div class="db-card-head"><div><h2>Media by Category</h2><p>All your media, including pending</p></div>
                    <a class="db-link" href="{{ route('vendor.inventory') }}">Media</a></div>
                @if (count($charts['categories']['data']))
                    <div class="db-chart" style="min-height:240px"><canvas id="chCategory" aria-label="Media by category chart" role="img"></canvas></div>
                @else
                    <div class="db-empty"><div><i class="fa-solid fa-chart-column"></i>Add media to see it here.</div></div>
                @endif
            </section>

            <section class="vp-card db-card">
                <div class="db-card-head"><div><h2>Media Approval Status</h2><p>New media goes live once Brand Adda approves it</p></div></div>
                <div class="db-appr">
                    <a href="{{ route('vendor.inventory', ['approval' => 'pending']) }}"><span class="db-ico" style="--bg:#FFEDD5;--fg:#C2410C"><i class="fa-solid fa-hourglass-half"></i></span><span>Pending Approval</span><b>{{ $approval['pending'] }}</b></a>
                    <a href="{{ route('vendor.inventory', ['approval' => 'approved']) }}"><span class="db-ico" style="--bg:#DCFCE7;--fg:#15803D"><i class="fa-solid fa-circle-check"></i></span><span>Approved</span><b>{{ $approval['approved'] }}</b></a>
                    <a href="{{ route('vendor.inventory', ['approval' => 'rejected']) }}"><span class="db-ico" style="--bg:#FEE2E2;--fg:#B91C1C"><i class="fa-solid fa-circle-xmark"></i></span><span>Rejected</span><b>{{ $approval['rejected'] }}</b></a>
                </div>
                @if ($latestRejected)
                    <div class="db-reject">
                        <strong>{{ $latestRejected->hoarding_code ?: $latestRejected->media_code ?: $latestRejected->media_title }} rejected:</strong>
                        “{{ \Illuminate\Support\Str::limit($latestRejected->rejection_reason, 110) }}”
                        <a href="{{ route('vendor.media.edit', $latestRejected->id) }}" style="font-weight:700">Edit &amp; resubmit</a>
                    </div>
                @endif
            </section>

            <section class="vp-card db-card">
                <div class="db-card-head"><div><h2>Operations at a Glance</h2><p>Installation and monitoring of booked media</p></div>
                    <a class="db-link" href="{{ route('vendor.operations') }}">Open</a></div>
                <div class="db-pipe">
                    @foreach ($stageLabels as $key => $label)
                        @php [$hint, $sbg, $sfg] = $stageStyle[$key] ?? ['', '#F1F5F9', '#475569']; @endphp
                        <a href="{{ route('vendor.operations', ['stage' => $key]) }}">
                            <span class="db-step" style="background:{{ $sbg }};color:{{ $sfg }}">{{ $loop->iteration }}</span>
                            <span class="l">{{ $label }}<small>{{ $hint }}</small></span>
                            <b style="color:{{ $sfg }}">{{ $stages[$key] }}</b>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- ================= upcoming availability + enquiries ================= --}}
        <div class="db-grid db-grid-half">
            <section class="vp-card db-card">
                <div class="db-card-head"><div><h2>Upcoming Availability</h2><p>Booked or blocked media and when it frees up</p></div>
                    <a class="db-link" href="{{ route('vendor.availability') }}">View all</a></div>
                @forelse ($upcoming as $u)
                    <div class="db-row">
                        <span class="db-ico" style="--bg:{{ $u->bg }};--fg:{{ $u->fg }}"><i class="fa-regular fa-calendar"></i></span>
                        <div class="db-row-main">
                            <div class="db-row-title">{{ $u->media_title }}{{ $u->area_name ? ', ' . $u->area_name : '' }}</div>
                            <div class="db-row-sub">{{ $u->hoarding_code ?: $u->media_code }} · <span class="vp-badge" style="background:{{ $u->bg }};color:{{ $u->fg }};padding:1px 8px">{{ $u->label }}</span> till {{ \Carbon\Carbon::parse($u->to_date)->format('d M Y') }}</div>
                        </div>
                        <div style="text-align:right;min-width:100px">
                            <div style="font-size:11px;color:#64748B">Available from</div>
                            <div style="font-size:13px;font-weight:800;color:#15803D">{{ $u->available_from->format('d M Y') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="db-empty"><div><i class="fa-regular fa-calendar-check"></i>None of your media is booked or blocked today.</div></div>
                @endforelse
            </section>

            <section class="vp-card db-card">
                <div class="db-card-head"><div><h2>Latest Enquiries</h2><p>From your website’s Contact page{{ $newEnquiries ? ' · ' . $newEnquiries . ' new' : '' }}</p></div>
                    <a class="db-link" href="{{ route('vendor.enquiries') }}">View all</a></div>
                @forelse ($enquiries as $e)
                    @php [$ebg, $efg] = \App\Models\VendorEnquiry::COLORS[$e->status] ?? ['#F1F5F9', '#475569']; @endphp
                    <a class="db-row" href="{{ route('vendor.enquiries', ['open' => $e->id]) }}" style="text-decoration:none;color:#0F172A">
                        <span class="db-avatar">{{ strtoupper(mb_substr($e->name, 0, 1)) }}</span>
                        <div class="db-row-main">
                            <div class="db-row-title">{{ $e->name }}@if (!$e->read_at) <span class="vp-badge" style="background:#FFEDD5;color:#9A3412;padding:1px 8px;margin-left:4px">New</span>@endif</div>
                            <div class="db-row-sub">{{ $e->media ? ($e->media->hoarding_code ?: $e->media->media_code) . ' · ' : '' }}{{ \Illuminate\Support\Str::limit($e->message, 70) }}</div>
                        </div>
                        <div style="text-align:right">
                            <span class="vp-badge" style="background:{{ $ebg }};color:{{ $efg }}">{{ \App\Models\VendorEnquiry::STATUSES[$e->status] ?? ucfirst($e->status) }}</span>
                            <div style="font-size:11px;color:#94A3B8;margin-top:4px">{{ $e->created_at->diffForHumans() }}</div>
                        </div>
                    </a>
                @empty
                    <div class="db-empty"><div><i class="fa-regular fa-envelope"></i>No enquiries yet. Share your website to get some.</div></div>
                @endforelse
            </section>
        </div>

        {{-- ================= notifications ================= --}}
        <section class="vp-card db-card">
            <div class="db-card-head"><div><h2>Recent Notifications</h2><p>Approvals, assignments and enquiries</p></div>
                <a class="db-link" href="{{ route('vendor.notifications') }}">See all</a></div>
            @forelse ($notifications as $n)
                @include('vendor.partials.notification-row', ['n' => $n])
            @empty
                <p class="vp-muted" style="margin:0">No notifications yet.</p>
            @endforelse
        </section>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            if (!window.Chart) return;
            Chart.defaults.font.family = "'Montserrat', sans-serif";
            Chart.defaults.font.size = 12;
            Chart.defaults.color = '#64748B';
            Chart.defaults.plugins.tooltip.backgroundColor = '#0F172A';
            Chart.defaults.plugins.tooltip.padding = 10;
            Chart.defaults.plugins.tooltip.cornerRadius = 8;

            const status = @json($statusChart);
            const charts = @json($charts);
            const grid = { color: '#F1F5F9' };

            // Pie (doughnut): today's status of approved media.
            const s = document.getElementById('chStatus');
            if (s) new Chart(s, {
                type: 'doughnut',
                data: { labels: status.labels, datasets: [{ data: status.data, backgroundColor: status.colors, borderColor: '#fff', borderWidth: 3, hoverOffset: 6 }] },
                options: { cutout: '68%', maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });

            // Line: bookings and enquiries per month.
            const t = document.getElementById('chTrend');
            if (t) {
                const fill = function (ctx, rgb) {
                    const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, ctx.chart.height);
                    g.addColorStop(0, 'rgba(' + rgb + ',.22)'); g.addColorStop(1, 'rgba(' + rgb + ',0)');
                    return g;
                };
                new Chart(t, {
                    type: 'line',
                    data: {
                        labels: charts.trend.labels,
                        datasets: [
                            { label: 'Bookings', data: charts.trend.bookings, borderColor: '#2563EB', backgroundColor: function (c) { return fill(c, '37,99,235'); },
                              fill: true, tension: .35, borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: '#fff', pointBorderWidth: 2 },
                            { label: 'Enquiries', data: charts.trend.enquiries, borderColor: '#F97316', backgroundColor: function (c) { return fill(c, '249,115,22'); },
                              fill: true, tension: .35, borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: '#fff', pointBorderWidth: 2 }
                        ]
                    },
                    options: {
                        maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                        plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8, boxHeight: 8 } } },
                        scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 }, grid: grid, border: { display: false } } }
                    }
                });
            }

            // Bar: media by category.
            const c = document.getElementById('chCategory');
            if (c) new Chart(c, {
                type: 'bar',
                // Long names ("Hoardings/Billboards") wrap onto two lines under the bar.
                data: { labels: charts.categories.labels.map(function (l) { return l.length > 14 && l.indexOf('/') > 0 ? l.split('/').map(function (p, i, a) { return i < a.length - 1 ? p + '/' : p; }) : l; }), datasets: [{ label: 'Media', data: charts.categories.data,
                    backgroundColor: ['#F97316', '#0F172A', '#2563EB', '#16A34A', '#D97706', '#7C3AED', '#0891B2'], borderRadius: 8, maxBarThickness: 46 }] },
                options: {
                    maintainAspectRatio: false, plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: grid, border: { display: false } },
                              x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: false } } }
                }
            });
        })();
    </script>
@endsection

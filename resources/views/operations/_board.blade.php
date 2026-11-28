{{-- Operations board (PRD 2.8), shared by Super Admin and the vendor panel.

     Super Admin runs the workflow ($canEdit = true: upload photos, share them with
     the user, mark the media removed). The vendor panel shows the same board
     read-only ($canEdit = false) so the vendor sees every photo Brand Adda added.

     Layout: stage step bar → booking list | (summary · progress tracker ·
     "what's happening now" · photo tabs per stage · removal · history).

     Params: $routes ['index', and when $canEdit: 'photo', 'share', 'complete'],
             $baseQuery (extra query kept on every link, e.g. vendor_id),
             $canEdit, $showVendor, $mediaUrl (fn (int $mediaId) => url),
             plus the controller data: $stage, $stageLabels, $counts, $list,
             $pendingMedia, $selected, $photos, $shots, $monitorDate. --}}
@php
    $fmt = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '—';
    $stageKeys = array_keys($stageLabels);
    $link = fn (array $q = []) => route($routes['index'], array_filter(array_merge($baseQuery, $q), fn ($v) => $v !== null && $v !== ''));
    // What each stage means, in one line, and its icon.
    $stageInfo = [
        'pending'      => ['fa-solid fa-hourglass-half', 'Free, not booked yet'],
        'installation' => ['fa-solid fa-screwdriver-wrench', 'Being installed'],
        'monitoring'   => ['fa-solid fa-eye', 'Campaign running'],
        'end_date'     => ['fa-solid fa-flag-checkered', 'Campaign ended'],
        'completed'    => ['fa-solid fa-circle-check', 'Flex removed'],
    ];
@endphp

<style>
    .ops { --o-orange: #F97316; --o-navy: #0F172A; --o-line: #E5E7EB; --o-muted: #64748B; color: var(--o-navy); }
    .ops * { box-sizing: border-box; }
    /* Stage step bar */
    .ops-steps { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; }
    .ops-step { position: relative; display: flex; align-items: center; gap: 12px; padding: 14px; border: 1px solid var(--o-line); border-radius: 14px; background: #fff; color: var(--o-navy) !important; text-decoration: none !important; transition: border-color .15s, box-shadow .15s; }
    .ops-step:hover { border-color: #FDBA74; }
    .ops-step.is-on { border: 2px solid var(--o-orange); background: #FFF7ED; box-shadow: 0 6px 16px rgba(249, 115, 22, .15); }
    .ops-step-ico { width: 40px; height: 40px; flex: none; border-radius: 12px; display: grid; place-items: center; background: #F1F5F9; color: #334155; font-size: 16px; }
    .ops-step.is-on .ops-step-ico { background: var(--o-orange); color: #fff; }
    .ops-step-txt { min-width: 0; flex: 1; }
    .ops-step-txt b { display: block; font-size: 14px; line-height: 1.25; }
    .ops-step-txt small { display: block; font-size: 12px; color: var(--o-muted); margin-top: 2px; }
    .ops-step-count { font-size: 20px; font-weight: 800; }
    .ops-step-no { position: absolute; top: -9px; left: 12px; padding: 0 7px; border-radius: 999px; background: var(--o-navy); color: #fff; font-size: 11px; font-weight: 700; line-height: 18px; }
    .ops-step.is-on .ops-step-no { background: var(--o-orange); }
    /* Layout */
    .ops-wrap { display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start; margin-top: 20px; }
    .ops-list { flex: 1 1 290px; max-width: 360px; background: #fff; border: 1px solid var(--o-line); border-radius: 14px; padding: 8px; }
    .ops-list-head { padding: 10px 12px; font-size: 13px; font-weight: 700; color: var(--o-muted); text-transform: uppercase; letter-spacing: .04em; }
    .ops-bk { display: block; padding: 12px 14px; margin-top: 6px; border: 1px solid transparent; border-radius: 12px; color: var(--o-navy) !important; text-decoration: none !important; }
    .ops-bk:hover { background: #F8FAFC; }
    .ops-bk.is-on { background: #FFF7ED; border-color: #FDBA74; }
    .ops-bk-title { font-size: 14px; font-weight: 700; }
    .ops-bk-sub { font-size: 12px; color: var(--o-muted); margin-top: 3px; }
    .ops-bk-row { display: flex; flex-wrap: wrap; gap: 6px 12px; margin-top: 8px; font-size: 12px; color: #334155; }
    .ops-main { flex: 999 1 560px; min-width: 0; display: flex; flex-direction: column; gap: 18px; }
    .ops-card { background: #fff; border: 1px solid var(--o-line); border-radius: 14px; padding: 20px 22px; }
    .ops-card h3 { margin: 0; font-size: 17px; font-weight: 800; }
    .ops-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
    /* Summary */
    .ops-sum-top { display: flex; flex-wrap: wrap; gap: 10px 16px; align-items: flex-start; justify-content: space-between; }
    .ops-sum-title { font-size: 19px; font-weight: 800; line-height: 1.3; }
    .ops-sum-sub { font-size: 13px; color: var(--o-muted); margin-top: 3px; }
    .ops-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-top: 16px; padding-top: 16px; border-top: 1px dashed var(--o-line); }
    .ops-fact { display: flex; gap: 10px; }
    .ops-fact i { width: 34px; height: 34px; flex: none; border-radius: 10px; display: grid; place-items: center; background: #F1F5F9; color: #334155; font-size: 14px; }
    .ops-fact span { display: block; font-size: 12px; color: var(--o-muted); }
    .ops-fact b { display: block; font-size: 14px; word-break: break-word; }
    .ops-fact a { color: #C2410C; }
    /* Progress tracker */
    .ops-track { list-style: none; margin: 16px 0 0; padding: 0; display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); }
    .ops-track li { position: relative; text-align: center; padding: 0 4px; }
    .ops-track li::before { content: ''; position: absolute; top: 15px; left: -50%; width: 100%; height: 3px; background: var(--o-line); z-index: 0; }
    .ops-track li:first-child::before { display: none; }
    .ops-track li.done::before, .ops-track li.now::before { background: #16A34A; }
    .ops-dot { position: relative; z-index: 1; width: 32px; height: 32px; margin: 0 auto; border-radius: 999px; display: grid; place-items: center; font-size: 13px; font-weight: 800; background: #fff; border: 3px solid var(--o-line); color: var(--o-muted); }
    .ops-track li.done .ops-dot { background: #16A34A; border-color: #16A34A; color: #fff; }
    .ops-track li.now .ops-dot { background: var(--o-orange); border-color: #FED7AA; color: #fff; box-shadow: 0 0 0 4px rgba(249, 115, 22, .15); }
    .ops-track b { display: block; margin-top: 8px; font-size: 12px; }
    .ops-track small { display: block; font-size: 11px; color: var(--o-muted); margin-top: 2px; }
    .ops-track li.upcoming b { color: var(--o-muted); font-weight: 600; }
    /* Now box */
    .ops-now { display: flex; gap: 14px; align-items: flex-start; padding: 16px 18px; border-radius: 14px; border: 1px solid #FED7AA; background: #FFF7ED; }
    .ops-now i { font-size: 20px; color: #C2410C; margin-top: 2px; }
    .ops-now b { display: block; font-size: 14px; }
    .ops-now p { margin: 4px 0 0; font-size: 13px; color: #334155; line-height: 1.5; }
    .ops-now.done { border-color: #BBF7D0; background: #F0FDF4; }
    .ops-now.done i { color: #16A34A; }
    /* Photo tabs */
    .ops-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin: 14px 0 16px; border-bottom: 1px solid var(--o-line); }
    .ops-tab { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; margin-bottom: -1px; border: 0; border-bottom: 3px solid transparent; background: none; font: inherit; font-size: 14px; font-weight: 700; color: var(--o-muted); cursor: pointer; }
    .ops-tab.is-on { color: var(--o-navy); border-bottom-color: var(--o-orange); }
    .ops-tab .ops-pill { padding: 2px 8px; font-size: 11px; }
    .ops-shots { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; }
    .ops-shot-label { font-size: 13px; font-weight: 700; margin-bottom: 6px; }
    .ops-shot img { display: block; width: 100%; height: 160px; object-fit: cover; border-radius: 12px; border: 1px solid var(--o-line); }
    .ops-shot-empty { height: 160px; display: grid; place-items: center; text-align: center; padding: 12px; border: 2px dashed #CBD5E1; border-radius: 12px; background: #F8FAFC; color: var(--o-muted); font-size: 13px; }
    .ops-shot-empty i { display: block; font-size: 22px; margin-bottom: 6px; }
    .ops-shot-meta { display: flex; justify-content: space-between; gap: 8px; margin-top: 6px; font-size: 12px; color: #475569; }
    .ops-shot .vp-drop { height: 160px; margin: 0; }
    .ops-sharebar { margin-top: 16px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; padding: 12px 14px; border-radius: 12px; background: #F8FAFC; font-size: 13px; color: #334155; }
    .ops-sharemenu { position: relative; }
    .ops-sharemenu > summary { list-style: none; cursor: pointer; }
    .ops-sharemenu > summary::-webkit-details-marker { display: none; }
    .ops-sharemenu-list { position: absolute; right: 0; bottom: calc(100% + 6px); z-index: 20; min-width: 320px; max-width: calc(100vw - 40px); background: #fff; border: 1px solid var(--o-line); border-radius: 10px; box-shadow: 0 10px 30px rgba(15,23,42,.15); padding: 6px; }
    /* The admin theme hides plain checkboxes (moves them off-screen); this one must be visible. */
    .ops-check { position: static !important; left: auto !important; opacity: 1 !important; width: 18px; height: 18px; flex: none; margin: 0; accent-color: #F97316; pointer-events: auto !important; }
    .ops-share-btn { min-height: 40px; background: #F97316; color: #0F172A; font-weight: 700; border: 1px solid #F97316; }
    .ops-share-btn:hover { background: #EA580C; border-color: #EA580C; }
    .ops-share-btn.is-again { background: #fff; color: #C2410C; }
    .ops-share-btn.is-again:hover { background: #FFF7ED; }
    .ops-share-row { padding: 6px 4px; }
    .ops-share-row + .ops-share-row { border-top: 1px solid var(--o-line); }
    .ops-share-row label { display: flex; gap: 8px; align-items: center; font-weight: 700; font-size: 13px; margin: 0 0 6px; }
    .ops-share-input { display: flex; gap: 6px; }
    .ops-share-input .vp-input { min-height: 36px; flex: 1; min-width: 0; }
    .ops-sharemenu-list .ops-share-input button { width: auto; flex: none; justify-content: center; background: #F97316; border: 1px solid #F97316; color: #0F172A; padding: 6px 16px; }
    .ops-sharemenu-list .ops-share-input button:hover { background: #EA580C; }
    .ops-sharemenu-list button { display: flex; gap: 10px; align-items: center; width: 100%; text-align: left; background: none; border: 0; border-radius: 8px; padding: 9px 10px; font-weight: 700; font-size: 14px; color: #0F172A; cursor: pointer; }
    .ops-sharemenu-list button i { font-size: 18px; width: 20px; text-align: center; }
    .ops-sharemenu-list button small { display: block; font-weight: 400; font-size: 12px; color: #64748B; }
    .ops-sharemenu-list button:hover:not(:disabled) { background: #FFF7ED; }
    .ops-sharemenu-list button:disabled { opacity: .5; cursor: not-allowed; }
    .ops-addbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin: 0 0 12px; padding: 10px 12px; border: 1px dashed var(--o-orange); border-radius: 10px; background: #FFF7ED; }
    .ops-dates { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 14px; }
    .ops-date-chip { padding: 4px 10px; border-radius: 999px; border: 1px solid var(--o-line); font-size: 12px; font-weight: 600; color: #334155 !important; text-decoration: none !important; background: #fff; }
    .ops-date-chip.is-on { border-color: var(--o-orange); background: #FFF7ED; color: #C2410C !important; }
    @media (max-width: 1100px) { .ops-steps { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 700px) {
        .ops-steps { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .ops-list { max-width: none; }
        .ops-track b { font-size: 11px; }
        .ops-track small { display: none; }
    }
</style>

<div class="ops">
    {{-- ================= STAGE STEP BAR ================= --}}
    <nav class="ops-steps" aria-label="Operation stages">
        @foreach ($stageLabels as $key => $label)
            <a href="{{ $link(['stage' => $key, 'q' => request('q')]) }}" class="ops-step {{ $key === $stage ? 'is-on' : '' }}" @if ($key === $stage) aria-current="page" @endif>
                <span class="ops-step-no">Step {{ $loop->iteration }}</span>
                <span class="ops-step-ico"><i class="{{ $stageInfo[$key][0] }}" aria-hidden="true"></i></span>
                <span class="ops-step-txt"><b>{{ $label }}</b><small>{{ $stageInfo[$key][1] }}</small></span>
                <span class="ops-step-count" aria-label="{{ $counts[$key] }} in this stage">{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </nav>

    @if ($stage === 'pending')
        {{-- ================= PENDING MEDIA ================= --}}
        <section class="vp-card" style="overflow:hidden;margin-top:20px">
            <div style="padding:16px 20px;border-bottom:1px solid #E5E7EB">
                <h2 class="vp-h2">Pending Media</h2>
                <div class="vp-muted" style="margin-top:4px">Approved media that is free, with no booking coming up. When it is booked, it moves to <strong>Installation</strong>.</div>
            </div>
            <div class="vp-table-wrap">
                <table class="vp-table">
                    <thead><tr>@if ($showVendor)<th>Vendor</th>@endif<th>Hoarding Code</th><th>Media Title</th><th>Location</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse ($pendingMedia as $m)
                            <tr>
                                @if ($showVendor)<td class="nowrap">{{ $m->vendor_name ?: '—' }}</td>@endif
                                <td class="nowrap" style="font-weight:700">{{ $m->hoarding_code ?: $m->media_code ?: '—' }}</td>
                                <td>{{ $m->media_title }}</td>
                                <td>{{ $m->area_name }}{{ $m->city_name ? ', ' . $m->city_name : '' }}</td>
                                <td class="nowrap"><a href="{{ $mediaUrl($m->id) }}" class="vp-btn vp-btn-g vp-btn-sm">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $showVendor ? 5 : 4 }}" style="text-align:center;padding:40px;color:#475569">No pending media.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <div class="ops-wrap">
            {{-- ================= BOOKINGS IN THIS STAGE ================= --}}
            <section class="ops-list" aria-label="Bookings in {{ $stageLabels[$stage] }}">
                <div class="ops-list-head">{{ $stageLabels[$stage] }} · {{ $list->total() }} {{ \Illuminate\Support\Str::plural('booking', $list->total()) }}</div>
                @forelse ($list as $op)
                    @php $sel = $selected && $selected->id === $op->id; @endphp
                    <a href="{{ $link(['stage' => $stage, 'op' => $op->id, 'q' => request('q'), 'page' => $list->currentPage() > 1 ? $list->currentPage() : null]) }}" class="ops-bk {{ $sel ? 'is-on' : '' }}" @if ($sel) aria-current="true" @endif>
                        <div class="ops-bk-title">{{ $op->media_title ?: ($op->hoarding_code ?: $op->media_code) }}</div>
                        <div class="ops-bk-sub">{{ $op->hoarding_code ?: $op->media_code }} · {{ $op->order_no }}</div>
                        <div class="ops-bk-row">
                            <span><i class="fa-regular fa-calendar" aria-hidden="true"></i> {{ $fmt($op->from_date) }} – {{ $fmt($op->to_date) }}</span>
                            @if ($op->user_name)<span><i class="fa-regular fa-user" aria-hidden="true"></i> {{ $op->user_name }}</span>@endif
                            @if ($showVendor && $op->vendor_name)<span style="color:#C2410C;font-weight:600"><i class="fa-solid fa-store" aria-hidden="true"></i> {{ $op->vendor_name }}</span>@endif
                        </div>
                    </a>
                @empty
                    <p class="vp-muted" style="padding:10px 12px;margin:0">No bookings in this stage right now.</p>
                @endforelse
                @if ($list->hasPages())
                    <div style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 4px 4px">
                        <span class="vp-muted" style="font-size:12px">{{ $list->firstItem() }}–{{ $list->lastItem() }} of {{ $list->total() }}</span>
                        {{ $list->onEachSide(0)->links('pagination::simple-bootstrap-4') }}
                    </div>
                @endif
            </section>

            @if ($selected)
                @php
                    $current = $selected->effective_stage;
                    $currentIndex = array_search($current, $stageKeys);
                    $from = \Carbon\Carbon::parse($selected->from_date)->startOfDay();
                    $to = \Carbon\Carbon::parse($selected->to_date)->startOfDay();
                    $today = now()->startOfDay();
                    $days = $from->diffInDays($to) + 1;
                    $timing = $today->lt($from) ? 'Starts in ' . $today->diffInDays($from) . ' day' . ($today->diffInDays($from) === 1 ? '' : 's')
                        : ($today->lte($to) ? $today->diffInDays($to) . ' day' . ($today->diffInDays($to) === 1 ? '' : 's') . ' left' : 'Ended');
                    $stepDate = [
                        'pending'      => 'Booked ' . $fmt($selected->created_at),
                        'installation' => $selected->installation_shared_at ? 'Shared ' . $fmt($selected->installation_shared_at) : 'Due ' . $fmt($selected->from_date),
                        'monitoring'   => $current === 'monitoring' ? 'Till ' . $fmt($selected->to_date) : ($currentIndex > 2 ? 'Done' : 'Upcoming'),
                        'end_date'     => $selected->end_date_shared_at ? 'Shared ' . $fmt($selected->end_date_shared_at) : 'Due ' . $fmt($selected->to_date),
                        'completed'    => $selected->completed_at ? $fmt($selected->completed_at) : 'Upcoming',
                    ];
                    $panelStage = in_array($current, ['installation', 'monitoring', 'end_date']) ? $current : null;
                    $noUser = empty($selected->user_email) && !empty($selected->booked_by);
                    $pillColour = ['installation' => ['#FFEDD5', '#9A3412'], 'monitoring' => ['#DBEAFE', '#1E40AF'], 'end_date' => ['#FEF3C7', '#92400E'], 'completed' => ['#DCFCE7', '#166534']][$current] ?? ['#F1F5F9', '#334155'];
                    // "What's happening now", worded for whoever is looking.
                    $now = match ($current) {
                        'installation' => [$canEdit ? 'Upload the 3 installation photos' : 'Waiting for installation photos',
                            $canEdit ? 'Install the flex from ' . $fmt($selected->from_date) . ', upload the Newspaper, Long and Close shots below, then ' . ($noUser ? 'click Save & Move On.' : 'click Share with User and send them on WhatsApp or by email.')
                                     : 'Brand Adda installs the flex from ' . $fmt($selected->from_date) . ' and uploads 3 installation photos here. You will be notified when they are added.'],
                        'monitoring' => ['Campaign is running till ' . $fmt($selected->to_date),
                            $canEdit ? 'When the user asks for proof, pick the date in the Monitoring tab, upload the 3 photos for that date and share them.'
                                     : 'Brand Adda adds monitoring photos for the dates the user asks for. They appear in the Monitoring tab.'],
                        'end_date' => [$canEdit ? 'Upload the end-date photos, then remove the flex' : 'Campaign ended — end-date photos next',
                            $canEdit ? 'Upload the 3 end-date photos and share them. After that, remove the flex and click Mark Media Removed.'
                                     : 'Brand Adda uploads the end-date photos and then removes the flex. The booking then moves to Completed.'],
                        'completed' => ['Completed · media removed', 'The flex was removed on ' . $fmt($selected->completed_at) . '. The hoarding is free for new bookings.'
                            . (collect(['installation', 'monitoring', 'end_date'])->contains(fn ($st) => !empty($photos[$st])) ? ' The photos taken are kept below.' : ' No photos were recorded for this booking.')],
                        default => ['', ''],
                    };
                    // Photo tab to open first: the current stage, or the last one with photos once completed.
                    $photoStages = ['installation' => 'Installation', 'monitoring' => 'Monitoring', 'end_date' => 'End Date'];
                    // A completed booking is closed: show only the stages that have photos, no empty slots.
                    if ($current === 'completed') {
                        $photoStages = array_filter($photoStages, fn ($label, $st) => !empty($photos[$st]), ARRAY_FILTER_USE_BOTH);
                    }
                    // Monitoring date shown: the one asked for (?date=), else the latest date that has photos, else today.
                    $monitorDate = request('date') ?: (collect(array_keys($photos['monitoring'] ?? []))->sort()->last() ?? $monitorDate);
                    $openTab = array_key_exists((string) request('tab'), $photoStages) ? request('tab') : ($panelStage ?: (collect(array_keys($photoStages))->reverse()->first(fn ($s) => !empty($photos[$s])) ?? array_key_first($photoStages)));
                @endphp
                <div class="ops-main">
                    {{-- ================= SUMMARY ================= --}}
                    <section class="ops-card" aria-label="Booking summary">
                        <div class="ops-sum-top">
                            <div style="min-width:0">
                                <div class="ops-sum-title">{{ $selected->media_title ?: ($selected->hoarding_code ?: $selected->media_code) }}</div>
                                <div class="ops-sum-sub">{{ $selected->hoarding_code ?: $selected->media_code }}{{ $selected->area_name ? ' · ' . $selected->area_name : '' }} · Booking <strong style="color:#0F172A">{{ $selected->order_no }}</strong></div>
                            </div>
                            <span class="ops-pill" style="background:{{ $pillColour[0] }};color:{{ $pillColour[1] }}"><i class="{{ $stageInfo[$current][0] }}" aria-hidden="true"></i>{{ $stageLabels[$current] }}</span>
                        </div>
                        <div class="ops-facts">
                            <div class="ops-fact"><i class="fa-regular fa-user" aria-hidden="true"></i><div>
                                <span>Customer</span>
                                @if ($noUser)
                                    <b>{{ $selected->booked_by === 'admin' ? 'Booked by Brand Adda' : 'Booked by vendor' }}</b><span>No customer details</span>
                                @else
                                    <b>{{ $selected->user_name ?: '—' }}</b>
                                    @if ($selected->user_mobile)<span><a href="tel:{{ $selected->user_mobile }}">{{ $selected->user_mobile }}</a></span>@endif
                                    @if ($selected->user_email)<span><a href="mailto:{{ $selected->user_email }}">{{ $selected->user_email }}</a></span>@endif
                                @endif
                            </div></div>
                            <div class="ops-fact"><i class="fa-regular fa-calendar" aria-hidden="true"></i><div>
                                <span>Campaign</span>
                                <b>{{ $fmt($selected->from_date) }} → {{ $fmt($selected->to_date) }}</b>
                                <span>{{ $days }} {{ $days === 1 ? 'day' : 'days' }} · {{ $timing }}</span>
                            </div></div>
                            @if ($showVendor)
                                <div class="ops-fact"><i class="fa-solid fa-store" aria-hidden="true"></i><div>
                                    <span>Vendor</span><b>{{ $selected->vendor_name ?: '—' }}</b>
                                </div></div>
                            @endif
                        </div>

                        {{-- Progress: ✓ done · orange = now · grey = upcoming --}}
                        <ol class="ops-track" aria-label="Progress">
                            @foreach ($stageLabels as $key => $label)
                                @php
                                    $i = $loop->index;
                                    $state = $current === 'completed' || $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'now' : 'upcoming');
                                @endphp
                                <li class="{{ $state }}" @if ($state === 'now') aria-current="step" @endif>
                                    <span class="ops-dot">@if ($state === 'done')<i class="fa-solid fa-check" aria-hidden="true"></i>@else{{ $loop->iteration }}@endif</span>
                                    <b>{{ $label }}</b>
                                    <small>{{ $stepDate[$key] }}</small>
                                </li>
                            @endforeach
                        </ol>
                    </section>

                    {{-- ================= WHAT'S HAPPENING NOW ================= --}}
                    <div class="ops-now {{ $current === 'completed' ? 'done' : '' }}" role="status">
                        <i class="{{ $current === 'completed' ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-info' }}" aria-hidden="true"></i>
                        <div><b>{{ $now[0] }}</b><p>{{ $now[1] }}</p></div>
                    </div>

                    {{-- ================= PHOTOS (tab per stage) ================= --}}
                    @if ($photoStages)
                    <section class="ops-card" id="ops-photos" aria-label="Photos" style="scroll-margin-top:90px">
                        <h3>Photos</h3>
                        @if (session('ops_focus') && (session('success') || session('error')))
                            {{-- After an upload / share the page opens here, so the result is shown here too. --}}
                            <div class="alert {{ session('error') ? 'alert-danger' : 'alert-success' }}" role="status" style="margin:0 0 12px">{{ session('error') ?: session('success') }}</div>
                        @endif
                        @if ($errors->hasAny(['photo', 'photo_date', 'shot_type', 'share']))
                            <div class="alert alert-danger" role="alert" data-ops-focus style="margin:0 0 12px">{{ $errors->first('photo') ?: $errors->first('photo_date') ?: $errors->first('shot_type') ?: $errors->first('share') }}</div>
                        @endif
                        <div class="ops-tabs" role="tablist">
                            @foreach ($photoStages as $ps => $pl)
                                @php
                                    $sets = $photos[$ps] ?? [];
                                    $uploaded = collect($sets)->flatten()->count();
                                    $sharedAll = $uploaded > 0 && collect($sets)->flatten()->every(fn ($p) => $p->shared_at);
                                @endphp
                                <button type="button" class="ops-tab {{ $openTab === $ps ? 'is-on' : '' }}" role="tab" aria-selected="{{ $openTab === $ps ? 'true' : 'false' }}" data-tab="ops-tab-{{ $ps }}">
                                    {{ $pl }}
                                    @if ($uploaded)
                                        <span class="ops-pill" style="{{ $sharedAll ? 'background:#DCFCE7;color:#166534' : 'background:#FFEDD5;color:#9A3412' }}">{{ $sharedAll ? ($noUser ? 'Saved' : 'Shared') : $uploaded . ' uploaded' }}</span>
                                    @elseif ($ps === $panelStage)
                                        <span class="ops-pill" style="background:#F1F5F9;color:#475569">Now</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        @foreach ($photoStages as $ps => $pl)
                            @php
                                // Only the current stage can be changed. End-date photos can also be added while the campaign
                                // is running (Monitoring), e.g. when the flex comes down early or the end date is near.
                                $editable = $canEdit && ($ps === $panelStage || ($ps === 'end_date' && $panelStage === 'monitoring'));
                                $dateKey = $ps === 'monitoring' ? $monitorDate : '_';
                                $set = $photos[$ps][$dateKey] ?? [];
                                $setShared = count($set) === 3 && collect($set)->every(fn ($p) => $p->shared_at);
                            @endphp
                            <div id="ops-tab-{{ $ps }}" class="ops-tabpanel" role="tabpanel" @if ($openTab !== $ps) hidden @endif>
                                @if ($ps === 'monitoring')
                                    {{-- Monitoring photos are taken per date the user asks for. --}}
                                    @php
                                        $monDates = array_keys($photos['monitoring'] ?? []);
                                        sort($monDates);
                                        // Suggested date for a new upload: today, kept inside the campaign.
                                        $suggestDate = $today->copy()->max($from)->min($to)->toDateString();
                                        if (in_array($suggestDate, $monDates) && count($photos['monitoring'][$suggestDate] ?? []) === 3) {
                                            $suggestDate = $monitorDate === $suggestDate ? '' : $suggestDate;
                                        }
                                    @endphp
                                    @if ($editable)
                                        {{-- Add photos for any day the user asks for: pick the date, the 3 upload slots open below. --}}
                                        <form method="GET" action="{{ route($routes['index']) }}#ops-photos" class="ops-addbar">
                                            @foreach ($baseQuery as $bk => $bv)<input type="hidden" name="{{ $bk }}" value="{{ $bv }}">@endforeach
                                            <input type="hidden" name="stage" value="{{ $stage }}"><input type="hidden" name="op" value="{{ $selected->id }}">
                                            <i class="fa fa-upload" aria-hidden="true" style="color:var(--o-orange)"></i>
                                            <label for="mdate" style="margin:0;font-weight:700;font-size:13px">Upload monitoring photos for</label>
                                            <input id="mdate" type="date" name="date" class="vp-input" required style="width:auto;min-height:36px" value="{{ $suggestDate }}"
                                                min="{{ $from->toDateString() }}" max="{{ $to->toDateString() }}">
                                            <button type="submit" class="vp-btn vp-btn-primary" style="min-height:36px"><i class="fa fa-plus"></i>Add Photos</button>
                                        </form>
                                    @endif
                                    <div class="ops-dates">
                                        @if (!$editable)
                                            <form method="GET" action="{{ route($routes['index']) }}" style="display:flex;gap:8px;align-items:center;margin:0">
                                                @foreach ($baseQuery as $bk => $bv)<input type="hidden" name="{{ $bk }}" value="{{ $bv }}">@endforeach
                                                <input type="hidden" name="stage" value="{{ $stage }}"><input type="hidden" name="op" value="{{ $selected->id }}">
                                                <label class="vp-muted" for="mdate" style="font-size:13px;margin:0">Date</label>
                                                <input id="mdate" type="date" name="date" class="vp-input" style="width:auto;min-height:36px" value="{{ $monitorDate }}"
                                                    min="{{ $from->toDateString() }}" max="{{ $to->toDateString() }}" onchange="this.form.submit()">
                                            </form>
                                        @endif
                                        @if ($monDates)
                                            <span class="vp-muted" style="font-size:12px">Dates with photos:</span>
                                            @foreach ($monDates as $d)
                                                <a href="{{ $link(['stage' => $stage, 'op' => $selected->id, 'date' => $d]) }}#ops-photos" class="ops-date-chip {{ $d === $monitorDate ? 'is-on' : '' }}">{{ $fmt($d) }}</a>
                                            @endforeach
                                        @endif
                                    </div>
                                    <div style="font-size:13px;font-weight:700;margin-bottom:10px">Photos for {{ $fmt($monitorDate) }}
                                        @if ($editable && count($set) === 3)
                                            <span class="vp-muted" style="font-weight:400"> · all 3 uploaded. For another day, pick the date above and click Add Photos.</span>
                                        @endif
                                    </div>
                                @endif
                                @if ($ps === 'end_date' && $editable && $panelStage === 'monitoring')
                                    <div class="vp-muted" style="font-size:13px;margin-bottom:10px;padding:8px 10px;background:#FEF3C7;border-radius:8px;color:#92400E">
                                        <i class="fa-solid fa-circle-info"></i> The campaign runs till <strong>{{ $fmt($selected->to_date) }}</strong>.
                                        You can upload the end-date photos now; sharing them ends Monitoring and moves the booking to End Date Pics.
                                    </div>
                                @endif

                                <div class="ops-shots">
                                    @foreach ($shots as $shotKey => $shotLabel)
                                        @php $p = $set[$shotKey] ?? null; @endphp
                                        <div class="ops-shot">
                                            <div class="ops-shot-label">{{ $shotLabel }}</div>
                                            @if ($p)
                                                <a href="{{ $p->url }}" target="_blank" rel="noopener" title="Open full size"><img src="{{ $p->url }}" alt="{{ $pl }} – {{ $shotLabel }}" loading="lazy"></a>
                                                <div class="ops-shot-meta">
                                                    <span>{{ $p->shared_at ? ($noUser ? 'Saved ' : 'Shared ') . $p->shared_at->format('d M, h:i A') : 'Uploaded ' . $p->updated_at->format('d M, h:i A') }}</span>
                                                    @if ($editable && !$p->shared_at)
                                                        <label style="font-weight:700;color:#C2410C;cursor:pointer;margin:0">Replace
                                                            <input type="file" accept="image/*" class="sr-only js-photo" data-form="ph-{{ $ps }}-{{ $shotKey }}"></label>
                                                    @endif
                                                </div>
                                            @elseif ($editable)
                                                <label class="vp-drop">
                                                    <i class="fa fa-upload" style="font-size:22px"></i>Upload Photo
                                                    <input type="file" accept="image/*" class="sr-only js-photo" data-form="ph-{{ $ps }}-{{ $shotKey }}">
                                                </label>
                                            @else
                                                <div class="ops-shot-empty"><span><i class="fa-regular fa-image" aria-hidden="true"></i>Not uploaded yet</span></div>
                                            @endif
                                            @if ($editable)
                                                <form id="ph-{{ $ps }}-{{ $shotKey }}" method="POST" action="{{ route($routes['photo'], $selected->id) }}" enctype="multipart/form-data" style="display:none">
                                                    @csrf
                                                    @foreach ($baseQuery as $bk => $bv)<input type="hidden" name="{{ $bk }}" value="{{ $bv }}">@endforeach
                                                    <input type="hidden" name="stage" value="{{ $ps }}">
                                                    <input type="hidden" name="return_stage" value="{{ $stage }}">
                                                    <input type="hidden" name="shot_type" value="{{ $shotKey }}">
                                                    @if ($ps === 'monitoring')<input type="hidden" name="photo_date" value="{{ $monitorDate }}">@endif
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                @if ($editable || count($set))
                                    <div class="ops-sharebar">
                                        <span>
                                            @if ($setShared)
                                                <i class="fa-solid fa-circle-check" style="color:#16A34A"></i> {{ $noUser ? 'Photos saved.' : 'Shared with the user.' }} @if ($editable)<span class="vp-muted">Use Share Again to send them to anyone else.</span>@endif
                                            @elseif ($editable && $noUser)
                                                {{ count($set) }} of 3 uploaded · no user to email; saving moves the booking on.
                                            @elseif ($editable)
                                                {{ count($set) }} of 3 uploaded · share them with <strong>{{ $selected->user_name ?: 'the user' }}</strong> on WhatsApp or by email.
                                            @else
                                                {{ count($set) }} of 3 uploaded · not shared with the user yet.
                                            @endif
                                        </span>
                                        @if ($editable)
                                            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                                            @if ($noUser)
                                                <form method="POST" action="{{ route($routes['share'], $selected->id) }}" style="margin:0"
                                                    data-confirm="Save these photos and move the booking to the next stage?" data-confirm-button="Yes, save">
                                                    @csrf
                                                    @foreach ($baseQuery as $bk => $bv)<input type="hidden" name="{{ $bk }}" value="{{ $bv }}">@endforeach
                                                    <input type="hidden" name="stage" value="{{ $ps }}">
                                                    @if ($ps === 'monitoring')<input type="hidden" name="photo_date" value="{{ $monitorDate }}">@endif
                                                    <button type="submit" class="vp-btn vp-btn-o" @disabled(count($set) < 3 || $setShared)><i class="fa fa-check"></i>Save &amp; Move On</button>
                                                </form>
                                            @endif
                                            @php
                                                // WhatsApp: a ready-made message with the 3 photo links; the number is taken from the
                                                // field when Send is clicked (10-digit Indian numbers get the 91 code).
                                                $waText = 'Hello ' . ($selected->user_name ?: 'there') . ",\n\n"
                                                    . $pl . ' photos' . ($ps === 'monitoring' ? ' for ' . $fmt($monitorDate) : '')
                                                    . ' of ' . trim(($selected->media_title ?: '') . ' (' . ($selected->media_code ?: $selected->hoarding_code) . ')')
                                                    . ($selected->order_no ? ', booking ' . $selected->order_no : '') . ":\n";
                                                foreach ($shots as $shotKey => $shotLabel) {
                                                    if (isset($set[$shotKey])) { $waText .= "\n" . $shotLabel . ': ' . $set[$shotKey]->url; }
                                                }
                                                $waText .= "\n\nThank you,\nBrand Adda";
                                            @endphp
                                            @if (count($set) === 3)
                                                {{-- Always available once the 3 photos are in: share with the user, other people, or again. --}}
                                                <form method="POST" action="{{ route($routes['share'], $selected->id) }}" style="margin:0" class="ops-shareform">
                                                    @csrf
                                                    @foreach ($baseQuery as $bk => $bv)<input type="hidden" name="{{ $bk }}" value="{{ $bv }}">@endforeach
                                                    <input type="hidden" name="stage" value="{{ $ps }}">
                                                    @if ($ps === 'monitoring')<input type="hidden" name="photo_date" value="{{ $monitorDate }}">@endif
                                                    <details class="ops-sharemenu">
                                                        <summary class="vp-btn ops-share-btn {{ $setShared ? 'is-again' : '' }}"><i class="fa fa-share-nodes"></i>{{ $setShared ? 'Share Again' : 'Share with User' }} <i class="fa fa-caret-down" aria-hidden="true"></i></summary>
                                                        <div class="ops-sharemenu-list">
                                                            <div class="ops-share-row">
                                                                <label for="wa-{{ $ps }}"><i class="fa-brands fa-whatsapp" style="color:#16A34A"></i> WhatsApp</label>
                                                                <div class="ops-share-input">
                                                                    <input id="wa-{{ $ps }}" type="tel" name="to_mobile" class="vp-input" value="{{ $selected->user_mobile }}" placeholder="Mobile number" inputmode="tel" autocomplete="off">
                                                                    <button type="submit" name="channel" value="whatsapp" data-wa-text="{{ $waText }}"
                                                                        data-confirm-tpl="Mark these photos as shared and open WhatsApp to send them to {to}?" data-field="to_mobile">Send</button>
                                                                </div>
                                                            </div>
                                                            <div class="ops-share-row">
                                                                <label for="em-{{ $ps }}"><i class="fa fa-envelope" style="color:#C2410C"></i> Email</label>
                                                                <div class="ops-share-input">
                                                                    <input id="em-{{ $ps }}" type="email" name="to_email" class="vp-input" value="{{ $selected->user_email }}" placeholder="Email address" autocomplete="off">
                                                                    <button type="submit" name="channel" value="email"
                                                                        data-confirm-tpl="Email these photos to {to}?" data-field="to_email">Send</button>
                                                                </div>
                                                            </div>
                                                            <p class="vp-muted" style="font-size:12px;margin:6px 2px 0">Change the number or email to share with someone else. You can share as many times as you need.</p>
                                                        </div>
                                                    </details>
                                                </form>
                                            @else
                                                <button type="button" class="vp-btn vp-btn-o" disabled title="Upload all 3 photos to share"><i class="fa fa-share-nodes"></i>Share with User</button>
                                            @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </section>
                    {{-- Completed with no photos on record: the banner above says so; no empty photo slots. --}}
                    @endif

                    {{-- ================= MARK AS COMPLETED (admin, any active stage) ================= --}}
                    @if ($canEdit && in_array($current, ['installation', 'monitoring', 'end_date'], true))
                        <section class="ops-card" id="ops-complete">
                            <h3 style="margin-bottom:6px">Mark as completed</h3>
                            @if ($selected->end_date_shared_at)
                                <p class="vp-muted" style="margin:0 0 14px">End-date photos were shared on {{ $fmt($selected->end_date_shared_at) }}. Remove the flex, then mark the booking completed.</p>
                            @else
                                <p style="margin:0 0 14px;font-size:13px;padding:8px 10px;background:#FEF3C7;border-radius:8px;color:#92400E">
                                    <i class="fa-solid fa-triangle-exclamation"></i> The end-date photos have not been shared yet.
                                    You can still mark it completed, for example if the flex came down early or the campaign was stopped.
                                </p>
                            @endif
                            <form method="POST" action="{{ route($routes['complete'], $selected->id) }}" style="display:flex;flex-direction:column;gap:12px"
                                data-confirm="Mark this booking as completed? It moves to Completed / Media Remove and can no longer be changed.">
                                @csrf
                                @foreach ($baseQuery as $bk => $bv)<input type="hidden" name="{{ $bk }}" value="{{ $bv }}">@endforeach
                                <label style="display:flex;gap:10px;align-items:center;font-size:14px;margin:0">
                                    <input type="checkbox" name="confirm" value="1" required class="ops-check">
                                    The flex / advertisement has been removed from the site.
                                </label>
                                <div><label class="vp-label" for="rnote">Note (optional)</label><textarea id="rnote" name="removal_note" class="vp-input" maxlength="500" placeholder="e.g. Flex removed early on client request"></textarea></div>
                                <div><button type="submit" class="vp-btn ops-share-btn"><i class="fa-solid fa-flag-checkered"></i>Mark as Completed</button></div>
                            </form>
                        </section>
                    @endif

                    {{-- ================= HISTORY ================= --}}
                    @php
                        $events = collect();
                        foreach (['installation' => 'Installation photos', 'monitoring' => 'Monitoring photos', 'end_date' => 'End-date photos'] as $hs => $hl) {
                            foreach ($photos[$hs] ?? [] as $dk => $shotsSet) {
                                $first = collect($shotsSet)->sortByDesc('updated_at')->first();
                                $events->push(['when' => $first->shared_at ?? $first->updated_at, 'text' => $hl . ($dk !== '_' ? ' (' . $fmt($dk) . ')' : ''),
                                    'note' => count($shotsSet) . ' of 3 uploaded' . ($first->shared_at ? ($noUser ? ' · saved' : ' · shared') : ' · not shared yet')]);
                            }
                        }
                        if ($selected->completed_at) {
                            $events->push(['when' => \Carbon\Carbon::parse($selected->completed_at), 'text' => 'Media removed', 'note' => $selected->removal_note ?: '']);
                        }
                        $events = $events->sortByDesc('when');
                    @endphp
                    <section class="ops-card">
                        <h3 style="margin-bottom:10px">History</h3>
                        @forelse ($events as $ev)
                            <div style="display:flex;gap:12px;padding:10px 0;border-top:1px solid #F1F5F9">
                                <span style="width:10px;height:10px;margin-top:5px;border-radius:999px;background:#F97316;flex:none"></span>
                                <div><b style="font-size:14px">{{ $ev['text'] }}</b>
                                    <div class="vp-muted" style="font-size:12px">{{ $ev['when']->format('d M Y, h:i A') }}{{ $ev['note'] ? ' · ' . $ev['note'] : '' }}</div></div>
                            </div>
                        @empty
                            <p class="vp-muted" style="margin:0">Nothing has happened on this booking yet.</p>
                        @endforelse
                    </section>
                </div>
            @elseif ($list->total() === 0)
                {{-- Empty stage: explain how bookings arrive here and what to do then. --}}
                @php
                    $hint = [
                        'installation' => ['How bookings get here',
                            'A booking appears here as soon as a hoarding is booked (website order, Admin Booking or the vendor\'s Update Availability).',
                            $canEdit ? 'Then: upload the 3 installation photos (Newspaper, Long and Close shot) and click Share with User. The booking moves to Monitoring Pics.'
                                     : 'Then: Brand Adda installs the flex and uploads the 3 installation photos. The booking moves to Monitoring Pics.'],
                        'monitoring' => ['How bookings get here',
                            'A booking moves here once its 3 installation photos are shared (Step 2 · Installation).',
                            $canEdit ? 'Then: while the campaign runs, pick the date the customer asks for in the Monitoring tab, upload 3 photos and share them. Repeat for any date.'
                                     : 'Then: while the campaign runs, Brand Adda adds monitoring photos for the dates the customer asks for.'],
                        'end_date' => ['How bookings get here',
                            'A booking in Monitoring Pics moves here automatically on its campaign end date.',
                            $canEdit ? 'Then: upload the 3 end-date photos and share them, remove the flex, and click Mark as Completed.'
                                     : 'Then: Brand Adda uploads the end-date photos and removes the flex.'],
                        'completed' => ['How bookings get here',
                            'A booking moves here after its end-date photos are shared and the media is marked removed.',
                            'All its photos stay here for reference.'],
                    ][$stage] ?? null;
                @endphp
                <section class="ops-card" style="flex:999 1 560px">
                    @if ($hint)
                        <div style="display:flex;gap:14px;align-items:flex-start">
                            <span class="ops-step-ico" style="background:#FFF7ED;color:#C2410C"><i class="{{ $stageInfo[$stage][0] }}" aria-hidden="true"></i></span>
                            <div>
                                <h3 style="margin-bottom:6px">{{ $stageLabels[$stage] }}: {{ strtolower($hint[0]) }}</h3>
                                <p style="margin:0 0 8px;font-size:14px;color:#334155;line-height:1.55">{{ $hint[1] }}</p>
                                <p style="margin:0;font-size:14px;color:#334155;line-height:1.55">{{ $hint[2] }}</p>
                            </div>
                        </div>
                    @else
                        <p class="vp-muted" style="margin:0">No bookings in this stage right now.</p>
                    @endif
                </section>
            @else
                <section class="ops-card" style="flex:999 1 560px">
                    <p class="vp-muted" style="margin:0">Select a booking on the left to see its details and photos.</p>
                </section>
            @endif
        </div>
    @endif
</div>

<script>
    // A failed upload / share comes back without the #ops-photos anchor: open the Photos section anyway.
    document.addEventListener('DOMContentLoaded', function () {
        if (document.querySelector('[data-ops-focus]')) document.getElementById('ops-photos').scrollIntoView();
    });

    // Photo tabs (Installation / Monitoring / End Date).
    document.addEventListener('click', function (e) {
        const tab = e.target.closest('.ops-tab');
        if (!tab) return;
        const box = tab.closest('.ops-card');
        box.querySelectorAll('.ops-tab').forEach(function (t) { const on = t === tab; t.classList.toggle('is-on', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); });
        box.querySelectorAll('.ops-tabpanel').forEach(function (p) { p.hidden = p.id !== tab.dataset.tab; });
    });
</script>

@if ($canEdit)
    <script>
        // A file picked in a photo slot is moved into that slot's hidden form and sent.
        document.addEventListener('change', function (e) {
            const input = e.target;
            if (!input.classList || !input.classList.contains('js-photo') || !input.files.length) return;
            const form = document.getElementById(input.dataset.form);
            input.name = 'photo';
            input.classList.remove('js-photo');
            form.appendChild(input);
            form.submit();
        });
    </script>
@endif

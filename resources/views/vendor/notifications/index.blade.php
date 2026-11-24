@extends('vendor.layout')
@section('title', 'Notifications & Activity Log')
@section('crumb', 'Vendor Panel / Notifications & Activity Log')
@section('heading', 'Notifications & Activity Log')

@section('actions')
    @if ($unread)
        <form method="POST" action="{{ route('vendor.notifications.read-all') }}" style="margin:0">@csrf
            <button type="submit" class="vp-btn vp-btn-g">Mark all as read</button></form>
    @endif
@endsection

@section('content')
    <div style="display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start">
        <section class="vp-card" style="flex:1 1 380px;padding:8px 20px 12px">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 0 6px">
                <h2 class="vp-h2">Notifications</h2>
                @if ($unread)<span class="vp-badge vp-badge-pending">{{ $unread }} unread</span>@endif
            </div>
            @forelse ($notifications as $n)
                @include('vendor.partials.notification-row', ['n' => $n])
            @empty
                <p class="vp-muted" style="margin:8px 0">No notifications yet.</p>
            @endforelse
        </section>

        <section class="vp-card" style="flex:999 1 560px;min-width:0;overflow:hidden">
            <form method="GET" action="{{ route('vendor.notifications') }}" style="display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:center;padding:18px 20px;border-bottom:1px solid #E5E7EB;margin:0">
                <div><h2 class="vp-h2">Activity Log</h2><div class="vp-muted" style="font-size:12px;margin-top:2px">Last {{ \App\Models\VendorActivityLog::RETENTION_MONTHS }} months</div></div>
                <div style="display:flex;gap:10px">
                    <label for="la" class="sr-only">Activity type</label>
                    <select id="la" name="action" class="vp-input" style="min-height:40px;width:auto" onchange="this.form.submit()">
                        <option value="">All activities</option>
                        @foreach ($actions as $a)<option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>@endforeach
                    </select>
                    <label for="ld" class="sr-only">Period</label>
                    <select id="ld" name="days" class="vp-input" style="min-height:40px;width:auto" onchange="this.form.submit()">
                        <option value="">All time</option>
                        @foreach ([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'] as $d => $l)<option value="{{ $d }}" @selected(request('days') == $d)>{{ $l }}</option>@endforeach
                    </select>
                </div>
            </form>
            <div class="vp-table-wrap">
                <table class="vp-table">
                    <thead><tr><th>Date / Time</th><th>Activity</th><th>Media Code</th><th>Details</th><th>By</th></tr></thead>
                    <tbody>
                        @forelse ($log as $l)
                            <tr>
                                <td class="nowrap" style="color:#334155">{{ $l->created_at->format('d M Y, h:i A') }}</td>
                                <td class="nowrap" style="font-weight:700">{{ $l->action }}</td>
                                <td class="nowrap">{{ $l->media_code ?: '—' }}</td>
                                <td style="color:#334155;line-height:1.5">
                                    @foreach ($l->details ?? [] as $k => $v){{ $k }}: {{ is_array($v) ? implode(' → ', $v) : $v }}@if (!$loop->last) · @endif @endforeach
                                </td>
                                <td class="nowrap" style="color:#334155">{{ $l->actor_type === 'admin' ? 'Brand Adda · ' . $l->actor_name : $l->actor_name }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align:center;padding:40px;color:#475569">No activity yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="vp-pagination"><span class="vp-muted">{{ $log->total() }} entries</span>{{ $log->links('pagination::bootstrap-4') }}</div>
        </section>
    </div>
@endsection

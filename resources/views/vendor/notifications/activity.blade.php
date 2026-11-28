@extends('vendor.layout')
@section('title', 'Activity Log')
@section('crumb', 'Vendor Panel / Activity Log')
@section('heading', 'Activity Log')

@section('content')
    <section class="vp-card" style="min-width:0;overflow:hidden">
        <form method="GET" action="{{ route('vendor.activity') }}" style="display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:center;padding:18px 20px;border-bottom:1px solid #E5E7EB;margin:0">
            <div><h2 class="vp-h2">Activity Log</h2><div class="vp-muted" style="font-size:12px;margin-top:2px">Last {{ \App\Models\VendorActivityLog::RETENTION_MONTHS }} months</div></div>
            <div class="vp-selects">
                <label for="la" class="sr-only">Activity type</label>
                <select id="la" name="action" class="vp-input" onchange="this.form.submit()">
                    <option value="">All activities</option>
                    @foreach ($actions as $a)<option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>@endforeach
                </select>
                <label for="ld" class="sr-only">Period</label>
                <select id="ld" name="days" class="vp-input" onchange="this.form.submit()">
                    <option value="">All time</option>
                    @foreach ([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'] as $d => $l)<option value="{{ $d }}" @selected(request('days') == $d)>{{ $l }}</option>@endforeach
                </select>
                {{-- Clears both filters (All activities, All time). --}}
                <a href="{{ route('vendor.activity') }}" class="vp-btn vp-btn-g" title="Clear filters"><i class="fa-solid fa-rotate-left"></i>Reset</a>
            </div>
        </form>
        <div class="vp-table-wrap">
            <table class="vp-table">
                <thead><tr><th style="width:70px">Sr. No.</th><th>Date / Time</th><th>Activity</th><th>Media Code</th><th>Details</th><th>By</th></tr></thead>
                <tbody>
                    @forelse ($log as $l)
                        <tr>
                            <td class="vp-muted">{{ $log->firstItem() + $loop->index }}</td>
                            <td class="nowrap" style="color:#334155">{{ $l->created_at->format('d M Y, h:i A') }}</td>
                            <td class="nowrap" style="font-weight:700">{{ $l->action }}</td>
                            <td class="nowrap">{{ $l->media_code ?: '—' }}</td>
                            <td style="color:#334155;line-height:1.5">
                                @foreach ($l->details ?? [] as $k => $v){{ $k }}: {{ is_array($v) ? implode(' → ', $v) : $v }}@if (!$loop->last) · @endif @endforeach
                            </td>
                            <td class="nowrap" style="color:#334155">{{ $l->actor_type === 'admin' ? 'Brand Adda · ' . $l->actor_name : $l->actor_name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;padding:40px;color:#475569">
                            @if (request('action') || request('days'))
                                No activity matches these filters. <a href="{{ route('vendor.activity') }}" style="font-weight:700">Reset filters</a>
                            @else
                                No activity yet.
                            @endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="vp-pagination" style="flex-wrap:wrap;gap:12px">
            <span class="vp-muted" style="font-size:13px">
                @if ($log->total())Showing {{ $log->firstItem() }}–{{ $log->lastItem() }} of {{ $log->total() }}@else Nothing to show @endif
                @if ($log->hasPages()) · Page {{ $log->currentPage() }} of {{ $log->lastPage() }}@endif
            </span>
            {{ $log->onEachSide(1)->links('pagination::bootstrap-4') }}
        </div>
    </section>
@endsection

@extends('vendor.layout')
@section('title', 'Media Utilisation Report')
@section('crumb', 'Vendor Panel / Reports')
@section('heading', 'Media Utilisation Report')

@section('actions')
    <a href="{{ route('vendor.reports.utilisation.export', array_merge(['format' => 'excel'], request()->query())) }}" class="vp-btn vp-btn-g"><i class="fa-regular fa-file-excel"></i>Export Excel</a>
    <a href="{{ route('vendor.reports.utilisation.export', array_merge(['format' => 'pdf'], request()->query())) }}" class="vp-btn vp-btn-g"><i class="fa-regular fa-file-pdf"></i>Export PDF</a>
@endsection

@section('content')
    <form class="vp-card vp-card-pad" method="GET" action="{{ route('vendor.reports.utilisation') }}" aria-label="Report filters">
        <div class="vp-grid">
            <div><label class="vp-label" for="r-y">Year</label>
                <select id="r-y" name="year" class="vp-input"><option value="">—</option>
                    @foreach ($years as $y)<option value="{{ $y }}" @selected(request('year') == $y)>{{ $y }}</option>@endforeach</select></div>
            <div><label class="vp-label" for="r-m">Month</label>
                <select id="r-m" name="month" class="vp-input"><option value="">—</option>
                    @foreach ($months as $n => $m)<option value="{{ $n }}" @selected(request('month') == $n)>{{ $m }}</option>@endforeach</select></div>
            <div><label class="vp-label" for="r-f">From Date</label><input id="r-f" type="date" name="from_date" class="vp-input" value="{{ request('from_date') }}"></div>
            <div><label class="vp-label" for="r-t">To Date</label><input id="r-t" type="date" name="to_date" class="vp-input" value="{{ request('to_date') }}"></div>
            <div><label class="vp-label" for="r-md">Media</label>
                <select id="r-md" name="media_id" class="vp-input"><option value="">All Media</option>
                    @foreach ($allMedia as $m)<option value="{{ $m->id }}" @selected(request('media_id') == $m->id)>{{ $m->hoarding_code ?: $m->media_code }} · {{ $m->media_title }}</option>@endforeach</select></div>
            <div><label class="vp-label" for="r-q">Search</label><input id="r-q" type="search" name="q" class="vp-input" placeholder="Media or user name" value="{{ request('q') }}"></div>
            {{-- Filter / Reset on the same row as the fields, at the right end. --}}
            <div class="vp-filter-inline">
                <button type="submit" class="vp-btn vp-btn-n"><i class="fa-solid fa-filter"></i>Filter</button>
                <a href="{{ route('vendor.reports.utilisation') }}" class="vp-btn vp-btn-g"><i class="fa-solid fa-rotate-left"></i>Reset</a>
            </div>
        </div>
    </form>

    <section class="vp-kpis" aria-label="Summary">
        @foreach (['Media in report' => $summary['media'], 'Booked days' => $summary['booked'], 'Average utilisation' => $summary['avg'] . '%', 'Bookings' => $summary['bookings']] as $k => $v)
            <div class="vp-card vp-kpi"><div class="vp-kpi-label"><span>{{ $k }}</span></div><div class="vp-kpi-value" style="font-size:30px">{{ $v }}</div></div>
        @endforeach
    </section>

    <section class="vp-card" style="overflow:hidden">
        <div style="padding:16px 20px;border-bottom:1px solid #E5E7EB;font-size:15px;font-weight:800">
            Media-wise utilisation · {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }} <span class="vp-muted" style="font-weight:500">({{ $days }} days)</span>
        </div>
        <div class="vp-table-wrap">
            <table class="vp-table">
                <thead><tr><th>Sr. No.</th><th>Sector Code</th><th>Media Title</th><th>Location</th><th>Booked By</th><th>Booked Days</th><th>Blocked Days</th><th>Free Days</th><th style="min-width:220px">Utilisation</th></tr></thead>
                <tbody>
                    @forelse ($rows as $r)
                        <tr>
                            <td>{{ $rows->firstItem() + $loop->index }}</td>
                            <td class="nowrap" style="font-weight:700">{{ $r->code }}</td>
                            <td style="font-weight:600">{{ $r->title }}</td>
                            <td>{{ $r->location }}</td>
                            <td>{{ $r->users ?: '—' }}</td>
                            <td>{{ $r->booked }}</td>
                            <td>{{ $r->blocked }}</td>
                            <td>{{ $r->free }}</td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div style="flex:1;height:8px;border-radius:999px;background:#E2E8F0;overflow:hidden" role="img" aria-label="{{ $r->pct }} percent">
                                        <div style="height:8px;border-radius:999px;background:#F97316;width:{{ $r->pct }}%"></div>
                                    </div>
                                    <span style="font-weight:700;min-width:40px;text-align:right">{{ $r->pct }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" style="text-align:center;padding:40px;color:#475569">No approved media matches these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="vp-pagination" style="flex-wrap:wrap;gap:12px">
            <span class="vp-muted" style="font-size:13px">
                @if ($rows->total())Showing {{ $rows->firstItem() }}–{{ $rows->lastItem() }} of {{ $rows->total() }}@else Nothing to show @endif
                @if ($rows->hasPages()) · Page {{ $rows->currentPage() }} of {{ $rows->lastPage() }}@endif
            </span>
            {{ $rows->onEachSide(1)->links('pagination::bootstrap-4') }}
        </div>
        <div class="vp-muted" style="padding:0 20px 14px;font-size:13px">Only your own inventory is included in this report. Excel and PDF include every row.</div>
    </section>
@endsection

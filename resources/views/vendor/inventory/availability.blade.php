@extends('vendor.layout')
@section('title', 'Availability')
@section('crumb', 'Vendor Panel / Availability')
@section('heading', 'Availability & Status')

@section('content')
    @php $labels = \App\Support\MediaAvailability::LABELS; @endphp

    <form class="vp-card vp-card-pad" method="GET" action="{{ route('vendor.availability') }}">
        {{-- One row: three fields, then Reset / Filter. --}}
        <div style="display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr) minmax(0,1fr) auto;gap:16px;align-items:end" class="av-filter-row">
            <div><label class="vp-label" for="a-q">Search</label>
                <input id="a-q" type="search" name="q" class="vp-input" placeholder="Title, area or code" value="{{ $filters['q'] ?? '' }}"></div>
            <div><label class="vp-label" for="a-cat">Category</label>
                <select id="a-cat" name="category_id" class="vp-input">
                    <option value="">All categories</option>
                    @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(($filters['category_id'] ?? '') == $c->id)>{{ $c->category_name }}</option>@endforeach
                </select></div>
            <div><label class="vp-label" for="a-st">Status today</label>
                <select id="a-st" name="status" class="vp-input">
                    <option value="">All statuses</option>
                    @foreach ($labels as $k => $l)<option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $l }}</option>@endforeach
                </select></div>
            <div style="display:flex;gap:10px">
                <a href="{{ route('vendor.availability') }}" class="vp-btn vp-btn-g">Reset</a>
                <button type="submit" class="vp-btn vp-btn-n">Filter</button>
            </div>
        </div>
    </form>
    <style>@media (max-width: 900px) { .av-filter-row { grid-template-columns: 1fr 1fr !important; } }</style>

    <div class="vp-alert vp-alert-info">
        <strong>Available</strong> is the default. <strong>Booked</strong> and <strong>Blocked</strong> need a From and To date.
        <strong>Ask for Availability</strong> keeps the media visible on Brand Adda but buyers must enquire first. Only approved media is listed here.
    </div>

    <section class="vp-card" style="overflow:hidden">
        <div class="vp-table-wrap">
            <table class="vp-table">
                <thead><tr><th style="width:70px">Sr. No.</th><th>Sector Code</th><th>Media Title</th><th>Location</th><th>Status Today</th><th>Upcoming booked / blocked dates</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse ($rows as $r)
                        <tr>
                            <td class="vp-muted">{{ $rows->firstItem() + $loop->index }}</td>
                            <td class="nowrap" style="font-weight:700">{{ $r->hoarding_code ?: '—' }}</td>
                            <td style="font-weight:600">{{ $r->media_title }}</td>
                            <td>{{ $r->area_name }}{{ $r->city_name ? ', ' . $r->city_name : '' }}</td>
                            <td class="nowrap"><span class="vp-badge" style="background:{{ $r->availability['bg'] }};color:{{ $r->availability['fg'] }}">{{ $r->availability['label'] }}</span></td>
                            <td>
                                @forelse ($r->ranges as $range)
                                    <div style="font-size:12px;color:#334155">
                                        {{ ucfirst($range->status_type) }} · {{ \Carbon\Carbon::parse($range->from_date)->format('d M Y') }} – {{ \Carbon\Carbon::parse($range->to_date)->format('d M Y') }}
                                    </div>
                                @empty
                                    <span class="vp-muted">—</span>
                                @endforelse
                            </td>
                            <td class="nowrap">
                                <button type="button" class="vp-btn vp-btn-g vp-btn-sm js-availability" data-url="{{ route('vendor.availability.form', $r->id) }}">
                                    <i class="fa-regular fa-calendar"></i>Update Status</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="text-align:center;padding:40px;color:#475569">No approved media matches these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="vp-pagination" style="flex-wrap:wrap;gap:12px">
            <form method="GET" action="{{ route('vendor.availability') }}" style="display:flex;align-items:center;gap:8px;margin:0">
                @foreach (request()->except(['per_page', 'page']) as $k => $v)
                    @if (is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                @endforeach
                <label for="a-pp" class="vp-muted" style="font-size:13px">Show</label>
                <select id="a-pp" name="per_page" class="vp-input" style="width:auto;min-height:36px;padding:0 8px" onchange="this.form.submit()">
                    @foreach ([10, 20, 50, 100] as $n)<option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>@endforeach
                </select>
                <span class="vp-muted" style="font-size:13px">per page</span>
            </form>
            <span class="vp-muted" style="font-size:13px">
                @if ($rows->total())Showing {{ $rows->firstItem() }}–{{ $rows->lastItem() }} of {{ $rows->total() }}@else 0 media @endif
                @if ($rows->hasPages()) · Page {{ $rows->currentPage() }} of {{ $rows->lastPage() }}@endif
            </span>
            {{ $rows->onEachSide(1)->links('pagination::bootstrap-4') }}
        </div>
    </section>

    @include('vendor.inventory.partials.availability-modal')
@endsection

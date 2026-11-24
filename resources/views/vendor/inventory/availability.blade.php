@extends('vendor.layout')
@section('title', 'Availability')
@section('crumb', 'Vendor Panel / Availability')
@section('heading', 'Availability & Status')

@section('content')
    @php $labels = \App\Support\MediaAvailability::LABELS; @endphp

    <form class="vp-card vp-card-pad" method="GET" action="{{ route('vendor.availability') }}">
        <div class="vp-grid">
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
        </div>
        <div class="vp-filter-actions">
            <a href="{{ route('vendor.availability') }}" class="vp-btn vp-btn-g">Reset</a>
            <button type="submit" class="vp-btn vp-btn-n">Filter</button>
        </div>
    </form>

    <div class="vp-alert vp-alert-info">
        <strong>Available</strong> is the default. <strong>Booked</strong> and <strong>Blocked</strong> need a From and To date.
        <strong>Ask for Availability</strong> keeps the media visible on Brand Adda but buyers must enquire first. Only approved media is listed here.
    </div>

    <section class="vp-card" style="overflow:hidden">
        <div class="vp-table-wrap">
            <table class="vp-table">
                <thead><tr><th>Sector Code</th><th>Media Title</th><th>Location</th><th>Status Today</th><th>Upcoming booked / blocked dates</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse ($rows as $r)
                        <tr>
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
                        <tr><td colspan="6" style="text-align:center;padding:40px;color:#475569">No approved media matches these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @include('vendor.inventory.partials.availability-modal')
@endsection

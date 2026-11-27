@extends('vendor.layout')
@section('title', 'Bookings')
@section('crumb', 'Vendor Panel / Bookings')
@section('heading', 'Bookings')

@section('content')
    @php
        $tabs = [
            ''          => ['All', null],
            'running'   => ['Running now', '#166534'],
            'upcoming'  => ['Upcoming', '#1E40AF'],
            'completed' => ['Completed', '#475569'],
        ];
        $statusStyle = [
            'running'   => ['Running', '#DCFCE7', '#166534'],
            'upcoming'  => ['Upcoming', '#DBEAFE', '#1E40AF'],
            'completed' => ['Completed', '#F1F5F9', '#475569'],
        ];
        $today = now()->startOfDay();
    @endphp

    <style>
        .bk-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
        .vp-body .bk-tab, .bk-tab { display: inline-flex; align-items: center; gap: 8px; min-height: 38px; padding: 0 14px; border-radius: 999px; border: 1px solid #E5E7EB;
            background: #fff; color: #334155; font-weight: 600; font-size: 14px; text-decoration: none; }
        .bk-tab b { min-width: 24px; padding: 1px 7px; border-radius: 999px; background: #F1F5F9; font-size: 12px; text-align: center; }
        .vp-body .bk-tab.active, .bk-tab.active { background: #0F172A; border-color: #0F172A; color: #fff; }
        .bk-tab.active b { background: rgba(255,255,255,.18); color: #fff; }
        .bk-sub { font-size: 12px; color: #64748B; margin-top: 2px; }
    </style>

    <p class="vp-muted" style="margin:0 0 16px">
        Bookings of your media made on Brand Adda. Track installation and photos in
        <a href="{{ route('vendor.operations') }}" style="font-weight:700">Operations</a>.
    </p>

    <section class="vp-card" style="overflow:hidden">
        <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid #E5E7EB">
            <nav class="bk-tabs" aria-label="Booking status">
                @foreach ($tabs as $key => [$label])
                    <a href="{{ route('vendor.bookings', array_filter(['status' => $key, 'q' => request('q')])) }}"
                        class="bk-tab {{ $status === $key ? 'active' : '' }}" @if ($status === $key) aria-current="page" @endif>
                        {{ $label }} <b>{{ $counts[$key ?: 'all'] }}</b></a>
                @endforeach
            </nav>
            <form method="GET" action="{{ route('vendor.bookings') }}" class="vp-search">
                @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
                <label for="bq" class="sr-only">Search bookings</label>
                <input id="bq" type="search" name="q" class="vp-input" placeholder="Booking no., hoarding code or customer" value="{{ request('q') }}">
                <button class="vp-btn vp-btn-n">Search</button>
                @if (request('q'))
                    <a href="{{ route('vendor.bookings', array_filter(['status' => $status])) }}" class="vp-btn vp-btn-g">Reset</a>
                @endif
            </form>
        </div>

        <div class="vp-table-wrap">
            <table class="vp-table">
                <thead>
                    <tr>
                        <th style="width:70px">Sr. No.</th>
                        <th>Booking</th>
                        <th>Media</th>
                        <th>Customer</th>
                        <th>Booking Period</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $b)
                        @php
                            $from = \Carbon\Carbon::parse($b->from_date)->startOfDay();
                            $to = \Carbon\Carbon::parse($b->to_date)->startOfDay();
                            $state = $to->lt($today) ? 'completed' : ($from->gt($today) ? 'upcoming' : 'running');
                            [$sLabel, $sBg, $sFg] = $statusStyle[$state];
                        @endphp
                        <tr>
                            <td class="vp-muted">{{ $bookings->firstItem() + $loop->index }}</td>
                            <td class="nowrap">
                                <strong>{{ $b->order_no }}</strong>
                                <div class="bk-sub">{{ $b->payment_status === 'PAID' ? 'Website order' : 'Booked by Brand Adda' }}</div>
                            </td>
                            <td style="min-width:200px">
                                <a href="{{ route('vendor.media.show', $b->media_id) }}" style="font-weight:700">{{ $b->hoarding_code ?: '—' }}</a>
                                <div class="bk-sub" style="color:#0F172A">{{ $b->media_title }}</div>
                                @if ($b->area_name)<div class="bk-sub">{{ $b->area_name }}</div>@endif
                            </td>
                            <td style="min-width:180px">
                                {{ $b->user_name ?: '—' }}
                                <div class="bk-sub">{{ collect([$b->user_email, $b->mobile_number])->filter()->implode(' · ') ?: '—' }}</div>
                            </td>
                            <td class="nowrap">
                                {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}
                                <div class="bk-sub">{{ $from->diffInDays($to) + 1 }} days</div>
                            </td>
                            <td><span class="vp-badge" style="background:{{ $sBg }};color:{{ $sFg }}">{{ $sLabel }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;padding:40px;color:#475569">
                            {{ request('q') || $status ? 'No bookings match this view.' : 'No bookings on your media yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="vp-pagination">
            <span class="vp-muted">{{ $bookings->total() }} booking(s)</span>
            {{ $bookings->links('pagination::bootstrap-4') }}
        </div>
    </section>
@endsection

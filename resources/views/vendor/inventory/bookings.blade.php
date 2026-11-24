@extends('vendor.layout')
@section('title', 'Bookings')
@section('crumb', 'Vendor Panel / Bookings')
@section('heading', 'Bookings')

@section('content')
    <div class="vp-alert vp-alert-info">
        Bookings on your media made through Brand Adda (website orders and Admin Booking). Booking is handled by the Brand Adda team;
        track installation and photos in <a href="{{ route('vendor.operations') }}" style="font-weight:700">Operations</a>.
    </div>

    <section class="vp-card" style="overflow:hidden">
        <div style="display:flex;justify-content:flex-end;padding:16px 20px;border-bottom:1px solid #E5E7EB">
            <form method="GET" action="{{ route('vendor.bookings') }}">
                <label for="bq" class="sr-only">Search bookings</label>
                <input id="bq" type="search" name="q" class="vp-input" style="width:300px" placeholder="Booking no., hoarding code or user" value="{{ request('q') }}">
            </form>
        </div>
        <div class="vp-table-wrap">
            <table class="vp-table">
                <thead><tr><th>Booking No.</th><th>Hoarding Code</th><th>Media</th><th>User</th><th>Email ID</th><th>Mobile</th><th>Start Date</th><th>End Date</th><th>Type</th></tr></thead>
                <tbody>
                    @forelse ($bookings as $b)
                        <tr>
                            <td class="nowrap" style="font-weight:700">{{ $b->order_no }}</td>
                            <td class="nowrap"><a href="{{ route('vendor.media.show', $b->media_id) }}">{{ $b->hoarding_code ?: '—' }}</a></td>
                            <td>{{ $b->media_title }}{{ $b->area_name ? ', ' . $b->area_name : '' }}</td>
                            <td class="nowrap">{{ $b->user_name ?: '—' }}</td>
                            <td class="nowrap">{{ $b->user_email ?: '—' }}</td>
                            <td class="nowrap">{{ $b->mobile_number ?: '—' }}</td>
                            <td class="nowrap">{{ \Carbon\Carbon::parse($b->from_date)->format('d M Y') }}</td>
                            <td class="nowrap">{{ \Carbon\Carbon::parse($b->to_date)->format('d M Y') }}</td>
                            <td class="nowrap"><span class="vp-badge" style="background:#DBEAFE;color:#1E40AF">{{ $b->payment_status === 'PAID' ? 'Website' : 'Admin Booking' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" style="text-align:center;padding:40px;color:#475569">No bookings on your media yet.</td></tr>
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

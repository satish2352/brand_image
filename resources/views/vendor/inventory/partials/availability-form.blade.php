@php
    $options = [
        'available' => ['Available', 'Default, no dates'],
        'booked'    => ['Booked', 'From and To date required'],
        'blocked'   => ['Blocked', 'From and To date required'],
        'ask'       => ['Ask for Availability', 'No dates needed'],
    ];
    $selected = $current['status'];
@endphp
<form method="POST" action="{{ route('vendor.availability.update', $media->id) }}">
    @csrf
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:20px 24px;border-bottom:1px solid #E5E7EB">
        <div>
            <h2 id="availabilityTitle" style="margin:0;font-size:20px;font-weight:800">Update Availability</h2>
            <div class="vp-muted" style="margin-top:4px">{{ $media->display_code }} · {{ $media->media_title }}</div>
        </div>
        <button type="button" class="vp-ib" data-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div style="padding:22px 24px;display:flex;flex-direction:column;gap:18px">
        <div style="display:flex;align-items:center;gap:10px;font-size:14px">
            <span class="vp-muted" style="font-size:14px">Current status:</span>
            <span class="vp-badge" style="background:{{ $current['bg'] }};color:{{ $current['fg'] }}">{{ $current['label'] }}</span>
        </div>

        <fieldset style="border:0;margin:0;padding:0">
            <legend class="vp-label">New Status</legend>
            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px">
                @foreach ($options as $value => [$label, $hint])
                    @php $on = $value === $selected; @endphp
                    <label class="js-status-card" style="display:flex;gap:10px;align-items:flex-start;padding:12px 14px;border-radius:12px;cursor:pointer;margin:0;border:{{ $on ? '2px solid #F97316' : '1px solid #CBD5E1' }};background:{{ $on ? '#FFF7ED' : '#fff' }}">
                        <input type="radio" name="status" value="{{ $value }}" @checked($on) style="width:18px;height:18px;margin-top:2px;accent-color:#F97316">
                        <span><span style="display:block;font-size:14px;font-weight:700">{{ $label }}</span><span class="vp-muted" style="font-size:12px">{{ $hint }}</span></span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="js-dates" style="display:{{ in_array($selected, ['booked', 'blocked']) ? 'grid' : 'none' }};grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
            <div><label class="vp-label" for="av-from">From Date <span style="color:#B91C1C">*</span></label>
                <input id="av-from" type="date" name="from_date" class="vp-input" min="{{ now()->toDateString() }}"></div>
            <div><label class="vp-label" for="av-to">To Date <span style="color:#B91C1C">*</span></label>
                <input id="av-to" type="date" name="to_date" class="vp-input" min="{{ now()->toDateString() }}"></div>
        </div>

        <div style="border-radius:12px;background:#F8FAFC;border:1px solid #E5E7EB;padding:14px 16px">
            <div style="font-size:13px;font-weight:700;margin-bottom:8px">Already booked / blocked dates</div>
            @forelse ($ranges as $r)
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;font-size:13px;color:#334155;padding:4px 0">
                    <span>{{ \Carbon\Carbon::parse($r->from_date)->format('d M Y') }} – {{ \Carbon\Carbon::parse($r->to_date)->format('d M Y') }}</span>
                    <span style="display:flex;gap:6px;align-items:center">
                        <span class="vp-badge" style="{{ $r->status_type === 'blocked' ? 'background:#FEE2E2;color:#991B1B' : 'background:#DBEAFE;color:#1E40AF' }}">
                            {{ ucfirst($r->status_type) }}{{ $r->source === 'vendor' ? ' · by you' : ' · Brand Adda' }}</span>
                        @if ($r->source === 'vendor')
                            <button type="submit" form="rm-range-{{ $r->id }}" class="vp-ib danger" style="width:30px;height:30px" aria-label="Remove these dates"><i class="fa-solid fa-xmark"></i></button>
                        @endif
                    </span>
                </div>
            @empty
                <div class="vp-muted">No upcoming booked or blocked dates.</div>
            @endforelse
            <div class="vp-muted" style="font-size:12px;margin-top:10px">Overlapping dates are not allowed. Changes sync to Brand Adda instantly.</div>
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:12px;padding:16px 24px;border-top:1px solid #E5E7EB;background:#F8FAFC;border-radius:0 0 16px 16px">
        <button type="button" class="vp-btn vp-btn-g" data-dismiss="modal">Cancel</button>
        <button type="submit" class="vp-btn vp-btn-o">Update Status</button>
    </div>
</form>
@foreach ($ranges->where('source', 'vendor') as $r)
    <form id="rm-range-{{ $r->id }}" method="POST" action="{{ route('vendor.availability.range.remove', [$media->id, $r->id]) }}" data-confirm="Remove these dates?" data-confirm-button="Yes, remove">@csrf</form>
@endforeach

@extends('vendor.layout')
@section('title', 'Dashboard')
@section('crumb', 'Vendor Panel / Dashboard')
@section('heading', 'Dashboard')

@section('content')
    @php
        $tiles = [
            ['Total Inventory', $status['total'], 'Approved & assigned media', '#0F172A'],
            ['Available', $status['available'], 'Open for booking', '#16A34A'],
            ['Booked', $status['booked'], 'With an active booking', '#2563EB'],
            ['Blocked', $status['blocked'], 'Held for a date range', '#DC2626'],
            ['Ask for Availability', $status['ask'], 'Confirm on enquiry', '#D97706'],
        ];
        $hints = [
            'pending' => 'No action yet',
            'installation' => 'Photos due',
            'monitoring' => 'Running',
            'end_date' => 'End date reached',
            'completed' => 'Removed',
        ];
    @endphp

    <section class="vp-card vp-card-pad" style="display:flex;flex-wrap:wrap;gap:20px;align-items:center;justify-content:space-between">
        <div style="display:flex;gap:18px;align-items:center">
            <div style="width:72px;height:72px;border-radius:16px;background:#FFF7ED;border:2px dashed #FDBA74;display:grid;place-items:center;font-weight:800;font-size:22px;color:#9A3412;overflow:hidden">
                @if ($vendor->logo_url)
                    <img src="{{ $vendor->logo_url }}" alt="{{ $vendor->vendor_name }} logo" style="width:100%;height:100%;object-fit:contain;background:#fff">
                @else
                    {{ $vendor->initials }}
                @endif
            </div>
            <div>
                <div style="font-size:22px;font-weight:800">{{ $vendor->vendor_name }}</div>
                <div class="vp-muted" style="font-size:14px;margin-top:4px">Your website:
                    <a href="{{ $vendor->website_url }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://#', '', $vendor->website_url) }}</a>
                </div>
            </div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:12px">
            <a href="{{ route('vendor.media.create') }}" class="vp-btn vp-btn-o"><i class="fa-solid fa-plus"></i>Add Media</a>
            <a href="{{ route('vendor.availability') }}" class="vp-btn vp-btn-g">Update Availability</a>
            <a href="{{ route('vendor.inventory.export') }}" class="vp-btn vp-btn-g">Export Inventory</a>
        </div>
    </section>

    <section class="vp-kpis" aria-label="Inventory summary">
        @foreach ($tiles as [$label, $value, $sub, $dot])
            <div class="vp-card vp-kpi">
                <div class="vp-kpi-label"><span>{{ $label }}</span><span class="vp-kpi-dot" style="background:{{ $dot }}"></span></div>
                <div class="vp-kpi-value">{{ $value }}</div>
                <div class="vp-muted" style="font-size:12px">{{ $sub }}</div>
            </div>
        @endforeach
    </section>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));gap:24px;align-items:start">
        <section class="vp-card vp-card-pad">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                <h2 class="vp-h2">Upcoming Availability</h2>
                <a href="{{ route('vendor.availability') }}" style="font-size:13px;font-weight:700">View all</a>
            </div>
            @forelse ($upcoming as $u)
                <div style="display:flex;gap:14px;align-items:center;padding:12px 0;border-top:1px solid #F1F5F9">
                    <div style="flex:1;min-width:0">
                        <div style="font-size:14px;font-weight:700">{{ $u->media_title }}{{ $u->area_name ? ', ' . $u->area_name : '' }}</div>
                        <div class="vp-muted" style="font-size:12px;margin-top:2px">{{ $u->hoarding_code ?: $u->media_code }} · till {{ \Carbon\Carbon::parse($u->to_date)->format('d M Y') }}</div>
                    </div>
                    <span class="vp-badge" style="background:{{ $u->bg }};color:{{ $u->fg }}">{{ $u->label }}</span>
                    <div style="text-align:right;min-width:108px">
                        <div class="vp-muted" style="font-size:11px">Available from</div>
                        <div style="font-size:13px;font-weight:700;color:#166534">{{ $u->available_from->format('d M Y') }}</div>
                    </div>
                </div>
            @empty
                <p class="vp-muted" style="margin:8px 0 0">None of your media is booked or blocked today.</p>
            @endforelse
        </section>

        <div style="display:flex;flex-direction:column;gap:24px">
            <section class="vp-card vp-card-pad">
                <h2 class="vp-h2" style="margin-bottom:14px">Media Approval Status</h2>
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px">
                    <a href="{{ route('vendor.inventory', ['approval' => 'pending']) }}" style="background:#FFEDD5;border-radius:12px;padding:14px;text-decoration:none;color:#9A3412">
                        <div style="font-size:26px;font-weight:800">{{ $approval['pending'] }}</div><div style="font-size:13px;font-weight:600">Pending Approval</div></a>
                    <a href="{{ route('vendor.inventory', ['approval' => 'approved']) }}" style="background:#DCFCE7;border-radius:12px;padding:14px;text-decoration:none;color:#166534">
                        <div style="font-size:26px;font-weight:800">{{ $approval['approved'] }}</div><div style="font-size:13px;font-weight:600">Approved</div></a>
                    <a href="{{ route('vendor.inventory', ['approval' => 'rejected']) }}" style="background:#FEE2E2;border-radius:12px;padding:14px;text-decoration:none;color:#991B1B">
                        <div style="font-size:26px;font-weight:800">{{ $approval['rejected'] }}</div><div style="font-size:13px;font-weight:600">Rejected</div></a>
                </div>
                @if ($latestRejected)
                    <div style="margin-top:14px;padding:12px 14px;border-radius:10px;background:#FEF2F2;font-size:13px;color:#7F1D1D">
                        <strong>{{ $latestRejected->hoarding_code ?: $latestRejected->media_code ?: $latestRejected->media_title }} rejected:</strong>
                        “{{ $latestRejected->rejection_reason }}”
                        <a href="{{ route('vendor.media.edit', $latestRejected->id) }}" style="font-weight:700">Edit &amp; resubmit</a>
                    </div>
                @endif
            </section>

            <section class="vp-card vp-card-pad">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                    <h2 class="vp-h2">Operations at a Glance</h2>
                    <a href="{{ route('vendor.operations') }}" style="font-size:13px;font-weight:700">Open</a>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px">
                    @foreach ($stageLabels as $key => $label)
                        <a href="{{ route('vendor.operations', ['stage' => $key]) }}" style="display:flex;align-items:center;gap:12px;text-decoration:none;color:#0F172A">
                            <span style="width:26px;height:26px;border-radius:999px;background:#0F172A;color:#fff;display:grid;place-items:center;font-size:12px;font-weight:800">{{ $loop->iteration }}</span>
                            <span style="flex:1;font-size:14px;font-weight:600">{{ $label }}</span>
                            <span class="vp-muted" style="font-size:12px">{{ $hints[$key] }}</span>
                            <span style="min-width:36px;text-align:right;font-size:16px;font-weight:800">{{ $stages[$key] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    <section class="vp-card vp-card-pad">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
            <h2 class="vp-h2">Recent Notifications</h2>
            <a href="{{ route('vendor.notifications') }}" style="font-size:13px;font-weight:700">See all</a>
        </div>
        @forelse ($notifications as $n)
            @include('vendor.partials.notification-row', ['n' => $n])
        @empty
            <p class="vp-muted" style="margin:8px 0 0">No notifications yet.</p>
        @endforelse
    </section>
@endsection

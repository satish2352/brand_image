{{-- Admin bell popup. Two sources: booking/payment notifications
     ($notifications, unread only, as before) and Vendor Inventory module
     notifications ($vendorNotifications, latest 15; unread ones flagged). --}}
@php
    $vendorNotifications = $vendorNotifications ?? collect();
    $vendorIcons = [
        'media_submitted'      => ['mdi-upload', '#9A3412', '#FFEDD5'],
        'media_added'          => ['mdi-plus-circle', '#9A3412', '#FFEDD5'],
        'media_updated'        => ['mdi-pencil', '#1E40AF', '#DBEAFE'],
        'media_deleted'        => ['mdi-delete', '#991B1B', '#FEE2E2'],
        'availability_updated' => ['mdi-calendar-check', '#1E40AF', '#DBEAFE'],
        'rate_updated'         => ['mdi-currency-inr', '#166534', '#DCFCE7'],
        'installation_photos'  => ['mdi-camera', '#166534', '#DCFCE7'],
        'monitoring_photos'    => ['mdi-camera', '#166534', '#DCFCE7'],
        'end_date_photos'      => ['mdi-camera', '#166534', '#DCFCE7'],
        'media_removed'        => ['mdi-check-all', '#166534', '#DCFCE7'],
    ];
@endphp

<style>
    .bn-section { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; margin: 0 0 10px; display: flex; justify-content: space-between; align-items: center; }
    .bn-item { display: flex; gap: 12px; padding: 10px 8px; border-bottom: 1px solid #f1f5f9; text-decoration: none !important; color: inherit !important; border-radius: 8px; }
    .bn-item:hover { background: #f8fafc; }
    .bn-item.bn-unread { background: #FFFBF5; }
    .bn-icon { width: 34px; height: 34px; flex: none; border-radius: 9px; display: grid; place-items: center; font-size: 18px; }
    .bn-new { display: inline-block; margin-left: 6px; padding: 1px 8px; border-radius: 999px; background: #FFEDD5; color: #9A3412; font-size: 11px; font-weight: 700; }
</style>

{{-- ================= BOOKINGS ================= --}}
<div class="bn-section"><span>Bookings &amp; Payments</span></div>
@if ($notifications->count() == 0)
    <p class="text-muted mb-4">No new booking notifications.</p>
@else
    <ul style="padding-left:20px; list-style:disc;" class="mb-4">
        @foreach ($notifications as $noti)
            @php
                $firstItem = $noti->order?->items?->first();
            @endphp

            <li class="mb-3 border-bottom pb-2">
                <a href="{{ route('admin.notifications.read', $noti->id) }}" style="text-decoration:none;color:inherit;">
                    <strong>
                        {{ $noti->order?->customer?->name ?? 'Unknown User' }}

                        @if ($noti->media_id)
                            booked {{ $noti->media?->media_title ?? 'N/A' }}
                        @else
                            completed payment for order {{ $noti->order?->order_no }}
                        @endif
                    </strong>
                    <br>

                    @if ($noti->media_id)
                        From {{ \Carbon\Carbon::parse($firstItem?->from_date)->format('d M Y') ?? '-' }}
                        To {{ \Carbon\Carbon::parse($firstItem?->to_date)->format('d M Y') ?? '-' }}
                        <br>
                    @endif

                    <small class="text-muted">
                        {{ $noti->created_at->diffForHumans() }}
                    </small>
                </a>
            </li>
        @endforeach
    </ul>
@endif

{{-- ================= VENDOR ================= --}}
<div class="bn-section">
    <span>Vendor Notifications</span>
    <a href="{{ route('admin.vendor.notifications') }}" style="text-transform:none;letter-spacing:0;font-weight:600">View all</a>
</div>
@forelse ($vendorNotifications as $vn)
    @php [$icon, $fg, $bg] = $vendorIcons[$vn->type] ?? ['mdi-bell', '#334155', '#F1F5F9']; @endphp
    <a href="{{ route('admin.vendor.notifications.open', $vn->id) }}" class="bn-item {{ $vn->is_read ? '' : 'bn-unread' }}">
        <span class="bn-icon" style="background:{{ $bg }};color:{{ $fg }}"><i class="mdi {{ $icon }}"></i></span>
        <span style="flex:1;min-width:0">
            <strong style="font-size:14px">{{ $vn->title }}</strong>
            @unless ($vn->is_read)<span class="bn-new">New</span>@endunless
            @if ($vn->message)<span class="d-block" style="font-size:13px;color:#374151">{{ $vn->message }}</span>@endif
            <small class="text-muted">{{ $vn->vendor?->vendor_name ? $vn->vendor->vendor_name . ' · ' : '' }}{{ $vn->created_at->diffForHumans() }}</small>
        </span>
    </a>
@empty
    <p class="text-muted mb-0">No vendor notifications yet.</p>
@endforelse

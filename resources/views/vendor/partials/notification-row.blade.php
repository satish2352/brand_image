@php
    $nIcons = [
        'media_approved'     => ['fa-solid fa-check', '#DCFCE7', '#166534'],
        'media_rejected'     => ['fa-solid fa-xmark', '#FEE2E2', '#991B1B'],
        'inventory_assigned' => ['fa-solid fa-plus', '#FFEDD5', '#9A3412'],
        'inventory_removed'  => ['fa-solid fa-minus', '#F1F5F9', '#334155'],
        'inventory_updated'  => ['fa-solid fa-pen', '#DBEAFE', '#1E40AF'],
        'booking_updated'    => ['fa-regular fa-calendar', '#DBEAFE', '#1E40AF'],
        'enquiry_received'   => ['fa-regular fa-envelope', '#FFEDD5', '#9A3412'],
        // Operations: photos uploaded / shared and removal recorded by Brand Adda.
        'installation_photos' => ['fa-solid fa-camera', '#FFEDD5', '#9A3412'],
        'monitoring_photos'   => ['fa-solid fa-camera', '#FFEDD5', '#9A3412'],
        'end_date_photos'     => ['fa-solid fa-camera', '#FFEDD5', '#9A3412'],
        'photos_shared'       => ['fa-regular fa-paper-plane', '#DCFCE7', '#166534'],
        'media_removed'       => ['fa-solid fa-flag-checkered', '#F1F5F9', '#334155'],
    ];
    [$nIcon, $nBg, $nFg] = $nIcons[$n->type] ?? ['fa-regular fa-bell', '#F1F5F9', '#334155'];
@endphp
{{-- The whole row opens what the notification is about. --}}
<a href="{{ route('vendor.notifications.open', $n->id) }}" class="vp-note {{ $n->is_read ? '' : 'is-new' }}">
    <span style="width:36px;height:36px;flex:none;border-radius:10px;display:grid;place-items:center;background:{{ $nBg }};color:{{ $nFg }}"><i class="{{ $nIcon }}" aria-hidden="true"></i></span>
    <div style="flex:1;min-width:0">
        <div style="font-size:14px;font-weight:700">
            {{ $n->title }}
            @unless ($n->is_read)<span class="vp-badge vp-badge-pending" style="margin-left:6px;font-size:10px;padding:2px 8px">New</span>@endunless
        </div>
        @if ($n->message)<div style="font-size:13px;color:#334155;margin-top:2px;line-height:1.5">{{ $n->message }}</div>@endif
    </div>
    <span class="vp-muted" style="font-size:12px;white-space:nowrap">{{ $n->created_at->diffForHumans() }}</span>
</a>

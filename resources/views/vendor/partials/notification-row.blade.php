@php
    $nIcons = [
        'media_approved'     => ['fa-solid fa-check', '#DCFCE7', '#166534'],
        'media_rejected'     => ['fa-solid fa-xmark', '#FEE2E2', '#991B1B'],
        'inventory_assigned' => ['fa-solid fa-plus', '#FFEDD5', '#9A3412'],
        'inventory_removed'  => ['fa-solid fa-minus', '#F1F5F9', '#334155'],
        'inventory_updated'  => ['fa-solid fa-pen', '#DBEAFE', '#1E40AF'],
        'booking_updated'    => ['fa-regular fa-calendar', '#DBEAFE', '#1E40AF'],
    ];
    [$nIcon, $nBg, $nFg] = $nIcons[$n->type] ?? ['fa-regular fa-bell', '#F1F5F9', '#334155'];
    $nHref = $n->link ? route('vendor.notifications.open', $n->id) : null;
@endphp
<div style="display:flex;gap:14px;align-items:flex-start;padding:12px 0;border-top:1px solid #F1F5F9;{{ $n->is_read ? '' : 'background:#FFFBF5;margin:0 -12px;padding:12px' }}">
    <span style="width:36px;height:36px;flex:none;border-radius:10px;display:grid;place-items:center;background:{{ $nBg }};color:{{ $nFg }}"><i class="{{ $nIcon }}" aria-hidden="true"></i></span>
    <div style="flex:1;min-width:0">
        <div style="font-size:14px;font-weight:700">
            @if ($nHref)<a href="{{ $nHref }}" style="color:#0F172A">{{ $n->title }}</a>@else{{ $n->title }}@endif
            @unless ($n->is_read)<span class="vp-badge vp-badge-pending" style="margin-left:6px;font-size:10px;padding:2px 8px">New</span>@endunless
        </div>
        @if ($n->message)<div style="font-size:13px;color:#334155;margin-top:2px;line-height:1.5">{{ $n->message }}</div>@endif
    </div>
    <span class="vp-muted" style="font-size:12px;white-space:nowrap">{{ $n->created_at->diffForHumans() }}</span>
</div>

@php $am = $approvalMedia; @endphp
@if ($am->approval_status === 'rejected')
    <div class="vp-alert vp-alert-error" role="alert" style="margin-bottom:16px">
        <div style="font-weight:800">Rejected by Brand Adda{{ $am->reviewed_at ? ' on ' . \Carbon\Carbon::parse($am->reviewed_at)->format('d M Y') : '' }}</div>
        <div style="margin-top:4px">Reason: “{{ $am->rejection_reason }}”</div>
        <div style="margin-top:6px;font-size:13px">Correct the details below and click <strong>Resubmit for Approval</strong>. To change photos, use the media’s detail page.</div>
    </div>
@elseif ($am->approval_status === 'pending')
    <div class="vp-alert vp-alert-info" style="margin-bottom:16px">
        This media is <span class="vp-badge vp-badge-pending">Pending Approval</span>. You can still correct it; Brand Adda will review the latest details.
    </div>
@endif
<div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:18px;font-size:13px;color:#475569">
    <span>Vendor:</span>
    <span class="vp-badge" style="background:#F1F5F9;color:#0F172A"><i class="fa-solid fa-lock" style="margin-right:6px"></i>{{ $lockedVendor->vendor_name }} (auto)</span>
    @if ($approvalHistory->isNotEmpty())
        <span style="margin-left:12px">History:</span>
        @foreach ($approvalHistory as $h)
            <span class="vp-badge {{ $h->action === 'Media Approved' ? 'vp-badge-approved' : ($h->action === 'Media Rejected' ? 'vp-badge-rejected' : 'vp-badge-pending') }}">
                {{ str_replace('Media ', '', $h->action) }} · {{ $h->created_at->format('d M Y') }}
            </span>
        @endforeach
    @endif
</div>

@extends('superadm.layout.master')

@section('content')
    @php
        use App\Support\MediaImageUrl;

        $status = $media->approval_status;
        $code = $media->hoarding_code ?: ($media->media_code ?: '#' . $media->id);
        $fmt = fn($d, $f = 'd M Y, h:i A') => $d ? \Carbon\Carbon::parse($d)->format($f) : '—';
        $statusClass = ['pending' => 'vr-pending', 'approved' => 'vr-approved', 'rejected' => 'vr-rejected'][$status] ?? 'vr-pending';
        $size = $sizes->isNotEmpty()
            ? $sizes->map(fn($s) => ucfirst($s->position) . ': ' . (float) $s->width . ' × ' . (float) $s->height . ' ft')->implode(' · ')
            : ($media->width && $media->height ? (float) $media->width . ' × ' . (float) $media->height . ' ft' : '—');

        $sections = [
            'Vendor Details' => [
                'Vendor Name' => $media->vendor_name ?: 'Not assigned',
                'Vendor Code' => $media->vendor_code ?: '—',
                'Email' => $media->vendor_email ?: '—',
                'Mobile' => $media->vendor_mobile ?: '—',
            ],
            'Media Details' => [
                \App\Support\MediaCode::labelForCode($media->hoarding_code) => $media->hoarding_code ?: '—',
                'Media Code' => $media->media_code ?: '—',
                'Category' => $media->category_name ?: '—',
                'Media Title' => $media->media_title ?: '—',
                'Illumination' => $media->illumination_name ?: '—',
                'Facing' => $media->facing ?: '—',
            ],
            'Location' => [
                'State' => $media->state_name ?: '—',
                'District' => $media->district_name ?: '—',
                'City / Town' => $media->city_name ?: '—',
                'Area' => $media->area_name ?: '—',
                'Area Type' => $media->areatype_name ?: '—',
                'Highway' => $media->highway_name ?: '—',
                'Landmarks' => $landmarks->implode(', ') ?: '—',
                'Latitude / Longitude' => $media->latitude . ', ' . $media->longitude,
                'Address' => $media->address ?: '—',
            ],
            'Size & Pricing' => [
                'Size' => $size,
                'Area (sq.ft)' => $media->area_auto ? (float) $media->area_auto : '—',
                'Monthly Price' => '₹ ' . number_format((float) $media->price, 2),
            ],
        ];
    @endphp

    <style>
        .vr-card { background: #fff; border-radius: 12px; box-shadow: 0 8px 20px rgba(0, 0, 0, .06); margin-bottom: 24px; overflow: hidden; }
        .vr-card-header { background: #00929c; color: #fff; padding: 12px 20px; font-weight: 600; font-size: 16px; display: flex; justify-content: space-between; align-items: center; }
        .vr-card-body { padding: 20px; }
        .vr-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 24px; }
        .vr-label { font-size: 13px; color: #6b7280; }
        .vr-value { font-size: 15px; font-weight: 500; color: #222; word-break: break-word; }
        .vr-wide { grid-column: 1 / -1; }
        .vr-badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 13px; font-weight: 600; vertical-align: middle; }
        .vr-pending { background: #FFEDD5; color: #9A3412; }
        .vr-approved { background: #DCFCE7; color: #166534; }
        .vr-rejected { background: #FEE2E2; color: #991B1B; }
        .vr-photo { display: block; border-radius: 10px; overflow: hidden; border: 1px solid #e5e7eb; }
        .vr-photo img { width: 100%; height: 150px; object-fit: cover; transition: .3s; }
        .vr-photo:hover img { transform: scale(1.05); }
        .vr-timeline { list-style: none; margin: 0; padding: 0; }
        .vr-timeline li { position: relative; padding: 0 0 16px 22px; }
        .vr-timeline li::before { content: ''; position: absolute; left: 0; top: 5px; width: 10px; height: 10px; border-radius: 50%; background: #00929c; }
        .vr-timeline li::after { content: ''; position: absolute; left: 4px; top: 17px; bottom: 0; width: 2px; background: #e5e7eb; }
        .vr-timeline li:last-child::after { display: none; }
        .vr-timeline li.vr-t-approved::before { background: #16A34A; }
        .vr-timeline li.vr-t-rejected::before { background: #DC2626; }
        .vr-timeline li.vr-t-submitted::before { background: #F97316; }
        .vr-sticky { position: sticky; top: 80px; }
        @media (max-width: 768px) { .vr-grid { grid-template-columns: 1fr; } .vr-sticky { position: static; } }
    </style>

    <div class="container-fluid mt-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:12px">
            <a href="{{ route('admin.vendor.approvals', ['status' => $status]) }}" class="btn btn-outline-secondary">
                ← Back to Vendor Media Approvals
            </a>
            <a href="{{ route('media.edit', base64_encode($media->id)) }}" class="btn btn-outline-info">
                <i class="mdi mdi-pencil"></i> Open in Media Management
            </a>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        {{-- ================= SUMMARY ================= --}}
        <div class="vr-card">
            <div class="vr-card-body d-flex flex-wrap justify-content-between align-items-center" style="gap:16px">
                <div>
                    <h3 class="mb-1" style="font-weight:600">
                        {{ $code }}
                        <span class="vr-badge {{ $statusClass }} ml-2">{{ $labels[$status] ?? ucfirst($status) }}</span>
                    </h3>
                    <div class="vr-label" style="font-size:15px">{{ $media->media_title }} · {{ $media->category_name }}</div>
                </div>
                <div class="d-flex flex-wrap" style="gap:28px">
                    <div><div class="vr-label">Vendor</div><div class="vr-value">{{ $media->vendor_name ?: '—' }}</div></div>
                    <div><div class="vr-label">Submitted</div><div class="vr-value">{{ $fmt($media->submitted_at) }}</div></div>
                    <div><div class="vr-label">Monthly Price</div><div class="vr-value">₹ {{ number_format((float) $media->price) }}</div></div>
                </div>
            </div>
        </div>

        @if ($status === 'rejected' && $media->rejection_reason)
            <div class="alert alert-danger"><strong>Rejected{{ $media->reviewer_name ? ' by ' . $media->reviewer_name : '' }} on {{ $fmt($media->reviewed_at, 'd M Y') }}:</strong> {{ $media->rejection_reason }}</div>
        @elseif ($status === 'pending')
            <div class="alert alert-warning mb-4">This media is <strong>waiting for approval</strong>. It is hidden from the website and cannot be booked until it is approved.</div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                {{-- ================= PHOTOS ================= --}}
                <div class="vr-card">
                    <div class="vr-card-header"><span>Photos</span><span style="font-weight:400;font-size:14px">{{ $images->count() }} uploaded</span></div>
                    <div class="vr-card-body">
                        <div class="row">
                            @forelse ($images as $img)
                                @php $url = MediaImageUrl::for($img->images); @endphp
                                <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                    <a href="{{ $url }}" target="_blank" rel="noopener" class="vr-photo" title="Open full size">
                                        <img src="{{ $url }}" alt="Photo {{ $loop->iteration }} of {{ $code }}" loading="lazy">
                                    </a>
                                </div>
                            @empty
                                <div class="col-12 text-muted">No photos uploaded.</div>
                            @endforelse
                        </div>
                        @if ($media->panorama_image)
                            <div class="d-flex mt-1">
                                <a href="{{ MediaImageUrl::for($media->panorama_image) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" style="width:auto !important;flex:0 0 auto">
                                    <i class="mdi mdi-panorama"></i> View 360° image</a>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ================= DETAIL SECTIONS ================= --}}
                @foreach ($sections as $title => $fields)
                    <div class="vr-card">
                        <div class="vr-card-header">{{ $title }}</div>
                        <div class="vr-card-body">
                            <div class="vr-grid">
                                @foreach ($fields as $label => $value)
                                    <div class="{{ in_array($label, ['Address', 'Landmarks']) ? 'vr-wide' : '' }}">
                                        <div class="vr-label">{{ $label }}</div>
                                        <div class="vr-value">{{ $value }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="col-lg-4">
                <div class="vr-sticky">
                    {{-- ================= DECISION ================= --}}
                    <div class="vr-card">
                        <div class="vr-card-header">Review Decision</div>
                        <div class="vr-card-body">
                            @if ($status === 'approved')
                                <p class="mb-3">
                                    <span class="vr-badge vr-approved">Approved</span><br>
                                    <span class="vr-label">{{ $media->reviewer_name ? 'By ' . $media->reviewer_name . ' · ' : '' }}{{ $fmt($media->reviewed_at) }}</span>
                                </p>
                                <p class="vr-label mb-3">Live on the website and open for booking under {{ $media->vendor_name ?: 'its vendor' }}.</p>
                            @else
                                <form method="POST" action="{{ route('admin.vendor.approvals.approve', $media->id) }}" class="mb-3 js-confirm"
                                    data-title="Approve {{ $code }}?" data-text="It will be published on the website and open for booking.">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-block"><i class="mdi mdi-check-circle"></i> Approve Media</button>
                                </form>
                                <p class="vr-label mb-3">Approving makes the media active, visible on the website and bookable. The vendor is notified.</p>
                            @endif

                            @if ($status !== 'rejected')
                                <button type="button" class="btn btn-danger btn-block" data-toggle="modal" data-target="#rejectModal">
                                    <i class="mdi mdi-close-circle"></i> {{ $status === 'approved' ? 'Withdraw Approval' : 'Reject Media' }}
                                </button>
                            @else
                                <p class="vr-label mb-0">The vendor can correct it and resubmit; it will come back to the Pending list.</p>
                            @endif
                        </div>
                    </div>

                    {{-- ================= HISTORY ================= --}}
                    <div class="vr-card">
                        <div class="vr-card-header">Activity History</div>
                        <div class="vr-card-body">
                            @if ($history->isEmpty())
                                <span class="text-muted">No activity recorded.</span>
                            @else
                                <ul class="vr-timeline">
                                    @foreach ($history as $h)
                                        @php
                                            $t = str_contains($h->action, 'Approved') ? 'vr-t-approved' : (str_contains($h->action, 'Rejected') ? 'vr-t-rejected' : (str_contains($h->action, 'Submitted') ? 'vr-t-submitted' : ''));
                                        @endphp
                                        <li class="{{ $t }}">
                                            <div style="font-weight:600;font-size:14px">{{ $h->action }}</div>
                                            <div class="vr-label">{{ $h->created_at->format('d M Y, h:i A') }} · {{ $h->actor_type === 'admin' ? 'Admin' : 'Vendor' }}: {{ $h->actor_name }}</div>
                                            @if ($h->details)
                                                <div style="font-size:13px;color:#374151">
                                                    @foreach ($h->details as $dk => $dv){{ $dk }}: {{ is_array($dv) ? implode(' → ', $dv) : $dv }}@if (!$loop->last) · @endif @endforeach
                                                </div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($status !== 'rejected')
        {{-- ================= REJECT MODAL ================= --}}
        <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form method="POST" action="{{ route('admin.vendor.approvals.reject', $media->id) }}" class="modal-content" style="border-radius:12px;overflow:hidden">
                    @csrf
                    <div class="modal-header" style="background:#DC2626;color:#fff">
                        <h5 class="modal-title" id="rejectModalTitle" style="color:#fff;font-weight:600">
                            {{ $status === 'approved' ? 'Withdraw approval' : 'Reject media' }} · {{ $code }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;opacity:1">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="vr-label mb-3">
                            {{ $media->media_title }} · {{ $media->vendor_name ?: 'No vendor' }}<br>
                            @if ($status === 'approved')
                                The media will be taken off the website and can no longer be booked.
                            @else
                                The media stays off the website.
                            @endif
                            The vendor is notified and can correct it and resubmit.
                        </p>
                        <label for="reason" style="font-weight:600">Reason for rejection <span class="text-danger">*</span></label>
                        <textarea id="reason" name="reason" class="form-control @error('reason') is-invalid @enderror" rows="5" required minlength="5" maxlength="1000"
                            placeholder="Shown to the vendor, e.g. Close shot photo is blurred, please upload a clear image.">{{ old('reason') }}</textarea>
                        @error('reason')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <small class="text-muted d-block mt-1"><span id="reasonCount">0</span> / 1000 characters</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger"><i class="mdi mdi-send"></i> Send Rejection</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        $(document).on('submit', 'form.js-confirm', function (e) {
            const form = this;
            if (form.dataset.ok) return;
            e.preventDefault();
            Swal.fire({
                title: form.dataset.title, text: form.dataset.text, icon: 'question', showCancelButton: true,
                confirmButtonColor: '#28a745', confirmButtonText: 'Yes, approve'
            }).then(function (r) { if (r.isConfirmed) { form.dataset.ok = '1'; form.submit(); } });
        });

        // Reject modal: the admin layout's content wrapper creates its own
        // stacking layer, which would leave the dialog behind the backdrop, so
        // it is moved to <body>. Then: focus the reason, count characters,
        // reopen after a validation error.
        $('#rejectModal').appendTo('body');
        $('#rejectModal').on('shown.bs.modal', function () { $('#reason').trigger('focus'); });
        $('#reason').on('input', function () { $('#reasonCount').text(this.value.length); }).trigger('input');
        @if ($errors->has('reason'))
            $(function () { $('#rejectModal').modal('show'); });
        @endif
    </script>
@endsection

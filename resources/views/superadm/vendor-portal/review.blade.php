@extends('superadm.layout.master')

@section('content')
    @include('superadm.vendor-portal._styles')
    @php
        $status = $media->approval_status;
        $rows = [
            'Vendor' => $media->vendor_name ? $media->vendor_name . ' (' . $media->vendor_code . ')' : 'Not assigned',
            'Vendor contact' => trim(($media->vendor_email ?? '') . ' · ' . ($media->vendor_mobile ?? ''), ' ·'),
            'Sector / Hoarding Code' => $media->hoarding_code ?: '—',
            'Media Code' => $media->media_code ?: '—',
            'Category' => $media->category_name,
            'Media Title' => $media->media_title,
            'Location' => collect([$media->area_name, $media->city_name, $media->district_name, $media->state_name])->filter()->implode(', '),
            'Address' => $media->address ?: '—',
            'Latitude / Longitude' => $media->latitude . ', ' . $media->longitude,
            'Size' => (float) $media->width . ' × ' . (float) $media->height . ' ft',
            'Illumination' => $media->illumination_name ?: '—',
            'Facing' => $media->facing ?: '—',
            'Monthly Price' => '₹ ' . number_format((float) $media->price),
            'Submitted' => $media->submitted_at ? \Carbon\Carbon::parse($media->submitted_at)->format('d M Y, h:i A') : '—',
        ];
    @endphp

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                <div>
                    <a href="{{ route('admin.vendor.approvals', ['status' => $status]) }}" class="vpa-muted">&larr; Vendor Media Approvals</a>
                    <h4 class="mb-1 mt-2">{{ $media->hoarding_code ?: $media->media_title }}
                        <span class="vpa-badge vpa-{{ $status }} ml-2" style="vertical-align:middle">{{ $labels[$status] ?? $status }}</span></h4>
                    <div class="vpa-muted">{{ $media->media_title }}</div>
                </div>
                <div class="text-nowrap">
                    <a href="{{ route('media.edit', base64_encode($media->id)) }}" class="btn btn-outline-secondary btn-sm">Open in Media Management</a>
                </div>
            </div>

            @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
            @if ($status === 'rejected' && $media->rejection_reason)
                <div class="alert alert-warning"><strong>Rejected:</strong> {{ $media->rejection_reason }}</div>
            @endif

            <div class="row">
                <div class="col-lg-8">
                    <h5>Photos ({{ $images->count() }})</h5>
                    <div class="d-flex flex-wrap mb-4" style="gap:10px">
                        @forelse ($images as $img)
                            <a href="{{ $imageBase . $img->images }}" target="_blank" rel="noopener">
                                <img src="{{ $imageBase . $img->images }}" alt="Photo {{ $loop->iteration }}" style="width:160px;height:110px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb"></a>
                        @empty
                            <span class="text-muted">No photos uploaded.</span>
                        @endforelse
                    </div>

                    <h5>Details</h5>
                    <table class="table table-sm table-bordered vpa-table">
                        @foreach ($rows as $k => $v)
                            <tr><th style="width:220px;background:#f8fafc">{{ $k }}</th><td>{{ $v }}</td></tr>
                        @endforeach
                    </table>
                </div>

                <div class="col-lg-4">
                    @if ($status !== 'approved')
                        <div class="border rounded p-3 mb-3">
                            <h5>Approve</h5>
                            <p class="vpa-muted">The media becomes active, visible on the website and open for booking under the vendor.</p>
                            <form method="POST" action="{{ route('admin.vendor.approvals.approve', $media->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-success btn-block">Approve Media</button>
                            </form>
                        </div>
                    @endif
                    @if ($status !== 'rejected')
                        <div class="border rounded p-3 mb-3">
                            <h5>Reject</h5>
                            <form method="POST" action="{{ route('admin.vendor.approvals.reject', $media->id) }}">
                                @csrf
                                <label for="reason" class="vpa-muted">Reason (shown to the vendor) <span class="text-danger">*</span></label>
                                <textarea id="reason" name="reason" class="form-control mb-2" rows="4" required minlength="5" maxlength="1000">{{ old('reason') }}</textarea>
                                <button type="submit" class="btn btn-danger btn-block">Reject Media</button>
                            </form>
                        </div>
                    @endif

                    <div class="border rounded p-3">
                        <h5>History</h5>
                        @forelse ($history as $h)
                            <div class="py-2 border-top">
                                <strong style="font-size:13px">{{ $h->action }}</strong>
                                <div class="vpa-muted">{{ $h->created_at->format('d M Y, h:i A') }} · {{ $h->actor_name }}</div>
                                @if ($h->details)
                                    <div style="font-size:12px">@foreach ($h->details as $dk => $dv){{ $dk }}: {{ is_array($dv) ? implode(' → ', $dv) : $dv }}@if (!$loop->last) · @endif @endforeach</div>
                                @endif
                            </div>
                        @empty
                            <span class="text-muted">No activity recorded.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('superadm.layout.master')

@section('content')
    @php
        use App\Support\MediaImageUrl;
        use Illuminate\Support\Str;

        $statusMeta = [
            'pending'  => ['Pending Approval', 'mdi-timer-sand', '#9A3412', '#FFEDD5', 'Waiting for your review. Hidden from the website.'],
            'approved' => ['Approved', 'mdi-check-decagram', '#166534', '#DCFCE7', 'Live on the website and open for booking.'],
            'rejected' => ['Rejected', 'mdi-close-octagon', '#991B1B', '#FEE2E2', 'Sent back to the vendor with a reason.'],
        ];
        [$curLabel, , $curFg, $curBg, $curHint] = $statusMeta[$status];
        $fmt = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y, h:i A') : '—';
        $vendorName = $vendors->firstWhere('id', (int) request('vendor_id'))?->vendor_name;
    @endphp

    <style>
        .va-card { background: #fff; border-radius: 12px; box-shadow: 0 8px 20px rgba(0, 0, 0, .06); margin-bottom: 24px; overflow: hidden; }
        .va-card-header { background: #00929c; color: #fff; padding: 12px 20px; font-weight: 600; font-size: 16px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; }
        .va-muted { color: #6b7280; font-size: 13px; }
        .va-stat { display: flex; align-items: center; gap: 14px; padding: 16px 18px; border-radius: 12px; background: #fff; border: 2px solid transparent;
            box-shadow: 0 8px 20px rgba(0, 0, 0, .06); text-decoration: none !important; color: #111827; transition: .15s; height: 100%; }
        .va-stat:hover { transform: translateY(-2px); }
        .va-stat.active { border-color: #00929c; }
        .va-stat-icon { width: 48px; height: 48px; border-radius: 12px; display: grid; place-items: center; font-size: 24px; flex: none; }
        .va-stat b { display: block; font-size: 26px; line-height: 1; }
        .va-table { font-size: 14px; margin-bottom: 0; }
        .va-table thead th { background: #f8fafc; color: #374151; font-weight: 600; font-size: 13px; border-bottom: 1px solid #e5e7eb; border-top: 0; white-space: nowrap; }
        .va-table td { vertical-align: middle; border-top: 1px solid #f1f5f9; }
        .va-row { cursor: pointer; }
        .va-row:hover { background: #F0FDFA; }
        .va-thumb { width: 72px; height: 52px; border-radius: 8px; object-fit: cover; background: #f1f5f9; display: block; }
        .va-nothumb { width: 72px; height: 52px; border-radius: 8px; background: #f1f5f9; display: grid; place-items: center; color: #94a3b8; font-size: 20px; }
        .va-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .va-reason { min-width: 220px; max-width: 300px; font-size: 13px; color: #7f1d1d; background: #FEF2F2; border-radius: 8px; padding: 6px 10px; line-height: 1.4; }
        .va-btn { background: #00929c; border: 1px solid #00929c; color: #fff !important; font-weight: 600; }
        .va-btn:hover { background: #007a82; border-color: #007a82; }
        .va-btn-outline { background: #fff; border: 1px solid #00929c; color: #00929c !important; font-weight: 600; }
        .va-btn-outline:hover { background: #E6F6F7; }
    </style>

    <div class="container-fluid mt-3">
        {{-- ================= HEADER + FILTERS ================= --}}
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-3" style="gap:12px">
            <div>
                <h3 class="mb-1" style="font-weight:600">Vendor Media Approvals</h3>
                <div class="va-muted">Media added by vendors from the Vendor Panel stays off the website until it is approved here.</div>
            </div>
            <form method="GET" action="{{ route('admin.vendor.approvals') }}" class="d-flex flex-wrap" style="gap:8px">
                <input type="hidden" name="status" value="{{ $status }}">
                <select name="vendor_id" class="form-control" style="width:auto;min-width:190px" aria-label="Vendor" onchange="this.form.submit()">
                    <option value="">All vendors</option>
                    @foreach ($vendors as $v)<option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->vendor_name }}</option>@endforeach
                </select>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" style="width:230px" placeholder="Search code, title or area" aria-label="Search">
                <button class="btn va-btn"><i class="mdi mdi-magnify"></i> Search</button>
                @if (request('q') || request('vendor_id'))
                    <a href="{{ route('admin.vendor.approvals', ['status' => $status]) }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </form>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        {{-- ================= STATUS CARDS ================= --}}
        <div class="row mb-2">
            @foreach ($statusMeta as $key => [$label, $icon, $fg, $bg, $hint])
                <div class="col-md-4 mb-3">
                    <a href="{{ route('admin.vendor.approvals', array_filter(['status' => $key, 'vendor_id' => request('vendor_id'), 'q' => request('q')])) }}"
                        class="va-stat {{ $status === $key ? 'active' : '' }}" @if ($status === $key) aria-current="page" @endif>
                        <span class="va-stat-icon" style="background:{{ $bg }};color:{{ $fg }}"><i class="mdi {{ $icon }}"></i></span>
                        <span>
                            <b>{{ (int) ($counts[$key] ?? 0) }}</b>
                            <span style="font-weight:600">{{ $label }}</span>
                            <span class="va-muted d-block" style="font-size:12px">{{ $hint }}</span>
                        </span>
                    </a>
                </div>
            @endforeach
        </div>

        {{-- ================= TABLE ================= --}}
        <div class="va-card">
            <div class="va-card-header">
                <span>{{ $curLabel }}{{ $vendorName ? ' · ' . $vendorName : '' }}</span>
                <span style="font-weight:400;font-size:14px">{{ $media->total() }} {{ Str::plural('media', $media->total()) }}</span>
            </div>

            @if ($media->isEmpty())
                <div class="text-center py-5">
                    <i class="mdi mdi-inbox-outline" style="font-size:40px;color:#00929c"></i>
                    <h5 class="mt-2 mb-1">
                        @if (request('q') || request('vendor_id'))
                            No media matches these filters
                        @else
                            {{ $status === 'pending' ? 'Nothing waiting for approval' : 'No ' . strtolower($curLabel) . ' media yet' }}
                        @endif
                    </h5>
                    <div class="va-muted">{{ $status === 'pending' ? 'New vendor submissions will appear here.' : 'Media reviewed from Vendor Approvals will be listed here.' }}</div>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table va-table">
                        <thead>
                            <tr>
                                <th style="width:50px">#</th>
                                <th style="width:90px">Photo</th>
                                <th>Media</th>
                                <th>Vendor</th>
                                <th class="text-right">Price / Month</th>
                                <th>{{ $status === 'pending' ? 'Submitted' : 'Reviewed' }}</th>
                                @if ($status === 'rejected')<th>Reason</th>@endif
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($media as $m)
                                @php $reviewUrl = route('admin.vendor.approvals.show', $m->id); @endphp
                                <tr class="va-row" data-href="{{ $reviewUrl }}" tabindex="0" role="link" aria-label="Review {{ $m->hoarding_code ?: $m->media_title }}">
                                    <td class="va-muted">{{ $media->firstItem() + $loop->index }}</td>
                                    <td>
                                        @if (isset($thumbs[$m->id]))
                                            <img src="{{ MediaImageUrl::for($thumbs[$m->id]) }}" alt="" class="va-thumb" loading="lazy">
                                        @else
                                            <span class="va-nothumb"><i class="mdi mdi-image-off-outline"></i></span>
                                        @endif
                                    </td>
                                    <td style="min-width:180px">
                                        <strong style="color:#00929c">{{ $m->hoarding_code ?: $m->media_code ?: '#' . $m->id }}</strong>
                                        <div style="font-weight:600">{{ $m->media_title ?: '—' }}</div>
                                        <div class="va-muted" style="font-size:12px">{{ $m->category_name }}</div>
                                        <div class="va-muted" style="font-size:12px"><i class="mdi mdi-map-marker-outline"></i> {{ collect([$m->area_name, $m->city_name])->filter()->unique()->implode(', ') ?: '—' }}</div>
                                    </td>
                                    <td class="text-nowrap">
                                        @if ($m->vendor_name)
                                            {{ $m->vendor_name }}
                                            <div class="va-muted" style="font-size:12px">{{ $m->vendor_code }}</div>
                                        @else
                                            <span class="va-badge" style="background:#F1F5F9;color:#475569">No vendor</span>
                                        @endif
                                    </td>
                                    <td class="text-right text-nowrap" style="font-weight:600">₹ {{ number_format((float) $m->price) }}</td>
                                    <td class="text-nowrap">
                                        @if ($status === 'pending')
                                            {{ $fmt($m->submitted_at) }}
                                        @else
                                            {{ $fmt($m->reviewed_at) }}
                                            @if ($m->reviewer_name)<div class="va-muted" style="font-size:12px">by {{ $m->reviewer_name }}</div>@endif
                                        @endif
                                    </td>
                                    @if ($status === 'rejected')
                                        <td><div class="va-reason" title="{{ $m->rejection_reason }}">{{ Str::limit($m->rejection_reason, 90) }}</div></td>
                                    @endif
                                    <td class="text-right text-nowrap">
                                        <a href="{{ $reviewUrl }}" class="btn btn-sm {{ $status === 'pending' ? 'va-btn' : 'va-btn-outline' }}">
                                            <i class="mdi {{ $status === 'pending' ? 'mdi-clipboard-check-outline' : 'mdi-eye-outline' }}"></i>
                                            {{ $status === 'pending' ? 'Review' : 'View' }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($media->hasPages())
                    <div class="d-flex justify-content-between align-items-center flex-wrap px-3 py-2" style="gap:10px">
                        <span class="va-muted">Showing {{ $media->firstItem() }}–{{ $media->lastItem() }} of {{ $media->total() }}</span>
                        {{ $media->links('pagination::bootstrap-4') }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Click (or Enter on) a row to open its review page; links inside keep their own action.
        $(document).on('click', '.va-row', function (e) {
            if (e.target.closest('a, button')) return;
            window.location = this.dataset.href;
        });
        $(document).on('keydown', '.va-row', function (e) {
            if (e.key === 'Enter' && e.target === this) window.location = this.dataset.href;
        });
    </script>
@endsection

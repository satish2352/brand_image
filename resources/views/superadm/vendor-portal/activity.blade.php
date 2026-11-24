@extends('superadm.layout.master')

@section('content')
    @php
        use App\Models\VendorActivityLog;

        // Icon + colour per kind of activity (matched on keywords in the action name).
        $style = function (string $action) {
            $a = strtolower($action);
            return match (true) {
                str_contains($a, 'approved')                          => ['mdi-check-decagram', '#166534', '#DCFCE7'],
                str_contains($a, 'rejected')                          => ['mdi-close-octagon', '#991B1B', '#FEE2E2'],
                str_contains($a, 'deleted') || str_contains($a, 'removed') && !str_contains($a, 'removal')
                                                                      => ['mdi-delete-outline', '#991B1B', '#FEE2E2'],
                str_contains($a, 'submitted')                         => ['mdi-upload', '#9A3412', '#FFEDD5'],
                str_contains($a, 'added') || str_contains($a, 'assigned')
                                                                      => ['mdi-plus-circle-outline', '#9A3412', '#FFEDD5'],
                str_contains($a, 'availability')                      => ['mdi-calendar-check', '#1E40AF', '#DBEAFE'],
                str_contains($a, 'rate')                              => ['mdi-currency-inr', '#166534', '#DCFCE7'],
                str_contains($a, 'photo') || str_contains($a, 'shared')
                                                                      => ['mdi-camera-outline', '#0F766E', '#CCFBF1'],
                str_contains($a, 'removal')                           => ['mdi-check-all', '#166534', '#DCFCE7'],
                str_contains($a, 'login')                             => ['mdi-key-outline', '#6B21A8', '#F3E8FF'],
                str_contains($a, 'enquiry')                           => ['mdi-email-outline', '#9A3412', '#FFEDD5'],
                default                                               => ['mdi-pencil-outline', '#1E40AF', '#DBEAFE'],
            };
        };
        $cards = [
            ['Activities', $summary['total'], 'mdi-history', '#00929c', '#E6F6F7', 'In the last ' . VendorActivityLog::RETENTION_MONTHS . ' months'],
            ['By vendors', $summary['vendor'], 'mdi-storefront-outline', '#9A3412', '#FFEDD5', 'Changes made in the Vendor Panel'],
            ['By Brand Adda', $summary['admin'], 'mdi-shield-account-outline', '#1E40AF', '#DBEAFE', 'Admin actions on vendor inventory'],
            ['Today', $summary['today'], 'mdi-calendar-today', '#166534', '#DCFCE7', now()->format('d M Y')],
        ];
    @endphp

    <style>
        .al-card { background: #fff; border-radius: 12px; box-shadow: 0 8px 20px rgba(0, 0, 0, .06); margin-bottom: 24px; overflow: hidden; }
        .al-card-header { background: #00929c; color: #fff; padding: 12px 20px; font-weight: 600; font-size: 16px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; }
        .al-muted { color: #6b7280; font-size: 13px; }
        .al-stat { display: flex; align-items: center; gap: 14px; padding: 16px 18px; border-radius: 12px; background: #fff; box-shadow: 0 8px 20px rgba(0, 0, 0, .06); height: 100%; }
        .al-stat-icon { width: 46px; height: 46px; border-radius: 12px; display: grid; place-items: center; font-size: 22px; flex: none; }
        .al-stat b { display: block; font-size: 24px; line-height: 1.1; color: #111827; }
        .al-label { font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px; display: block; }
        .al-table { font-size: 14px; margin-bottom: 0; }
        .al-table thead th { background: #f8fafc; color: #374151; font-weight: 600; font-size: 13px; border-bottom: 1px solid #e5e7eb; border-top: 0; white-space: nowrap; }
        .al-table td { vertical-align: middle; border-top: 1px solid #f1f5f9; }
        .al-icon { width: 34px; height: 34px; border-radius: 9px; display: grid; place-items: center; font-size: 17px; flex: none; }
        .al-chip { display: inline-block; margin: 2px 4px 2px 0; padding: 2px 8px; border-radius: 6px; background: #F1F5F9; font-size: 12px; color: #334155; max-width: 360px; white-space: normal; }
        .al-chip b { color: #0F172A; font-weight: 600; }
        .al-who { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .al-btn { background: #00929c; border: 1px solid #00929c; color: #fff !important; font-weight: 600; }
        .al-btn:hover { background: #007a82; border-color: #007a82; }
        .al-pager { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; padding: 12px 20px; border-top: 1px solid #f1f5f9; }
        .al-pager .pagination { margin: 0; }
        .al-pager .page-item.active .page-link { background: #00929c; border-color: #00929c; color: #fff; }
        .al-pager .page-link { color: #00929c; }
    </style>

    <div class="container-fluid mt-3">
        <div class="mb-3">
            <h3 class="mb-1" style="font-weight:600">Vendor Activity Log</h3>
            <div class="al-muted">Every change made in the Vendor Panel, and every Brand Adda action on vendor inventory. Kept for the last {{ VendorActivityLog::RETENTION_MONTHS }} months.</div>
        </div>

        {{-- ================= SUMMARY ================= --}}
        <div class="row">
            @foreach ($cards as [$label, $value, $icon, $fg, $bg, $hint])
                <div class="col-md-6 col-xl-3 mb-3">
                    <div class="al-stat">
                        <span class="al-stat-icon" style="background:{{ $bg }};color:{{ $fg }}"><i class="mdi {{ $icon }}"></i></span>
                        <span><b>{{ number_format($value) }}</b><span style="font-weight:600">{{ $label }}</span>
                            <span class="al-muted d-block" style="font-size:12px">{{ $hint }}</span></span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ================= FILTERS ================= --}}
        <form method="GET" action="{{ route('admin.vendor.activity') }}" class="al-card" style="padding:18px 20px">
            <div class="form-row">
                <div class="col-md-6 col-xl-2 mb-2">
                    <label class="al-label" for="f-vendor">Vendor</label>
                    <select id="f-vendor" name="vendor_id" class="form-control"><option value="">All vendors</option>
                        @foreach ($vendors as $v)<option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->vendor_name }}</option>@endforeach</select>
                </div>
                <div class="col-md-6 col-xl-2 mb-2">
                    <label class="al-label" for="f-actor">Done by</label>
                    <select id="f-actor" name="actor" class="form-control"><option value="">Vendor &amp; Brand Adda</option>
                        <option value="vendor" @selected(request('actor') === 'vendor')>Vendor</option>
                        <option value="admin" @selected(request('actor') === 'admin')>Brand Adda</option></select>
                </div>
                <div class="col-md-6 col-xl-2 mb-2">
                    <label class="al-label" for="f-action">Activity</label>
                    <select id="f-action" name="action" class="form-control"><option value="">All activities</option>
                        @foreach ($actions as $a)<option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>@endforeach</select>
                </div>
                <div class="col-md-6 col-xl-2 mb-2">
                    <label class="al-label" for="f-from">From date</label>
                    <input id="f-from" type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
                </div>
                <div class="col-md-6 col-xl-2 mb-2">
                    <label class="al-label" for="f-to">To date</label>
                    <input id="f-to" type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                </div>
                <div class="col-md-6 col-xl-2 mb-2">
                    <label class="al-label" for="f-q">Media code</label>
                    <input id="f-q" type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="e.g. HD000034">
                </div>
            </div>
            <div class="d-flex justify-content-end mt-2" style="gap:8px">
                @if ($filtered)<a href="{{ route('admin.vendor.activity') }}" class="btn btn-outline-secondary"><i class="mdi mdi-refresh"></i> Reset</a>@endif
                <button class="btn al-btn"><i class="mdi mdi-filter-outline"></i> Apply Filters</button>
            </div>
        </form>

        {{-- ================= LOG ================= --}}
        <div class="al-card">
            <div class="al-card-header">
                <span>Activity</span>
                <span style="font-weight:400;font-size:14px">{{ number_format($log->total()) }} {{ \Illuminate\Support\Str::plural('entry', $log->total()) }}{{ $filtered ? ' (filtered)' : '' }}</span>
            </div>

            @if ($log->isEmpty())
                <div class="text-center py-5">
                    <i class="mdi mdi-history" style="font-size:40px;color:#00929c"></i>
                    <h5 class="mt-2 mb-1">{{ $filtered ? 'No activity matches these filters' : 'No activity yet' }}</h5>
                    <div class="al-muted">Vendor and Brand Adda actions on vendor inventory appear here.</div>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table al-table">
                        <thead>
                            <tr>
                                <th style="width:150px">Date / Time</th>
                                <th>Activity</th>
                                <th>Vendor</th>
                                <th>Media</th>
                                <th>Details</th>
                                <th>Done by</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($log as $l)
                                @php [$icon, $fg, $bg] = $style($l->action); @endphp
                                <tr>
                                    <td class="text-nowrap">
                                        {{ $l->created_at->format('d M Y') }}
                                        <div class="al-muted" style="font-size:12px">{{ $l->created_at->format('h:i A') }} · {{ $l->created_at->diffForHumans(null, true) }} ago</div>
                                    </td>
                                    <td style="min-width:250px">
                                        <div class="d-flex align-items-center" style="gap:10px">
                                            <span class="al-icon" style="background:{{ $bg }};color:{{ $fg }}"><i class="mdi {{ $icon }}"></i></span>
                                            <strong style="font-size:14px">{{ $l->action }}</strong>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        @if ($l->vendor)
                                            <a href="{{ route('admin.vendor.activity', array_merge(request()->except('page'), ['vendor_id' => $l->vendor_id])) }}" title="Show only this vendor" style="color:#111827">{{ $l->vendor->vendor_name }}</a>
                                        @else
                                            <span class="al-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        @if ($l->media_id)
                                            <a href="{{ route('admin.vendor.approvals.show', $l->media_id) }}" style="color:#00929c;font-weight:600">{{ $l->media_code }}</a>
                                        @else
                                            <span class="al-muted">—</span>
                                        @endif
                                    </td>
                                    <td style="min-width:240px">
                                        @forelse ($l->details ?? [] as $k => $v)
                                            <span class="al-chip"><b>{{ $k }}:</b> {{ is_array($v) ? implode(' → ', $v) : \Illuminate\Support\Str::limit((string) $v, 120) }}</span>
                                        @empty
                                            <span class="al-muted">—</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @if ($l->actor_type === 'admin')
                                            <span class="al-who" style="background:#DBEAFE;color:#1E40AF"><i class="mdi mdi-shield-account-outline"></i> Brand Adda</span>
                                        @else
                                            <span class="al-who" style="background:#FFEDD5;color:#9A3412"><i class="mdi mdi-storefront-outline"></i> Vendor</span>
                                        @endif
                                        <div class="al-muted" style="font-size:12px;margin-top:3px">{{ $l->actor_name }}</div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="al-pager">
                    <form method="GET" action="{{ route('admin.vendor.activity') }}" class="d-flex align-items-center" style="gap:8px;margin:0">
                        @foreach (request()->except(['per_page', 'page']) as $k => $v)
                            @if (is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                        @endforeach
                        <label for="per-page" class="al-muted" style="margin:0">Show</label>
                        <select id="per-page" name="per_page" class="form-control form-control-sm" style="width:auto" onchange="this.form.submit()">
                            @foreach ([10, 20, 50, 100] as $n)<option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>@endforeach
                        </select>
                        <span class="al-muted">per page</span>
                    </form>
                    <span class="al-muted">
                        Showing <strong style="color:#111827">{{ $log->firstItem() }}–{{ $log->lastItem() }}</strong> of <strong style="color:#111827">{{ number_format($log->total()) }}</strong>
                        @if ($log->hasPages()) · Page {{ $log->currentPage() }} of {{ $log->lastPage() }} @endif
                    </span>
                    @if ($log->hasPages())
                        <nav aria-label="Activity log pages">{{ $log->onEachSide(1)->links('pagination::bootstrap-4') }}</nav>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection

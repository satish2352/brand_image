@extends('superadm.layout.master')

@section('content')
    @php
        use App\Models\VendorEnquiry;
        $total = (int) $counts->sum();
        $keep  = request()->except(['status', 'page', 'open']);
        // Status cards: click to filter; the current one is highlighted.
        $cards = [
            [null, 'All Enquiries', $total, 'mdi-email-multiple-outline', '#00929c', '#E6F6F7'],
            ['new', 'New', (int) ($counts['new'] ?? 0), 'mdi-email-alert-outline', '#9A3412', '#FFEDD5'],
            ['contacted', 'Contacted', (int) ($counts['contacted'] ?? 0), 'mdi-phone-outline', '#1E40AF', '#DBEAFE'],
            ['closed', 'Closed', (int) ($counts['closed'] ?? 0), 'mdi-check-circle-outline', '#166534', '#DCFCE7'],
        ];
        $badge = function (string $s) {
            [$bg, $fg] = VendorEnquiry::COLORS[$s] ?? ['#F1F5F9', '#334155'];
            return '<span class="eq-badge" style="background:' . $bg . ';color:' . $fg . '">' . e(VendorEnquiry::STATUSES[$s] ?? ucfirst($s)) . '</span>';
        };
        $openUrl = fn ($id) => route('admin.vendor.enquiries', array_merge(request()->except('open'), ['open' => $id]));
    @endphp

    <style>
        .eq-card { background: #fff; border-radius: 12px; box-shadow: 0 8px 20px rgba(0, 0, 0, .06); margin-bottom: 24px; overflow: hidden; }
        .eq-card-header { background: #00929c; color: #fff; padding: 12px 20px; font-weight: 600; font-size: 16px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; }
        .eq-muted { color: #6b7280; font-size: 13px; }
        .eq-stat { display: flex; align-items: center; gap: 14px; padding: 16px 18px; border-radius: 12px; background: #fff; box-shadow: 0 8px 20px rgba(0, 0, 0, .06); height: 100%;
            color: inherit !important; text-decoration: none !important; border: 2px solid transparent; transition: border-color .15s; }
        .eq-stat:hover, .eq-stat.is-on { border-color: var(--eq-fg); }
        .eq-stat-icon { width: 46px; height: 46px; border-radius: 12px; display: grid; place-items: center; font-size: 22px; flex: none; }
        .eq-stat b { display: block; font-size: 24px; line-height: 1.1; color: #111827; }
        .eq-label { font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px; display: block; }
        .eq-table { font-size: 14px; margin-bottom: 0; }
        .eq-table thead th { background: #f8fafc; color: #374151; font-weight: 600; font-size: 13px; border-bottom: 1px solid #e5e7eb; border-top: 0; white-space: nowrap; }
        .eq-table td { vertical-align: middle; border-top: 1px solid #f1f5f9; }
        .eq-table tr.is-new td { background: #FFFBF5; }
        .eq-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .eq-msg { max-width: 320px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; color: #374151; }
        .eq-btn { background: #00929c; border-color: #00929c; color: #fff !important; }
        .eq-pager { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; padding: 12px 20px; border-top: 1px solid #e5e7eb; }
        .eq-pager .pagination { margin: 0; }
        .eq-pager .page-item.active .page-link { background: #00929c; border-color: #00929c; }
        .eq-pager .page-link { color: #00929c; }
        .eq-dl dt { font-size: 12px; font-weight: 600; color: #6b7280; margin-top: 10px; }
        .eq-dl dd { margin: 2px 0 0; font-size: 15px; font-weight: 600; word-break: break-word; }
        #eqVendor + .select2-container .select2-selection--single { height: 38px; }
        #eqVendor + .select2-container .select2-selection__rendered { line-height: 36px; }
        #eqVendor + .select2-container .select2-selection__arrow { height: 36px; }
    </style>

    <div class="container-fluid">
        <div class="mb-3">
            <h3 class="mb-1" style="font-weight:600">Vendor Enquiries</h3>
            <div class="eq-muted">Enquiries sent from each vendor's website Contact page. The vendor follows them up and sets the status in its Vendor Panel.</div>
        </div>

        {{-- ================= STATUS CARDS ================= --}}
        <div class="row">
            @foreach ($cards as [$key, $label, $value, $icon, $fg, $bg])
                <div class="col-md-6 col-xl-3 mb-3">
                    <a href="{{ route('admin.vendor.enquiries', $key ? $keep + ['status' => $key] : $keep) }}" class="eq-stat {{ $status === $key ? 'is-on' : '' }}" style="--eq-fg:{{ $fg }}">
                        <span class="eq-stat-icon" style="background:{{ $bg }};color:{{ $fg }}"><i class="mdi {{ $icon }}"></i></span>
                        <span><b>{{ number_format($value) }}</b><span style="font-weight:600">{{ $label }}</span></span>
                    </a>
                </div>
            @endforeach
        </div>

        {{-- ================= FILTERS ================= --}}
        <form method="GET" action="{{ route('admin.vendor.enquiries') }}" class="eq-card" style="padding:18px 20px">
            @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <div class="form-row align-items-end">
                <div class="col-md-4 mb-2">
                    <label class="eq-label" for="eqVendor">Vendor</label>
                    <select id="eqVendor" name="vendor_id" class="form-control" style="width:100%">
                        <option value="">All vendors</option>
                        @foreach ($vendors as $v)<option value="{{ $v->id }}" @selected($vendorId === $v->id)>{{ $v->vendor_name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-5 mb-2">
                    <label class="eq-label" for="eqQ">Search</label>
                    <input id="eqQ" type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Name, email, mobile, company, city or message">
                </div>
                <div class="col-md-3 mb-2 d-flex" style="gap:8px">
                    <button class="btn eq-btn flex-fill"><i class="mdi mdi-filter-outline"></i> Filter</button>
                    <a href="{{ route('admin.vendor.enquiries') }}" class="btn btn-outline-secondary flex-fill"><i class="mdi mdi-refresh"></i> Reset</a>
                </div>
            </div>
        </form>

        {{-- ================= LIST ================= --}}
        <div class="eq-card">
            <div class="eq-card-header">
                <span>{{ $status ? VendorEnquiry::STATUSES[$status] . ' enquiries' : 'All enquiries' }}</span>
                <span style="font-weight:400;font-size:14px">{{ number_format($enquiries->total()) }} {{ \Illuminate\Support\Str::plural('enquiry', $enquiries->total()) }}</span>
            </div>
            @if ($enquiries->isEmpty())
                <div class="text-center py-5">
                    <i class="mdi mdi-email-outline" style="font-size:40px;color:#00929c"></i>
                    <h5 class="mt-2 mb-1">{{ request()->hasAny(['q', 'vendor_id', 'status']) ? 'No enquiries match these filters' : 'No enquiries yet' }}</h5>
                    <div class="eq-muted">Enquiries from the vendors' website Contact pages appear here.</div>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table eq-table">
                        <thead>
                            <tr><th style="width:60px">Sr. No.</th><th>Date</th><th>Vendor</th><th>Name</th><th>Contact</th><th>Media</th><th>Message</th><th>Status</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($enquiries as $e)
                                <tr class="{{ $e->status === 'new' ? 'is-new' : '' }}">
                                    <td class="eq-muted">{{ $enquiries->firstItem() + $loop->index }}</td>
                                    <td class="text-nowrap">{{ $e->created_at->format('d M Y') }}<div class="eq-muted">{{ $e->created_at->format('h:i A') }}</div></td>
                                    <td class="text-nowrap" style="font-weight:600">{{ $e->vendor_name ?: '—' }}</td>
                                    <td style="min-width:140px"><strong>{{ $e->name }}</strong>@if ($e->company)<div class="eq-muted">{{ $e->company }}</div>@endif</td>
                                    <td class="text-nowrap">
                                        <a href="tel:+{{ $e->phone_intl }}" style="color:#00929c">{{ $e->mobile }}</a>
                                        <div><a href="mailto:{{ $e->email }}" class="eq-muted">{{ $e->email }}</a></div>
                                    </td>
                                    <td class="text-nowrap">{{ $e->media ? ($e->media->hoarding_code ?: $e->media->media_code) : '—' }}</td>
                                    <td><div class="eq-msg">{{ $e->message }}</div></td>
                                    <td>{!! $badge($e->status) !!}</td>
                                    <td class="text-right"><a href="{{ $openUrl($e->id) }}" class="btn btn-sm btn-outline-info">View</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            <div class="eq-pager">
                <div class="d-flex flex-wrap align-items-center eq-muted" style="gap:12px">
                    <span>@if ($enquiries->total())Showing {{ $enquiries->firstItem() }}–{{ $enquiries->lastItem() }} of {{ $enquiries->total() }}@else No enquiries @endif</span>
                    <form method="GET" action="{{ route('admin.vendor.enquiries') }}" class="d-flex align-items-center" style="gap:6px;margin:0">
                        @foreach (request()->except(['per_page', 'page', 'open']) as $k => $v)@if (is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif @endforeach
                        <label for="eqPp" class="mb-0">Show</label>
                        <select id="eqPp" name="per_page" class="form-control form-control-sm" style="width:auto" onchange="this.form.submit()">
                            @foreach ([10, 20, 50, 100] as $n)<option value="{{ $n }}" @selected($perPage == $n)>{{ $n }}</option>@endforeach
                        </select>
                    </form>
                </div>
                @if ($enquiries->hasPages()){{ $enquiries->onEachSide(1)->links('pagination::bootstrap-4') }}@endif
            </div>
        </div>
    </div>

    {{-- ================= DETAILS POPUP ================= --}}
    @if ($selected)
        <div class="modal fade" id="eqModal" tabindex="-1" aria-labelledby="eqModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eqModalTitle">Enquiry from {{ $selected->name }} {!! $badge($selected->status) !!}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <dl class="col-md-6 eq-dl mb-0">
                                <dt>Vendor</dt><dd>{{ $selected->vendor_name ?: '—' }}</dd>
                                <dt>Received</dt><dd>{{ $selected->created_at->format('d M Y, h:i A') }}</dd>
                                <dt>Media</dt><dd>{{ $selected->media ? ($selected->media->display_code . ' · ' . $selected->media->media_title) : 'General enquiry' }}</dd>
                            </dl>
                            <dl class="col-md-6 eq-dl mb-0">
                                <dt>Name</dt><dd>{{ $selected->name }}</dd>
                                <dt>Mobile</dt><dd><a href="tel:+{{ $selected->phone_intl }}">{{ $selected->mobile }}</a> · <a href="https://wa.me/{{ $selected->phone_intl }}" target="_blank" rel="noopener">WhatsApp</a></dd>
                                <dt>Email</dt><dd><a href="mailto:{{ $selected->email }}">{{ $selected->email }}</a></dd>
                                @if ($selected->company)<dt>Company / Brand</dt><dd>{{ $selected->company }}</dd>@endif
                                @if ($selected->city)<dt>City</dt><dd>{{ $selected->city }}</dd>@endif
                            </dl>
                        </div>
                        <dl class="eq-dl mb-0"><dt>Message</dt><dd style="font-weight:400;white-space:pre-line;background:#f8fafc;border-radius:8px;padding:12px;margin-top:6px">{{ $selected->message }}</dd></dl>
                    </div>
                    <div class="modal-footer">
                        <span class="eq-muted mr-auto">The vendor updates the status from its Vendor Panel.</span>
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(function () {
            // Searchable vendor filter; picking one applies it.
            $('#eqVendor').select2({ width: '100%', placeholder: 'All vendors', language: { noResults: function () { return 'No vendor found'; } } })
                .on('change', function () { this.form.submit(); })
                .on('select2:open', function () { setTimeout(function () { const f = document.querySelector('.select2-container--open .select2-search__field'); if (f) f.focus(); }, 0); });

            // ?open=ID shows that enquiry; closing it drops ?open from the address.
            const modal = $('#eqModal');
            if (modal.length) {
                modal.appendTo('body').modal('show').on('hidden.bs.modal', function () {
                    const url = new URL(window.location.href); url.searchParams.delete('open'); history.replaceState(null, '', url);
                });
            }
        });
    </script>
@endsection

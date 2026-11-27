@extends('superadm.layout.master')

@section('content')
    <style>
        .vi-card { background: #fff; border-radius: 12px; box-shadow: 0 8px 20px rgba(0, 0, 0, .06); margin-bottom: 24px; overflow: hidden; }
        .vi-card-header { background: #00929c; color: #fff; padding: 12px 20px; font-weight: 600; font-size: 16px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; }
        .vi-card-body { padding: 20px; }
        .vi-pager { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; padding: 12px 20px; border-top: 1px solid #e5e7eb; }
        .vi-pager-info { display: flex; flex-wrap: wrap; gap: 14px; align-items: center; color: #6b7280; font-size: 13px; }
        .vi-pager-size { display: flex; gap: 6px; align-items: center; margin: 0; }
        .vi-pager-size label { margin: 0; }
        .vi-pager-size select { width: auto !important; }
        .vi-pager .pagination { margin: 0; }
        .vi-pager .page-item.active .page-link { background: #00929c; border-color: #00929c; }
        .vi-pager .page-link { color: #00929c; }
        .vi-vsearch { display: flex; gap: 6px; align-items: center; margin: 0; }
        .vi-vsearch input { min-width: 240px; height: 34px; border-radius: 6px; border: 0; padding: 0 10px; font-size: 14px; }
        .vi-muted { color: #6b7280; font-size: 13px; }
        .vi-table { font-size: 14px; margin-bottom: 0; }
        .vi-table thead th { background: #f8fafc; color: #374151; font-weight: 600; font-size: 13px; border-bottom: 1px solid #e5e7eb; white-space: nowrap; position: sticky; top: 0; z-index: 1; }
        .vi-table td { vertical-align: middle; }
        .vi-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .vi-approved { background: #DCFCE7; color: #166534; }
        .vi-pending { background: #FFEDD5; color: #9A3412; }
        .vi-rejected { background: #FEE2E2; color: #991B1B; }
        .vi-grey { background: #F1F5F9; color: #475569; }
        .vi-selected-row { background: #FFFBEB !important; }
        .vi-scroll { max-height: 480px; overflow: auto; }
        .vi-stat { padding: 0 20px; border-left: 1px solid #e5e7eb; }
        .vi-stat:first-child { border-left: 0; padding-left: 0; }
        .vi-stat b { display: block; font-size: 22px; color: #111827; }
        /* The admin theme hides native checkboxes (it draws one on a label);
           these have no label, so bring them back — as the Media List does. */
        input.vi-check[type="checkbox"],
        input.vi-check[type="checkbox"]:checked,
        input.vi-check[type="checkbox"]:not(:checked) {
            position: static; left: auto; opacity: 1; width: 17px; height: 17px;
            cursor: pointer; accent-color: #008a93; vertical-align: middle; margin: 0;
        }
        #manage { scroll-margin-top: 90px; }
        /* Vendor row actions: two equal-width buttons, stacked, icon + label on one line. */
        .vi-actions { display: flex; flex-direction: column; gap: 6px; width: 168px; margin-left: auto; }
        .vi-actions form { margin: 0; }
        .vi-btn { display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%; height: 34px; padding: 0 12px;
            border-radius: 6px; font-size: 13px; font-weight: 600; white-space: nowrap; cursor: pointer; text-decoration: none !important; transition: .15s; }
        .vi-btn i { font-size: 16px; line-height: 1; }
        .vi-btn-solid { background: #00929c; border: 1px solid #00929c; color: #fff !important; }
        .vi-btn-solid:hover { background: #007a82; border-color: #007a82; }
        .vi-btn-outline { background: #fff; border: 1px solid #00929c; color: #00929c !important; }
        .vi-btn-outline:hover { background: #E6F6F7; }
        .vi-header-btn { background: #fff; color: #00929c; border: 0; font-weight: 600; }
        .vi-header-btn:disabled { opacity: .6; cursor: not-allowed; }
        .vi-header-btn.danger { color: #DC2626; }
    </style>

    <div class="container-fluid mt-3">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-3" style="gap:12px">
            <div>
                <h3 class="mb-1" style="font-weight:600">Vendor Inventory &amp; Access</h3>
                <div class="vi-muted">See each vendor’s inventory, remove assignments, and send each vendor its Vendor Panel login.
                    To assign media to a vendor, set the Vendor field in <a href="{{ route('media.list') }}">Media Management</a>
                    or use <a href="{{ route('media.import-export') }}">Import &amp; Export</a> (Vendor column).</div>
            </div>
            <a href="{{ route('admin.vendor.approvals') }}" class="btn btn-outline-info"><i class="mdi mdi-check-decagram"></i> Vendor Approvals</a>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        {{-- ================= VENDORS ================= --}}
        <div class="vi-card">
            <div class="vi-card-header">
                <span>Vendors <span style="font-weight:400;font-size:14px">({{ $summary->total() }})</span></span>
                {{-- Vendor search (server-side): name, code, email or site address. --}}
                <form method="GET" action="{{ route('admin.vendor.inventory') }}" class="vi-vsearch" role="search">
                    @foreach (request()->except(['vq', 'page']) as $k => $v)
                        @if (is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                    @endforeach
                    <input type="search" name="vq" value="{{ request('vq') }}" placeholder="Search vendor name, code, email…" aria-label="Search vendors">
                    <button class="btn btn-sm vi-header-btn"><i class="mdi mdi-magnify"></i> Search</button>
                    @if (request('vq'))
                        <a href="{{ route('admin.vendor.inventory', request()->except(['vq', 'page'])) }}" class="btn btn-sm vi-header-btn">Clear</a>
                    @endif
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover vi-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:70px">Sr. No.</th>
                            <th>Vendor</th>
                            <th class="text-center">Media</th>
                            <th class="text-center">Pending</th>
                            <th>Vendor Panel Login</th>
                            <th class="text-right" style="width:190px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($summary as $s)
                            <tr class="{{ $vendorId === $s->id ? 'vi-selected-row' : '' }}">
                                <td class="text-center vi-muted">{{ $summary->firstItem() + $loop->index }}</td>
                                <td style="min-width:220px">
                                    <strong>{{ $s->vendor_name }}</strong>
                                    @unless ($s->is_active)<span class="vi-badge vi-rejected ml-1">Inactive</span>@endunless
                                    <div class="vi-muted">{{ $s->vendor_code }}</div>
                                    <div style="font-size:13px;margin-top:2px"><i class="mdi mdi-email-outline vi-muted"></i> {{ $s->email }}</div>
                                    @if ($s->slug)
                                        <a href="{{ url($s->slug) }}" target="_blank" rel="noopener" style="font-size:13px"><i class="mdi mdi-web"></i> /{{ $s->slug }}</a>
                                    @endif
                                </td>
                                <td class="text-center"><strong>{{ $s->total }}</strong></td>
                                <td class="text-center">
                                    @if ($s->pending)
                                        <a href="{{ route('admin.vendor.approvals', ['vendor_id' => $s->id]) }}" class="vi-badge vi-pending">{{ $s->pending }}</a>
                                    @else
                                        <span class="vi-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @if ($s->credentials_sent_at)
                                        <span class="vi-badge vi-approved">Sent {{ \Carbon\Carbon::parse($s->credentials_sent_at)->format('d M Y') }}</span>
                                    @else
                                        <span class="vi-badge vi-grey">Not sent</span>
                                    @endif
                                    <div class="vi-muted mt-1">Last login: {{ $s->last_login_at ? \Carbon\Carbon::parse($s->last_login_at)->format('d M Y, h:i A') : 'never' }}</div>
                                </td>
                                <td>
                                    <div class="vi-actions">
                                        <a href="{{ route('admin.vendor.inventory', ['vendor_id' => $s->id]) }}#manage" class="vi-btn vi-btn-solid">
                                            <i class="mdi mdi-format-list-bulleted"></i><span>Manage Inventory</span></a>
                                        <form method="POST" action="{{ route('admin.vendor.credentials', $s->id) }}" class="js-confirm"
                                            data-title="{{ $s->credentials_sent_at ? 'Resend login details?' : 'Send login details?' }}"
                                            data-text="{{ $s->credentials_sent_at ? 'A NEW password will be generated and emailed to ' . $s->email . '. The old one stops working.' : 'A password will be generated and the login details emailed to ' . $s->email . '.' }}"
                                            data-button="Yes, send">
                                            @csrf
                                            <button type="submit" class="vi-btn vi-btn-outline">
                                                <i class="mdi mdi-email-outline"></i><span>{{ $s->credentials_sent_at ? 'Resend Login' : 'Send Login' }}</span></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center vi-muted py-4">
                                @if (filled(request('vq')))
                                    No vendors match “{{ request('vq') }}”.
                                    <a href="{{ route('admin.vendor.inventory', request()->except(['vq', 'page'])) }}">Clear search</a>
                                @elseif ($summary->total())
                                    No vendors on this page. <a href="{{ route('admin.vendor.inventory', request()->except(['page'])) }}">Go to the first page</a>
                                @else
                                    No vendors yet.
                                @endif
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('superadm.vendor-portal._pager', ['page' => $summary, 'sizeKey' => 'per_page', 'size' => $perPage, 'noun' => 'vendors'])
        </div>

        <div id="manage"></div>
        @if (!$vendor)
            <div class="vi-card">
                <div class="vi-card-body text-center py-5">
                    <i class="mdi mdi-cursor-default-click-outline" style="font-size:36px;color:#00929c"></i>
                    <h5 class="mt-2 mb-1">Choose a vendor to manage its inventory</h5>
                    <div class="vi-muted">Click <strong>Manage Inventory</strong> on a vendor above to see or remove its media.</div>
                </div>
            </div>
        @else
            {{-- ================= SELECTED VENDOR ================= --}}
            <div class="vi-card">
                <div class="vi-card-body d-flex flex-wrap justify-content-between align-items-center" style="gap:16px">
                    <div class="d-flex flex-wrap align-items-center">
                        <div class="vi-stat">
                            <span class="vi-muted">Managing</span>
                            <b>{{ $vendor->vendor_name }}</b>
                        </div>
                        <div class="vi-stat"><span class="vi-muted">Assigned media</span><b>{{ $assigned->total() }}</b></div>
                        <div class="vi-stat"><span class="vi-muted">Pending approval</span><b>{{ $assignedPending }}</b></div>
                    </div>
                    <form method="GET" action="{{ route('admin.vendor.inventory') }}#manage" class="d-flex" style="gap:8px">
                        @foreach (request()->except(['q', 'mpage', 'vendor_id']) as $k => $v)
                            @if (is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                        @endforeach
                        <input type="hidden" name="vendor_id" value="{{ $vendorId }}">
                        <input type="search" name="q" value="{{ request('q') }}" class="form-control" style="min-width:260px" placeholder="Search code, title or area" aria-label="Search media">
                        <button class="btn btn-info"><i class="mdi mdi-magnify"></i> Search</button>
                        @if (request('q'))<a href="{{ route('admin.vendor.inventory', request()->except(['q', 'mpage'])) }}#manage" class="btn btn-outline-secondary">Clear</a>@endif
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <form method="POST" action="{{ route('admin.vendor.inventory.remove') }}" class="vi-card js-confirm js-pick-form"
                        data-title="Remove selected media from {{ $vendor->vendor_name }}?"
                        data-text="The vendor will no longer see this media in its panel and is notified." data-button="Yes, remove">
                        @csrf
                        <input type="hidden" name="vendor_id" value="{{ $vendorId }}">
                        <div class="vi-card-header">
                            <span>Assigned to {{ $vendor->vendor_name }} ({{ $assigned->total() }})</span>
                            <button type="submit" class="btn btn-sm vi-header-btn danger js-pick-submit" disabled>
                                <i class="mdi mdi-minus-circle"></i> Remove Selected <span class="js-pick-count"></span></button>
                        </div>
                        @include('superadm.vendor-portal._media-pick', ['rows' => $assigned, 'showVendor' => false, 'offset' => max(0, ($assigned->firstItem() ?? 1) - 1)])
                    </form>
                    {{-- Outside the Remove form so the "Show" selector never submits it. --}}
                    @include('superadm.vendor-portal._pager', ['page' => $assigned, 'sizeKey' => 'mper_page', 'size' => $mPerPage, 'noun' => 'media', 'pageKey' => 'mpage', 'anchor' => 'manage'])
                </div>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        // Confirm before sending login details / assigning / removing.
        $(document).on('submit', 'form.js-confirm', function (e) {
            const form = this;
            if (form.dataset.ok) return;
            e.preventDefault();
            Swal.fire({
                title: form.dataset.title, text: form.dataset.text, icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#00929c', confirmButtonText: form.dataset.button || 'Yes, continue'
            }).then(function (r) { if (r.isConfirmed) { form.dataset.ok = '1'; form.submit(); } });
        });

        // Selection: select-all, row highlight, and "(n)" on the action button.
        function refreshPick(form) {
            const boxes = form.querySelectorAll('input[name="media_ids[]"]');
            const checked = form.querySelectorAll('input[name="media_ids[]"]:checked');
            boxes.forEach(function (b) { b.closest('tr').classList.toggle('vi-selected-row', b.checked); });
            const all = form.querySelector('.js-pick-all');
            if (all) {
                all.checked = boxes.length > 0 && checked.length === boxes.length;
                all.indeterminate = checked.length > 0 && checked.length < boxes.length;
            }
            form.querySelector('.js-pick-submit').disabled = checked.length === 0;
            form.querySelector('.js-pick-count').textContent = checked.length ? '(' + checked.length + ')' : '';
        }
        $(document).on('change', '.js-pick-all', function () {
            const form = this.closest('form');
            form.querySelectorAll('input[name="media_ids[]"]').forEach(b => { b.checked = this.checked; });
            refreshPick(form);
        });
        $(document).on('change', 'input[name="media_ids[]"]', function () { refreshPick(this.closest('form')); });
        // Clicking anywhere on a row toggles it.
        $(document).on('click', '.js-pick-form tbody tr[data-pick]', function (e) {
            if (e.target.closest('input, a, button')) return;
            const box = this.querySelector('input[name="media_ids[]"]');
            box.checked = !box.checked;
            refreshPick(this.closest('form'));
        });
        document.querySelectorAll('.js-pick-form').forEach(refreshPick);
    </script>
@endsection

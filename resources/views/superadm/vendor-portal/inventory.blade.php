@extends('superadm.layout.master')

@section('content')
    <style>
        .vi-card { background: #fff; border-radius: 12px; box-shadow: 0 8px 20px rgba(0, 0, 0, .06); margin-bottom: 24px; overflow: hidden; }
        .vi-card-header { background: #00929c; color: #fff; padding: 12px 20px; font-weight: 600; font-size: 16px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; }
        .vi-card-body { padding: 20px; }
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
        .vi-header-btn { background: #fff; color: #00929c; border: 0; font-weight: 600; }
        .vi-header-btn:disabled { opacity: .6; cursor: not-allowed; }
        .vi-header-btn.danger { color: #DC2626; }
    </style>

    <div class="container-fluid mt-3">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-3" style="gap:12px">
            <div>
                <h3 class="mb-1" style="font-weight:600">Vendor Inventory &amp; Access</h3>
                <div class="vi-muted">Assign media to vendors, remove assignments, and send each vendor its Vendor Panel login.
                    Bulk assignment by Excel stays in <a href="{{ route('media.import-export') }}">Import &amp; Export</a> (Vendor column).</div>
            </div>
            <a href="{{ route('admin.vendor.approvals') }}" class="btn btn-outline-info"><i class="mdi mdi-check-decagram"></i> Vendor Approvals</a>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        {{-- ================= VENDORS ================= --}}
        <div class="vi-card">
            <div class="vi-card-header">
                <span>Vendors</span>
                <span style="font-weight:400;font-size:14px">{{ $summary->count() }} vendors</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover vi-table">
                    <thead>
                        <tr>
                            <th>Vendor</th>
                            <th>Login Email</th>
                            <th class="text-center">Media</th>
                            <th class="text-center">Pending</th>
                            <th>Vendor Panel Login</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($summary as $s)
                            <tr class="{{ $vendorId === $s->id ? 'vi-selected-row' : '' }}">
                                <td style="min-width:170px">
                                    <strong>{{ $s->vendor_name }}</strong>
                                    @unless ($s->is_active)<span class="vi-badge vi-rejected ml-1">Inactive</span>@endunless
                                    <div class="vi-muted">{{ $s->vendor_code }}</div>
                                    @if ($s->slug)
                                        <a href="{{ url($s->slug) }}" target="_blank" rel="noopener" style="font-size:13px"><i class="mdi mdi-web"></i> /{{ $s->slug }}</a>
                                    @endif
                                </td>
                                <td style="word-break:break-all;min-width:150px">{{ $s->email }}</td>
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
                                <td class="text-right">
                                    <a href="{{ route('admin.vendor.inventory', ['vendor_id' => $s->id]) }}#manage" class="btn btn-sm btn-info mb-1">
                                        <i class="mdi mdi-format-list-bulleted"></i> Manage Inventory</a>
                                    <form method="POST" action="{{ route('admin.vendor.credentials', $s->id) }}" class="d-inline js-confirm"
                                        data-title="{{ $s->credentials_sent_at ? 'Resend login details?' : 'Send login details?' }}"
                                        data-text="{{ $s->credentials_sent_at ? 'A NEW password will be generated and emailed to ' . $s->email . '. The old one stops working.' : 'A password will be generated and the login details emailed to ' . $s->email . '.' }}"
                                        data-button="Yes, send">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            <i class="mdi mdi-email-outline"></i> {{ $s->credentials_sent_at ? 'Resend Login' : 'Send Login' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div id="manage"></div>
        @if (!$vendor)
            <div class="vi-card">
                <div class="vi-card-body text-center py-5">
                    <i class="mdi mdi-cursor-default-click-outline" style="font-size:36px;color:#00929c"></i>
                    <h5 class="mt-2 mb-1">Choose a vendor to manage its inventory</h5>
                    <div class="vi-muted">Click <strong>Manage Inventory</strong> on a vendor above to assign or remove media.</div>
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
                        <div class="vi-stat"><span class="vi-muted">Assigned media</span><b>{{ $assigned->count() }}</b></div>
                        <div class="vi-stat"><span class="vi-muted">Pending approval</span><b>{{ $assigned->where('approval_status', 'pending')->count() }}</b></div>
                    </div>
                    <form method="GET" action="{{ route('admin.vendor.inventory') }}#manage" class="d-flex" style="gap:8px">
                        <input type="hidden" name="vendor_id" value="{{ $vendorId }}">
                        <input type="search" name="q" value="{{ request('q') }}" class="form-control" style="min-width:260px" placeholder="Search code, title or area" aria-label="Search media">
                        <button class="btn btn-info"><i class="mdi mdi-magnify"></i> Search</button>
                        @if (request('q'))<a href="{{ route('admin.vendor.inventory', ['vendor_id' => $vendorId]) }}#manage" class="btn btn-outline-secondary">Clear</a>@endif
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-6">
                    <form method="POST" action="{{ route('admin.vendor.inventory.remove') }}" class="vi-card js-confirm js-pick-form"
                        data-title="Remove selected media from {{ $vendor->vendor_name }}?"
                        data-text="The vendor will no longer see this media in its panel and is notified." data-button="Yes, remove">
                        @csrf
                        <input type="hidden" name="vendor_id" value="{{ $vendorId }}">
                        <div class="vi-card-header">
                            <span>Assigned to {{ $vendor->vendor_name }} ({{ $assigned->count() }})</span>
                            <button type="submit" class="btn btn-sm vi-header-btn danger js-pick-submit" disabled>
                                <i class="mdi mdi-minus-circle"></i> Remove Selected <span class="js-pick-count"></span></button>
                        </div>
                        @include('superadm.vendor-portal._media-pick', ['rows' => $assigned, 'showVendor' => false])
                    </form>
                </div>
                <div class="col-xl-6">
                    <form method="POST" action="{{ route('admin.vendor.inventory.assign') }}" class="vi-card js-confirm js-pick-form"
                        data-title="Assign selected media to {{ $vendor->vendor_name }}?"
                        data-text="Media assigned to another vendor will move to {{ $vendor->vendor_name }}. Both vendors are notified." data-button="Yes, assign">
                        @csrf
                        <input type="hidden" name="vendor_id" value="{{ $vendorId }}">
                        <div class="vi-card-header">
                            <span>Other Inventory ({{ $others->count() }}{{ $others->count() >= 200 ? '+' : '' }})</span>
                            <button type="submit" class="btn btn-sm vi-header-btn js-pick-submit" disabled>
                                <i class="mdi mdi-plus-circle"></i> Assign Selected <span class="js-pick-count"></span></button>
                        </div>
                        @include('superadm.vendor-portal._media-pick', ['rows' => $others, 'showVendor' => true])
                        @if ($others->count() >= 200)<div class="vi-muted px-3 py-2">Showing the first 200. Search to narrow down.</div>@endif
                    </form>
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

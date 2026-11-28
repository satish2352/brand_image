@extends('superadm.layout.master')

@section('content')
    {{-- The shared Operations board uses the vendor panel's .vp-* classes (scoped, safe here). --}}
    <link rel="stylesheet" href="{{ asset('asset/css/vendor-panel.css') }}?v={{ @filemtime(public_path('asset/css/vendor-panel.css')) }}">
    <style>
        .ops-page { padding: 0 6px 30px; color: #0F172A; }
        .ops-page .vp-thumb { object-fit: cover; border-radius: 8px; }
        .ops-head { display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end; justify-content: space-between; margin-bottom: 18px; }
        .ops-head h4 { margin: 0; font-weight: 700; }
        .ops-filters { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin: 0; }
        .ops-filters select, .ops-filters input { height: 38px; border: 1px solid #CBD5E1; border-radius: 8px; padding: 0 10px; font-size: 14px; background: #fff; }
        .ops-filters input { width: 280px; }
        .ops-board { display: flex; flex-direction: column; gap: 20px; }
    </style>

    <div class="container-fluid ops-page">
        <div class="ops-head">
            <div>
                <h4>Operations</h4>
                <div class="text-muted" style="font-size:14px;margin-top:4px">Upload installation, monitoring and end-date photos, share them with the user and record media removal. Vendors see these photos in their panel.</div>
            </div>
            <form method="GET" action="{{ route('admin.vendor.operations') }}" class="ops-filters" role="search">
                <input type="hidden" name="stage" value="{{ $stage }}">
                <select name="vendor_id" id="opsVendor" aria-label="Vendor" onchange="this.form.submit()">
                    <option value="">All vendors</option>
                    @foreach ($vendors as $v)<option value="{{ $v->id }}" @selected($vendorId === $v->id)>{{ $v->vendor_name }}</option>@endforeach
                </select>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search booking no., hoarding code, vendor" aria-label="Search operations">
                <button class="btn btn-info"><i class="mdi mdi-magnify"></i> Search</button>
                @if (request('q') || $vendorId)
                    <a href="{{ route('admin.vendor.operations', ['stage' => $stage]) }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </form>
        </div>

        @unless (session('ops_focus')) {{-- after an upload / share the message is shown in the Photos section --}}
            @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @endunless
        @if ($errors->any() && !$errors->hasAny(['photo', 'photo_date', 'shot_type', 'share']))<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        <div class="ops-board">
            @include('operations._board', [
                'routes'     => ['index' => 'admin.vendor.operations', 'photo' => 'admin.vendor.operations.photo', 'share' => 'admin.vendor.operations.share', 'complete' => 'admin.vendor.operations.complete'],
                'baseQuery'  => array_filter(['vendor_id' => $vendorId]),
                'canEdit'    => true,
                'showVendor' => true,
                'mediaUrl'   => fn ($id) => route('media.viewdetails', base64_encode($id)),
            ])
        </div>
    </div>
@endsection

@section('scripts')
    {{-- Vendor filter: a searchable dropdown (type to find a vendor). --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        .ops-filters .select2-container { min-width: 240px; }
        .ops-filters .select2-container .select2-selection--single { height: 38px; border: 1px solid #CBD5E1; border-radius: 8px; }
        .ops-filters .select2-container .select2-selection--single .select2-selection__rendered { line-height: 36px; padding-left: 10px; font-size: 14px; }
        .ops-filters .select2-container .select2-selection--single .select2-selection__arrow { height: 36px; }
        .select2-dropdown { border-color: #CBD5E1; }
        .select2-search--dropdown .select2-search__field { border-radius: 6px; padding: 6px 8px; }
        .select2-results__option--highlighted[aria-selected], .select2-results__option--highlighted.select2-results__option--selectable { background: #00929c !important; }
    </style>
    <script>
        $(function () {
            $('#opsVendor').select2({
                width: 'style',
                placeholder: 'All vendors',
                language: { noResults: function () { return 'No vendor found'; } }
            }); // picking a vendor fires the select's own change → onchange submits the filter
            // Typing goes straight into the search box when the dropdown opens.
            $('#opsVendor').on('select2:open', function () {
                setTimeout(function () { const f = document.querySelector('.select2-container--open .select2-search__field'); if (f) f.focus(); }, 0);
            });
        });

        // "Share with User" asks first (the vendor panel does this through data-confirm too).
        // Share menu: each Send button asks about the number / email typed next to it. WhatsApp also
        // opens the ready-made chat in a new tab right on the click, while this page records the share.
        document.addEventListener('submit', function (e) {
            const btn = e.submitter;
            if (btn && btn.dataset.confirmTpl) {
                const field = e.target.querySelector('[name="' + btn.dataset.field + '"]');
                const to = field ? field.value.trim() : '';
                if (!to) { e.preventDefault(); alert(btn.dataset.field === 'to_mobile' ? 'Enter the mobile number.' : 'Enter the email address.'); if (field) field.focus(); return; }
                if (btn.dataset.waText !== undefined) {
                    let digits = to.replace(/\D+/g, '');
                    if (digits.length === 10) digits = '91' + digits;
                    if (digits.length < 10) { e.preventDefault(); alert('Enter a valid mobile number.'); return; }
                    if (!window.confirm(btn.dataset.confirmTpl.replace('{to}', to))) { e.preventDefault(); return; }
                    window.open('https://wa.me/' + digits + '?text=' + encodeURIComponent(btn.dataset.waText), '_blank', 'noopener');
                    return;
                }
                if (!window.confirm(btn.dataset.confirmTpl.replace('{to}', to))) e.preventDefault();
                return;
            }
            const msg = e.target.getAttribute && e.target.getAttribute('data-confirm');
            if (msg && !window.confirm(msg)) e.preventDefault();
        });
        // Close an open Share menu on an outside click.
        document.addEventListener('click', function (e) {
            document.querySelectorAll('details.ops-sharemenu[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); });
        });
    </script>
@endsection

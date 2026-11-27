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
                <select name="vendor_id" aria-label="Vendor" onchange="this.form.submit()">
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

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

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
    <script>
        // "Share with User" asks first (the vendor panel does this through data-confirm too).
        document.addEventListener('submit', function (e) {
            const msg = e.target.getAttribute && e.target.getAttribute('data-confirm');
            if (msg && !window.confirm(msg)) e.preventDefault();
        });
    </script>
@endsection

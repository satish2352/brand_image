@extends('superadm.layout.master')

@section('content')
    @include('superadm.vendor-portal._styles')
    <div class="card">
        <div class="card-body">
            <h4 class="mb-1">Vendor Inventory &amp; Access</h4>
            <div class="vpa-muted mb-3">Assign inventory to vendors, remove assignments, and send each vendor its Vendor Panel login. Bulk assignment by Excel stays in
                <a href="{{ route('media.import-export') }}">Import &amp; Export</a> (Vendor column).</div>

            @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

            <div class="table-responsive mb-4">
                <table class="table table-bordered vpa-table">
                    <thead><tr><th>Vendor</th><th>Login Email</th><th>Media</th><th>Pending Approval</th><th>Website</th><th>Login Details</th><th>Last Login</th><th>Action</th></tr></thead>
                    <tbody>
                        @foreach ($summary as $s)
                            <tr class="{{ $vendorId === $s->id ? 'table-warning' : '' }}">
                                <td><strong>{{ $s->vendor_name }}</strong> <span class="vpa-muted">({{ $s->vendor_code }})</span>@unless ($s->is_active)<span class="vpa-badge vpa-rejected ml-1">Inactive</span>@endunless</td>
                                <td>{{ $s->email }}</td>
                                <td>{{ $s->total }}</td>
                                <td>@if ($s->pending)<a href="{{ route('admin.vendor.approvals', ['vendor_id' => $s->id]) }}" class="vpa-badge vpa-pending">{{ $s->pending }}</a>@else 0 @endif</td>
                                <td>@if ($s->slug)<a href="{{ url($s->slug) }}" target="_blank" rel="noopener">/{{ $s->slug }}</a>@endif</td>
                                <td class="text-nowrap">{{ $s->credentials_sent_at ? 'Sent ' . \Carbon\Carbon::parse($s->credentials_sent_at)->format('d M Y') : 'Not sent' }}</td>
                                <td class="text-nowrap">{{ $s->last_login_at ? \Carbon\Carbon::parse($s->last_login_at)->format('d M Y, h:i A') : '—' }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('admin.vendor.inventory', ['vendor_id' => $s->id]) }}" class="btn btn-sm btn-primary">Manage Inventory</a>
                                    <form method="POST" action="{{ route('admin.vendor.credentials', $s->id) }}" class="d-inline js-confirm"
                                        data-text="{{ $s->credentials_sent_at ? 'A NEW password will be generated and emailed to ' . $s->email . '. The old one stops working.' : 'Generate a password and email the login details to ' . $s->email . '?' }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">{{ $s->credentials_sent_at ? 'Resend Login' : 'Send Login' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($vendor)
                <form method="GET" class="form-inline mb-3">
                    <input type="hidden" name="vendor_id" value="{{ $vendorId }}">
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control mr-2" placeholder="Search code, title or area">
                    <button class="btn btn-outline-secondary">Search</button>
                </form>
                <div class="row">
                    <div class="col-lg-6">
                        <form method="POST" action="{{ route('admin.vendor.inventory.remove') }}" class="js-confirm" data-text="Remove the selected media from {{ $vendor->vendor_name }}?">
                            @csrf
                            <input type="hidden" name="vendor_id" value="{{ $vendorId }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0">Assigned to {{ $vendor->vendor_name }} ({{ $assigned->count() }})</h5>
                                <button type="submit" class="btn btn-sm btn-danger">Remove Selected</button>
                            </div>
                            @include('superadm.vendor-portal._media-pick', ['rows' => $assigned, 'showVendor' => false])
                        </form>
                    </div>
                    <div class="col-lg-6">
                        <form method="POST" action="{{ route('admin.vendor.inventory.assign') }}" class="js-confirm" data-text="Assign the selected media to {{ $vendor->vendor_name }}?">
                            @csrf
                            <input type="hidden" name="vendor_id" value="{{ $vendorId }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0">Other inventory</h5>
                                <button type="submit" class="btn btn-sm btn-success">Assign Selected</button>
                            </div>
                            @include('superadm.vendor-portal._media-pick', ['rows' => $others, 'showVendor' => true])
                            @if ($others->count() >= 200)<div class="vpa-muted">Showing the first 200. Search to narrow down.</div>@endif
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).on('submit', 'form.js-confirm', function (e) {
            const form = this;
            if (form.dataset.ok) return;
            e.preventDefault();
            Swal.fire({ title: 'Are you sure?', text: form.dataset.text, icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, continue' })
                .then(function (r) { if (r.isConfirmed) { form.dataset.ok = '1'; form.submit(); } });
        });
        $(document).on('change', '.js-pick-all', function () {
            $(this).closest('table').find('input[name="media_ids[]"]').prop('checked', this.checked);
        });
    </script>
@endsection

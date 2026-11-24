@extends('superadm.layout.master')

@section('content')
    @include('superadm.vendor-portal._styles')
    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-1">Vendor Media Approvals</h4>
                    <div class="vpa-muted">Media added by vendors from the Vendor Panel. It stays off the website until approved.</div>
                </div>
                <form method="GET" class="form-inline">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <select name="vendor_id" class="form-control" onchange="this.form.submit()">
                        <option value="">All vendors</option>
                        @foreach ($vendors as $v)<option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->vendor_name }}</option>@endforeach
                    </select>
                </form>
            </div>

            @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

            <div class="vpa-tabs mb-3">
                @foreach ($labels as $key => $label)
                    <a href="{{ route('admin.vendor.approvals', array_filter(['status' => $key, 'vendor_id' => request('vendor_id')])) }}" class="{{ $status === $key ? 'active' : '' }}">
                        {{ $label }} · {{ $counts[$key] ?? 0 }}</a>
                @endforeach
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped vpa-table">
                    <thead>
                        <tr><th>Sr. No</th><th>Submitted</th><th>Vendor</th><th>Sector Code</th><th>Media Title</th><th>Category</th><th>Location</th><th>Price</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($media as $m)
                            <tr>
                                <td>{{ $media->firstItem() + $loop->index }}</td>
                                <td class="text-nowrap">{{ $m->submitted_at ? \Carbon\Carbon::parse($m->submitted_at)->format('d M Y, h:i A') : '—' }}</td>
                                <td>{{ $m->vendor_name }} <span class="vpa-muted">({{ $m->vendor_code }})</span></td>
                                <td class="text-nowrap"><strong>{{ $m->hoarding_code ?: '—' }}</strong></td>
                                <td>{{ $m->media_title }}</td>
                                <td>{{ $m->category_name }}</td>
                                <td>{{ $m->area_name }}{{ $m->city_name ? ', ' . $m->city_name : '' }}</td>
                                <td class="text-nowrap">₹ {{ number_format((float) $m->price) }}</td>
                                <td>
                                    <span class="vpa-badge vpa-{{ $status }}">{{ $labels[$status] }}</span>
                                    @if ($status === 'rejected' && $m->rejection_reason)<div class="vpa-muted mt-1">{{ \Illuminate\Support\Str::limit($m->rejection_reason, 60) }}</div>@endif
                                </td>
                                <td class="text-nowrap"><a href="{{ route('admin.vendor.approvals.show', $m->id) }}" class="btn btn-sm btn-primary">Review</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-4">Nothing here.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $media->links('pagination::bootstrap-4') }}
        </div>
    </div>
@endsection

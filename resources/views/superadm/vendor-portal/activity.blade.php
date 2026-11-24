@extends('superadm.layout.master')

@section('content')
    @include('superadm.vendor-portal._styles')
    <div class="card">
        <div class="card-body">
            <h4 class="mb-1">Vendor Activity Log</h4>
            <div class="vpa-muted mb-3">Every change made in the Vendor Panel, and every Brand Adda action on vendor inventory. Kept for the last {{ \App\Models\VendorActivityLog::RETENTION_MONTHS }} months.</div>

            <form method="GET" class="form-row mb-3">
                <div class="col-md-2 mb-2"><select name="vendor_id" class="form-control"><option value="">All vendors</option>
                    @foreach ($vendors as $v)<option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->vendor_name }}</option>@endforeach</select></div>
                <div class="col-md-2 mb-2"><select name="actor" class="form-control"><option value="">Vendor &amp; Admin</option>
                    <option value="vendor" @selected(request('actor') === 'vendor')>By vendor</option><option value="admin" @selected(request('actor') === 'admin')>By Brand Adda</option></select></div>
                <div class="col-md-2 mb-2"><select name="action" class="form-control"><option value="">All activities</option>
                    @foreach ($actions as $a)<option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>@endforeach</select></div>
                <div class="col-md-2 mb-2"><input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control" aria-label="From date"></div>
                <div class="col-md-2 mb-2"><input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control" aria-label="To date"></div>
                <div class="col-md-2 mb-2 d-flex"><input type="search" name="q" value="{{ request('q') }}" class="form-control mr-2" placeholder="Media code">
                    <button class="btn btn-primary">Filter</button></div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped vpa-table">
                    <thead><tr><th>Date / Time</th><th>Vendor</th><th>Activity</th><th>Media Code</th><th>Details</th><th>By</th></tr></thead>
                    <tbody>
                        @forelse ($log as $l)
                            <tr>
                                <td class="text-nowrap">{{ $l->created_at->format('d M Y, h:i A') }}</td>
                                <td>{{ optional($l->vendor)->vendor_name ?: '—' }}</td>
                                <td class="text-nowrap"><strong>{{ $l->action }}</strong></td>
                                <td class="text-nowrap">@if ($l->media_id)<a href="{{ route('admin.vendor.approvals.show', $l->media_id) }}">{{ $l->media_code }}</a>@else — @endif</td>
                                <td>@foreach ($l->details ?? [] as $k => $v){{ $k }}: {{ is_array($v) ? implode(' → ', $v) : $v }}@if (!$loop->last) · @endif @endforeach</td>
                                <td class="text-nowrap">{{ $l->actor_type === 'admin' ? 'Admin · ' : 'Vendor · ' }}{{ $l->actor_name }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No activity yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $log->links('pagination::bootstrap-4') }}
        </div>
    </div>
@endsection

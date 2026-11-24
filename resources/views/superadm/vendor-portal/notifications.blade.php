@extends('superadm.layout.master')

@section('content')
    @include('superadm.vendor-portal._styles')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-1">Vendor Notifications</h4>
                    <div class="vpa-muted">{{ $unread }} unread</div>
                </div>
                @if ($unread)
                    <form method="POST" action="{{ route('admin.vendor.notifications.read-all') }}">@csrf
                        <button class="btn btn-outline-secondary btn-sm">Mark all as read</button></form>
                @endif
            </div>
            @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

            <div class="list-group">
                @forelse ($notifications as $n)
                    <a href="{{ route('admin.vendor.notifications.open', $n->id) }}" class="list-group-item list-group-item-action {{ $n->is_read ? '' : 'list-group-item-warning' }}">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $n->title }}</strong>
                            <small class="text-muted text-nowrap ml-3">{{ $n->created_at->diffForHumans() }}</small>
                        </div>
                        @if ($n->message)<div style="font-size:13px">{{ $n->message }}</div>@endif
                        @if ($n->vendor)<small class="text-muted">{{ $n->vendor->vendor_name }}</small>@endif
                    </a>
                @empty
                    <div class="text-muted">No vendor notifications yet.</div>
                @endforelse
            </div>
            <div class="mt-3">{{ $notifications->links('pagination::bootstrap-4') }}</div>
        </div>
    </div>
@endsection

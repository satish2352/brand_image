@extends('vendor.layout')
@section('title', 'Notifications')
@section('crumb', 'Vendor Panel / Notifications')
@section('heading', 'Notifications')

@section('actions')
    @if ($unread)
        <form method="POST" action="{{ route('vendor.notifications.read-all') }}" style="margin:0">@csrf
            <button type="submit" class="vp-btn vp-btn-g">Mark all as read</button></form>
    @endif
@endsection

@section('content')
    <div>
        <section class="vp-card" style="padding:8px 20px 12px">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 0 6px">
                <h2 class="vp-h2">Notifications</h2>
                @if ($unread)<span class="vp-badge vp-badge-pending">{{ $unread }} unread</span>@endif
            </div>
            @forelse ($notifications as $n)
                @include('vendor.partials.notification-row', ['n' => $n])
            @empty
                <p class="vp-muted" style="margin:8px 0">No notifications yet.</p>
            @endforelse
        </section>
        <p class="vp-muted" style="font-size:13px;margin:14px 2px 0">
            Looking for everything done on your account? See the <a href="{{ route('vendor.activity') }}" style="font-weight:700">Activity Log</a>.
        </p>

    </div>
@endsection

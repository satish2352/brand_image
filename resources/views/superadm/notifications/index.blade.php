@if ($notifications->count() == 0)
    <p class="text-muted text-center">No new notifications 🎉</p>
@else
    <ul style="padding-left:20px; list-style:disc;">
        @foreach ($notifications as $noti)
            @php
                $firstItem = $noti->order?->items?->first();
            @endphp

            <li class="mb-3 border-bottom pb-2">
                <a href="{{ route('admin.notifications.read', $noti->id) }}" style="text-decoration:none;color:inherit;">
                    <strong>
                        {{ $noti->order?->customer?->name ?? 'Unknown User' }}

                        @if ($noti->media_id)
                            booked {{ $noti->media?->media_title ?? 'N/A' }}
                        @else
                            completed payment for order {{ $noti->order?->order_no }}
                        @endif
                    </strong>
                    <br>

                    @if ($noti->media_id)
                        From {{ \Carbon\Carbon::parse($firstItem?->from_date)->format('d M Y') ?? '-' }}
                        To {{ \Carbon\Carbon::parse($firstItem?->to_date)->format('d M Y') ?? '-' }}
                        <br>
                    @endif

                    <small class="text-muted">
                        {{ $noti->created_at->diffForHumans() }}
                    </small>
                </a>
            </li>
        @endforeach
    </ul>
@endif

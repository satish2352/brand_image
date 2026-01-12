<style>
.notification-item {
    border-bottom: 1px solid #eee;
    padding: 10px 15px;
    cursor: pointer;
}
.notification-item:last-child {
    border-bottom: none;
}
.notification-item:hover {
    background-color: #f8f9fa;
}
.notification-title {
    font-size: 15px;
    font-weight: 600;
    color: #333;
}
.notification-time {
    font-size: 12px;
    color: #888;
}
</style>

@if($notifications->count() == 0)
    <p class="text-muted text-center p-3 mb-0">No notifications</p>
@else
    @foreach($notifications as $notify)
        <div class="notification-item d-block text-dark"
             data-id="{{ $notify->id }}"
             data-url="{{ route('admin.notifications.read', $notify->id) }}"
             style="cursor:pointer;">
            <div class="notification-title">
                {{ $notify->data['message'] ?? 'Notification' }}
            </div>
            <div class="notification-time">
                {{ $notify->created_at->diffForHumans() }}
            </div>
        </div>
    @endforeach
@endif
<script>
$(document).on('click', '.notification-item', function() {
    let id = $(this).data('id');
    let redirectUrl = $(this).data('url');
    let item = $(this);

    // Remove it visually
    item.fadeOut(300, function() {
        $(this).remove();
    });

    // Update badge count
    let badge = $('.fa-bell').siblings('.badge');
    let count = parseInt(badge.text()) - 1;
    if (count <= 0) {
        badge.remove();
    } else {
        badge.text(count);
    }

    // Redirect after a tiny delay
    setTimeout(() => window.location.href = redirectUrl, 350);
});
</script>



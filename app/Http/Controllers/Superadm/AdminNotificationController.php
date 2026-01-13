<?php

namespace App\Http\Controllers\Superadm;

use App\Models\User;
use App\Models\Notification;
use App\Http\Controllers\Controller;

class AdminNotificationController extends Controller
{
    public function getData()
    {
        $adminId = session('user_id') ?? session('id');

        $notifications = Notification::where('user_id', $adminId)
            ->where('is_read', 0)
            ->with([
                'order.items',
                'order.customer',
                'media'
            ])
            ->latest()
            ->take(20)
            ->get();

        return view('superadm.notifications.index', compact('notifications'));
    }



    public function markAllRead()
    {
        $adminId = session('user_id') ?? session('id');

        Notification::where('user_id', $adminId)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return response()->json(['status' => 'success']);
    }

    public function read($id)
    {
        $adminId = session('user_id') ?? session('id');

        Notification::where('user_id', $adminId)
            ->where('id', $id)
            ->update(['is_read' => 1]);

        return redirect()->route('admin.booking.list-booking');
    }
    public function count()
    {
        $adminId = session('user_id') ?? session('id');

        if (!$adminId) {
            return 0;
        }

        return \App\Models\Notification::where('user_id', $adminId)
            ->where('is_read', 0)
            ->count();
    }
}

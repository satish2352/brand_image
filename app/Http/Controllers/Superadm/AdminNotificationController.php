<?php

namespace App\Http\Controllers\Superadm;

use App\Models\User;
use App\Http\Controllers\Controller;

class AdminNotificationController extends Controller
{
    public function index()
    {
        // Get admin ID stored in session
        $adminId = session('id');

        // If admin not logged in, redirect
        if (!$adminId) {
            return redirect()->route('login')->with('error', 'Please login');
        }

        // Load admin user
        $admin = User::find($adminId);

        // Get unread notifications
        $notifications = $admin ? $admin->unreadNotifications : collect([]);

        return view('superadm.notifications.index', compact('notifications'));
    }

    public function markAllRead()
    {
        $adminId = session('id');

        if ($adminId) {
            $admin = User::find($adminId);

            if ($admin) {
                $admin->unreadNotifications->markAsRead();
            }
        }

        return back()->with('success', 'All notifications marked as read');
    }

    public function getData()
    {
        $adminId = session('user_id') ?? session('id');
        $admin = $adminId ? \App\Models\User::find($adminId) : null;

        if (!$admin) {
            return "<p>No admin logged in!</p>";
        }

        $notifications = $admin->notifications()->orderBy('created_at', 'desc')->take(20)->get();

        return view('superadm.notifications.index', compact('notifications'));
    }

    public function read($id)
    {
        $adminId = session('user_id') ?? session('id');
        $admin = $adminId ? \App\Models\User::find($adminId) : null;

        if (!$admin) {
            return redirect()->route('login');
        }

        // Find notification by ID
        $notification = $admin->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        // 🔥 Redirect wherever you want:
        return redirect()->route('admin.booking.list-booking');
    }
}

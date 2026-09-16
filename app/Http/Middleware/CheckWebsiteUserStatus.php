<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Support\AdminSession;
use Illuminate\Support\Facades\Auth;

class CheckWebsiteUserStatus
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('website')->check()) {

            $user = Auth::guard('website')->user();

            if ($user->is_deleted == 1) {

                Auth::guard('website')->logout();
                AdminSession::invalidateKeepingAdmin($request);

                $message = 'Your account has been deleted by admin.';

            } elseif ($user->is_active == 0) {

                Auth::guard('website')->logout();
                AdminSession::invalidateKeepingAdmin($request);

                $message = 'Your account has been deactivated by admin.';

            } else {
                return $next($request);
            }

            // 🔥 AJAX request
            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => false,
                    'reason'  => 'account_disabled',
                    'message' => $message,
                ], 401);
            }

            // 🔥 Normal request
            return redirect('/')
                ->with('auto_logout_message', $message);
        }

        return $next($request);
    }
}


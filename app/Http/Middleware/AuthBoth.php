<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthBoth
{
    public function handle(Request $request, Closure $next)
    {
        // Superadmin session check
        if ($request->session()->has('user_id')) {
            return $next($request);
        }

        // Website user guard check
        if (Auth::guard('website')->check()) {
            return $next($request);
        }

        // Not logged-in anywhere
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        return redirect('/')
            ->with('error', 'You must login to access this resource');
    }
}

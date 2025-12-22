<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebsiteAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('website')->check()) {
            return redirect('/')
                ->with('login_required', 'Please login to place order');
        }

        return $next($request);
    }
}

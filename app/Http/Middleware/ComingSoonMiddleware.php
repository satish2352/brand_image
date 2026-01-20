<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ComingSoonMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Allow admin & login pages
        if (
            $request->is('dashboard*') ||
            $request->is('admin*') ||
            $request->is('login')
        ) {
            return $next($request);
        }

        // Show coming soon if enabled
        if (env('COMING_SOON', false)) {
            return response()->view('website.coming-soon');
        }

        return $next($request);
    }
}

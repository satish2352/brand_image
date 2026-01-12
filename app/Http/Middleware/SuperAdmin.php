<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is logged in using session user_id
        if (!$request->session()->has('user_id')) {
            return redirect()->route('login')->with('error', 'Please login');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Employee
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        //  Check if employee session exists
        if (!$request->session()->has('emp_user_id')) {
            // Redirect to employee login page
            return redirect()->route('emp.login');
        }

        return $next($request);
    }
}

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
        // ✅ Check if employee session exists
        if (!$request->session()->has('emp_user_id')) {
            // Redirect to employee login page
            return redirect()->route('emp.login');
        }

        return $next($request);
    }
}


// namespace App\Http\Middleware;

// use Closure;
// use Illuminate\Http\Request;
// use Symfony\Component\HttpFoundation\Response;

// class Employee
// {
//     /**
//      * Handle an incoming request.
//      *
//      * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
//      */
//     public function handle(Request $request, Closure $next): Response
//     {
//         if ( $request->session()->has('user_id') == false  && $request->session()->get('role_id') == 0) {
//             return redirect()->route('login');
//         }

//         return $next($request);
//     }
// }

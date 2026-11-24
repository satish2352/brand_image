<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards /vendor-panel. A vendor deactivated or deleted from the admin Vendor
 * master loses access on its next request, not only at its next login.
 */
class VendorAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('vendor');

        if (!$guard->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please log in.'], 401);
            }

            return redirect()->guest(route('vendor.login'));
        }

        $vendor = $guard->user();

        if (!$vendor->is_active || $vendor->is_deleted) {
            $guard->logout();

            return redirect()->route('vendor.login')
                ->with('error', 'This vendor account is not active. Contact Brand Adda.');
        }

        return $next($request);
    }
}

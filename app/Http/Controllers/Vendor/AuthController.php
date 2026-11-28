<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Vendor Authentication (PRD 2.1): registered email + password, issued by the
 * Super Admin. The account IS the vendor record, so after login every query is
 * scoped to auth('vendor')->id() — the vendor's own inventory and nothing else.
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('vendor')->check()) {
            return redirect()->route('vendor.dashboard');
        }

        return view('vendor.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required'    => 'Enter your registered email ID.',
            'password.required' => 'Enter your password.',
        ]);

        $vendor = Vendor::where('email', $data['email'])
            ->where('is_deleted', 0)
            ->whereNotNull('password')
            ->first();

        // One message for every failure, so the form cannot be used to find
        // out which addresses belong to vendors.
        if (!$vendor || !Hash::check($data['password'], $vendor->password)) {
            return back()->withInput($request->only('email'))
                ->with('error', 'Email ID or password is incorrect.');
        }

        if (!$vendor->is_active) {
            return back()->withInput($request->only('email'))
                ->with('error', 'This vendor account is not active. Contact Brand Adda.');
        }

        Auth::guard('vendor')->login($vendor, $request->boolean('remember'));
        $request->session()->regenerate();

        $vendor->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('vendor.dashboard'));
    }

    public function logout(Request $request)
    {
        // Only this guard: a customer or admin signed in elsewhere in the same
        // browser stays signed in.
        Auth::guard('vendor')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('vendor.login')->with('success', 'You have been logged out.');
    }
}

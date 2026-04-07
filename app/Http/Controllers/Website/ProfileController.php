<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function view()
    {
        $user = Auth::guard('website')->user();
        return view('website.profile.view', compact('user'));
    }

    public function edit()
    {
        $user = Auth::guard('website')->user();
        return view('website.profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::guard('website')->user();

        $request->validate([
            'name'          => 'required|string|max:100|regex:/^[A-Za-z\s]+$/',
            'mobile_number' => 'required|digits:10|regex:/^[6-9][0-9]{9}$/',
            'organisation'  => 'nullable|string|max:150',
            'gst'           => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
        ], [
            'name.required'          => 'Full name is required.',
            'name.regex'             => 'Name must contain letters only.',
            'mobile_number.required' => 'Mobile number is required.',
            'mobile_number.digits'   => 'Mobile number must be exactly 10 digits.',
            'mobile_number.regex'    => 'Mobile number must start with 6, 7, 8, or 9.',
            'gst.size'               => 'GST number must be exactly 15 characters.',
            'gst.regex'              => 'Enter a valid GST number (e.g. 27ABCDE1234F1Z5).',
        ]);

        $user->update([
            'name'         => $request->name,
            'mobile_number'=> $request->mobile_number,
            'organisation' => $request->organisation,
            'gst'          => $request->gst,
        ]);

        return redirect()->route('website.profile.view')
            ->with('success', 'Profile updated successfully!');
    }

    public function showChangePassword()
    {
        return view('website.profile.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'new_password' => 'required|min:6|confirmed',
        ], [
            'new_password.required'  => 'New password is required.',
            'new_password.min'       => 'New password must be at least 6 characters.',
            'new_password.confirmed' => 'New password and confirm password do not match.',
        ]);

        $user = Auth::guard('website')->user();

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Logout after password change
        Auth::guard('website')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('website.home')
            ->with('password_changed', 'Password changed successfully. Please log in with your new password.');
    }
}

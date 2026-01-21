<?php

namespace App\Http\Controllers\Superadm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ChangePasswordController extends Controller
{
    public function index()
    {
        return view('superadm.change-password');
    }

    public function updatePassword(Request $request)
    {
        $userId = Session::get('user_id');

        if (!$userId) {
            return redirect()->route('login')
                ->with('error', 'Session expired. Please login again.');
        }

        // ✅ VALIDATION (matches frontend)
        $request->validate([
            'new_password' => [
                'required',
                'string',
                'min:6',
                'regex:/^[A-Za-z0-9@]+$/'
            ],
            'confirm_password' => 'required|same:new_password',
        ], [
            'new_password.required' => 'Enter new password',
            'new_password.min' => 'Password must be at least 6 characters',
            'new_password.regex' => 'Password can contain letters, numbers and @ only',
            'confirm_password.required' => 'Confirm your password',
            'confirm_password.same' => 'Passwords do not match',
        ]);

        $user = User::find($userId);

        if (!$user) {
            return back()->with('error', 'User not found.');
        }

        // 🔐 Update password (DO NOT logout)
        $user->password = Hash::make($request->new_password);
        $user->save();

        // ✅ redirect to dashboard (NOT logout)
        return redirect()->route('dashboard')
            ->with('success', 'Password updated successfully!');
    }
}

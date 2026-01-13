<?php

namespace App\Http\Controllers\Superadm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class ChangePasswordController extends Controller
{
    public function index()
    {
        return view('superadm.change-password');
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_password' => [
                'required',
                'string',
                'min:8',
                'max:255',
                'regex:/^(?=(?:.*\d){2,})(?=(?:.*[A-Za-z]){5,})(?=.*[^A-Za-z0-9]).+$/'
            ],
            'confirm_password' => 'required|same:new_password',
        ], [
            'new_password.required' => 'Enter password',
            'new_password.min'      => 'Password must be at least 8 characters',
            'new_password.max'      => 'Password must not exceed 255 characters',
            'new_password.regex'    => 'Password must contain at least 2 digits, 5 letters, and 1 special character',
            'confirm_password.required' => 'Please confirm your password',
            'confirm_password.same'     => 'New Password & Confirm Password must match',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        //  Detect role properly
        $role = Session::get('role');

        if ($role === 'admin') {
            $userId = Session::get('user_id');
            $loginRoute = 'login'; // admin login route
            $sessionKeys = ['user_id', 'role_id', 'role', 'email_id', 'department_id', 'projects_id'];
        } else {
            return redirect()->back()->with('error', 'Session not found.');
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->back()->with('error', 'User not found.');
        }

        $user->password = bcrypt($request->new_password);
        $user->save();

        //  Logout current user
        Session::forget($sessionKeys);

        return redirect()->route($loginRoute)->with('success', 'Password updated successfully! Please login again.');
    }
}

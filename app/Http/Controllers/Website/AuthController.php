<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WebsiteUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\WebsiteOtpMail;

class AuthController extends Controller
{
    public function signup(Request $req)
    {
        $req->validate([
            'signup_name' => 'required',
            'signup_email' => 'required|email',
            'signup_mobile_number' => 'required|digits:10',
            'signup_password' => 'required|min:6',
        ]);

        // CHECK EXISTING USER
        $existingUser = WebsiteUser::where('email', $req->signup_email)->first();

        // ACCOUNT DELETED
        if ($existingUser && $existingUser->is_deleted == 1) {
            return response()->json([
                'status' => false,
                'message' => 'Your account has been deleted by admin.'
            ]);
        }

        // ALREADY VERIFIED
        if ($existingUser && $existingUser->is_email_verified == 1) {
            return response()->json([
                'status' => false,
                'message' => 'This email is already registered. Please login.'
            ]);
        }

        // NOT VERIFIED → RESEND OTP
        if ($existingUser && $existingUser->is_email_verified == 0) {

            $otp = rand(100000, 999999);

            $existingUser->update([
                'otp' => $otp,
                'otp_expires_at' => Carbon::now()->addMinutes(2),
            ]);

            Mail::to($existingUser->email)->send(new WebsiteOtpMail($otp));

            return response()->json([
                'status' => true,
                'email' => $existingUser->email,
                'message' => 'OTP resent to your email'
            ]);
        }

        // NEW USER
        $otp = rand(100000, 999999);

        $user = WebsiteUser::create([
            'name' => $req->signup_name,
            'email' => $req->signup_email,
            'mobile_number' => $req->signup_mobile_number,
            'organisation' => $req->signup_organisation ?? null,
            'gst' => $req->signup_gst ?? null,
            'password' => Hash::make($req->signup_password),
            'otp' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(2),
            'is_email_verified' => 0,
            'is_active' => 0,
            'is_deleted' => 0,
        ]);

        Mail::to($user->email)->send(new WebsiteOtpMail($otp));

        return response()->json([
            'status' => true,
            'email' => $user->email,
            'message' => 'OTP sent to your email'
        ]);
    }

    public function verifyOtp(Request $req)
    {
        $req->validate([
            'email' => 'required|email',
            'otp' => 'required'
        ]);

        $user = WebsiteUser::where('email', $req->email)->first();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'User not found']);
        }

        // OTP ALREADY USED / NOT GENERATED
        if (!$user->otp || !$user->otp_expires_at) {
            return response()->json([
                'status' => false,
                'message' => 'OTP expired. Please request a new OTP.'
            ]);
        }

        // if (Carbon::now()->gt($user->otp_expires_at)) {
        //     return response()->json(['status' => false, 'message' => 'OTP expired']);
        // }

        $graceSeconds = 5;

        if (Carbon::now()->gt($user->otp_expires_at->addSeconds($graceSeconds))) {
            return response()->json([
                'status' => false,
                'message' => 'OTP expired'
            ]);
        }

        if ($user->otp !== $req->otp) {
            return response()->json(['status' => false, 'message' => 'Invalid OTP, please check and enter correct OTP']);
        }

        $user->update([
            'otp' => null,
            'otp_expires_at' => null,
            'is_email_verified' => 1,
            'is_active' => 1,
        ]);

        Auth::guard('website')->login($user);

        return response()->json([
            'status' => true,
            'message' => 'Account verified successfully'
        ]);
    }

    public function resendOtp(Request $req)
    {
        $req->validate(['email' => 'required|email']);

        $otp = rand(100000, 999999);

        WebsiteUser::where('email', $req->email)->update([
            'otp' => $otp,
            'otp_expires_at' => Carbon::now()->addMinute(2),
        ]);

        Mail::to($req->email)->send(new WebsiteOtpMail($otp));

        return response()->json(['status' => true, 'message' => 'OTP resent']);
    }


    public function login(Request $req)
    {
        // VALIDATION
        $req->validate([
            'login_email' => 'required|email',
            'login_password' => 'required',
        ], [
            'login_email.required' => 'Please enter email ID.',
            'login_email.email' => 'Enter a valid email address.',
            'login_password.required' => 'Please enter password.',
        ]);

        // GET USER
        $user = WebsiteUser::where('email', $req->login_email)->first();

        // EMAIL NOT FOUND
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'You need to sign up first, and then you can log in.'
            ]);
        }

        // ACCOUNT DELETED BY ADMIN
        if ($user->is_deleted == 1) {
            return response()->json([
                'status' => false,
                'message' => 'Your account has been deleted by admin.'
            ]);
        }

        // PASSWORD WRONG
        if (!Hash::check($req->login_password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid password.'
            ]);
        }
        //  ACCOUNT INACTIVE CHECK ( MAIN FIX)
        if ($user->is_active == 0) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is inactive. Please contact admin to activate your account.'
            ]);
        }
        // STORE SESSION
        // session(['website_user' => $user]);
        Auth::guard('website')->login($user);
        $req->session()->regenerate();
        return response()->json([
            'status' => true,
            'message' => 'Login successful!'
        ]);
    }



    public function logout(Request $request)
    {
        Auth::guard('website')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Logged out successfully!');
    }
}

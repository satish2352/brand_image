<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WebsiteUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // public function signup(Request $req)
    // {
    //     // VALIDATION
    //     $req->validate([
    //         'signup_name' => 'required|string|max:255',
    //         'signup_email' => [
    //             'required',
    //             'email:rfc,dns',
    //             'unique:website_users,email',
    //         ],
    //         'signup_mobile_number' => [
    //             'required',
    //             'digits:10',
    //             'regex:/^[0-9]{10}$/'
    //         ],
    //         'signup_organisation' => 'required|string|max:255',

    //         'signup_gst' => [
    //             'nullable',
    //             'regex:/^([0-9A-Z]{15})$/'
    //         ],

    //         'signup_password' => 'required|min:6',
    //     ], [
    //         'signup_name.required' => 'Please enter your full name.',
    //         'signup_email.required' => 'Please enter email ID.',
    //         'signup_email.email' => 'Enter a valid email address.',
    //         'signup_email.unique' => 'This email is already registered.',

    //         'signup_mobile_number.required' => 'Enter mobile number.',
    //         'signup_mobile_number.digits' => 'Mobile number must be exactly 10 digits.',
    //         'signup_mobile_number.regex' => 'Mobile number must contain only digits.',

    //         'signup_organisation.required' => 'Enter organisation name.',

    //         'signup_gst.regex' => 'Enter a valid 15-digit GST number.',

    //         'signup_password.required' => 'Enter password.',
    //         'signup_password.min' => 'Password must be at least 6 characters.',
    //     ]);

    //     // INSERT IN DATABASE
    //     WebsiteUser::create([
    //         'name'          => $req->signup_name,
    //         'email'         => $req->signup_email,
    //         'mobile_number' => $req->signup_mobile_number,
    //         'organisation'  => $req->signup_organisation,
    //         'gst'           => $req->signup_gst,
    //         'password'      => Hash::make($req->signup_password),
    //     ]);

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Registration successful! You can now login.'
    //     ]);
    // }
    /* ===================== SIGNUP ===================== */
    public function signup(Request $req)
    {
        $req->validate([
            'signup_name' => 'required|string|max:255',
            'signup_email' => 'required|email|unique:website_users,email',
            'signup_mobile_number' => 'required|digits:10',
            'signup_organisation' => 'required|string|max:255',
            'signup_gst' => 'nullable|regex:/^([0-9A-Z]{15})$/',
            'signup_password' => 'required|min:6',
        ]);

        WebsiteUser::create([
            'name'          => $req->signup_name,
            'email'         => $req->signup_email,
            'mobile_number' => $req->signup_mobile_number,
            'organisation'  => $req->signup_organisation,
            'gst'           => $req->signup_gst,
            'password'      => Hash::make($req->signup_password),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Registration successful! You can now login.'
        ]);
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

        // CHECK LOGIN
        if (!$user || !Hash::check($req->login_password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid email or password.'
            ]);
        }

        // STORE SESSION
        session(['website_user' => $user]);

        return response()->json([
            'status' => true,
            'message' => 'Login successful!'
        ]);
    }



    public function logout()
    {
        session()->forget('website_user');
        return redirect('/')->with('success', 'Logged out successfully!');
    }

    //   public function login(Request $req)
    // {
    //     $req->validate([
    //         'login_email' => 'required|email',
    //         'login_password' => 'required',
    //     ]);

    //     if (Auth::guard('website')->attempt([
    //         'email' => $req->login_email,
    //         'password' => $req->login_password,
    //     ])) {

    //         // 🔥 REQUIRED
    //         $req->session()->regenerate();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Login successful!',
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => false,
    //         'message' => 'Invalid email or password.',
    //     ]);
    // }

    // public function logout()
    // {
    //     Auth::guard('website')->logout();
    //     request()->session()->invalidate();
    //     request()->session()->regenerateToken();

    //     return redirect('/')->with('success', 'Logged out successfully!');
    // }
}

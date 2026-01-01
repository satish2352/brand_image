<?php

namespace App\Http\Controllers\Superadm;

use Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;

class LoginController extends Controller
{
    public function __construct() {}

    public function loginsuper()
    {
        return view('superadm.login');
    }
    public function validateSuperLogin(Request $req)
    {
        $req->validate([
            'superemail' => 'required|string',
            'superpassword' => 'required',
            'g-recaptcha-response' => 'required',
        ], [
            'superemail.required' => 'Enter user name',
            'superemail.email' => 'Enter a proper email address',
            'superpassword.required' => 'Enter password',
            'g-recaptcha-response.required' => 'Please verify that you are not a robot',
        ]);

        // Verify Google reCAPTCHA
        // $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
        //     'secret' => env('RECAPTCHA_SECRET_KEY'),
        //     'response' => $req->input('g-recaptcha-response'),
        //     'remoteip' => $req->ip(),
        // ]);

        // Verify Google reCAPTCHA
        if (!defined('CURL_SSLVERSION_TLSv1_2')) {
            define('CURL_SSLVERSION_TLSv1_2', 6);
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $req->input('g-recaptcha-response'),
            'remoteip' => $req->ip(),
        ]);

        $result = $response->json();
        if (!($result['success'] ?? false)) {
            return back()->withErrors(['g-recaptcha-response' => 'Captcha verification failed'])->withInput();
        }

        // Check user credentials
        $uname = $req->input('superemail');
        $pass = $req->input('superpassword');

        $user = User::where('email', $uname)
            ->where('is_deleted', 0)
            ->first();

        if (!$user) return redirect()->back()->with('error', 'User not found, contact admin');
        if ($user->is_active == 0) return redirect()->back()->with('error', 'User account is deactivated');

        if (!Hash::check($pass, $user->password)) {
            return redirect()->back()->with('error', 'User credentials not matching');
        }

        // ✅ Set session
        Session::put('user_id', $user->id);
        Session::put('role_id', $user->role_id);
        // Session::put('role', $user->role_id == 0 ? 'admin' : 'notadmin');
        Session::put('role', $user->role_id == 0 ? 'admin' : 'employee');
        Session::put('email', $user->email);
        Session::put('name', $user->name);

        return $user->role_id == 0 ? redirect('dashboard') : redirect('dashboard-emp');
    }

    public function logOut(Request $req)
    {
        $role = $req->session()->get('role'); // should now work
        $req->session()->flush();

        if ($role === 'admin') {
            return redirect()->route('login'); // admin login page
        } else {
            return redirect()->route('emp.login'); // employee login page
        }
    }
}

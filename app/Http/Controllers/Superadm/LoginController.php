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
use Illuminate\Http\Client\ConnectionException;

class LoginController extends Controller
{
    public function __construct() {}

    public function loginsuper()
    {
        return view('superadm.login');
    }
    public function validateSuperLogin(Request $req)
    {
        /* ---------------- BASIC VALIDATION ---------------- */
        $rules = [
            'superemail'    => 'required|string',
            'superpassword' => 'required',
        ];

        if (config('services.recaptcha.enabled')) {
            $rules['g-recaptcha-response'] = 'required';
        }

        $req->validate($rules, [
            'superemail.required' => 'Enter user name',
            'superpassword.required' => 'Enter password',
            'g-recaptcha-response.required' => 'Please verify that you are not a robot',
        ]);

        /* ---------------- CAPTCHA CHECK (SAFE) ---------------- */
        if (config('services.recaptcha.enabled')) {
            try {
                $response = Http::asForm()->post(
                    'https://www.google.com/recaptcha/api/siteverify',
                    [
                        'secret'   => config('services.recaptcha.secret'),
                        'response' => $req->input('g-recaptcha-response'),
                        'remoteip' => $req->ip(),
                    ]
                );

                $result = $response->json();

                if (!($result['success'] ?? false)) {
                    return back()
                        ->withErrors(['g-recaptcha-response' => 'Captcha verification failed'])
                        ->withInput();
                }
            } catch (ConnectionException $e) {
                // 🔥 Prevent cURL error crash
                return back()
                    ->withErrors(['g-recaptcha-response' => 'Captcha service unavailable. Try again later.'])
                    ->withInput();
            }
        }

        /* ---------------- USER AUTH ---------------- */
        $user = User::where('email', $req->superemail)
            ->where('is_deleted', 0)
            ->first();

        if (!$user) {
            return back()->with('error', 'User not found, contact admin');
        }

        if ($user->is_active == 0) {
            return back()->with('error', 'User account is deactivated');
        }

        if (!Hash::check($req->superpassword, $user->password)) {
            return back()->with('error', 'User credentials not matching');
        }

        /* ---------------- SESSION ---------------- */
        Session::put('user_id', $user->id);
        // Session::put('role_id', $user->role_id);
        // Session::put('role', $user->role_id == 0 ? 'admin' : 'employee');
        Session::put('email', $user->email);
        Session::put('name', $user->name);

        return redirect()->route('dashboard');
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

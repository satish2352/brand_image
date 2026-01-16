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
            return back()->with('error', 'Admin user not found. Contact the administrator for assistance.');
        }

        if ($user->is_active == 0) {
            return back()->with('error', 'This user account is deactivated. Contact the administrator for assistance.');
        }

        if (!Hash::check($req->superpassword, $user->password)) {
            return back()->with('error', 'User credentials not matching. Contact the administrator for assistance.');
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
        $req->session()->flush();   // clear all session values
        return redirect()->route('login'); // redirect to super admin login page
    }
}

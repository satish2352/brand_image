<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\WebsiteUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    // public function callback()
    // {
    //     try {
    //         $googleUser = Socialite::driver('google')
    //             ->stateless()
    //             ->user();
    //     } catch (\Exception $e) {
    //         return redirect('/')
    //             ->with('error', 'Google login failed. Please try again.');
    //     }

    //     $user = WebsiteUser::where('email', $googleUser->email)->first();

    //     if (!$user) {
    //         $user = WebsiteUser::create([
    //             'name'      => $googleUser->name,
    //             'email'     => $googleUser->email,
    //             'password'  => bcrypt(Str::random(32)),
    //             'is_active' => 1,
    //         ]);
    //     }

    //     if ($user->is_active == 0) {
    //         return redirect('/')
    //             ->with('error', 'Your account is inactive.');
    //     }

    //     Auth::guard('website')->login($user);
    //     request()->session()->regenerate();

    //     return redirect('/');
    // }

public function callback()
{
    try {
        $googleUser = Socialite::driver('google')->user();
    } catch (\Exception $e) {
        return redirect('/')
            ->with('error', 'Google login failed. Please try again.');
    }

    if (!$googleUser->email) {
        return redirect('/')
            ->with('error', 'Google account has no email.');
    }

    $user = WebsiteUser::where('email', $googleUser->email)->first();

    if (!$user) {
        $user = WebsiteUser::create([
            'name'          => $googleUser->name,
            'email'         => $googleUser->email,
            'mobile_number' => null,
            'organisation'  => null,
            'gst'           => null,
            'password'      => bcrypt(Str::random(32)),
            'is_active'     => 1,
        ]);
    }

    if ((int) $user->is_active === 0) {
        return redirect('/')
            ->with('error', 'Your account is inactive.');
    }

    Auth::guard('website')->login($user);
    request()->session()->regenerate();

    return redirect()->route('dashboard.home');
}

}

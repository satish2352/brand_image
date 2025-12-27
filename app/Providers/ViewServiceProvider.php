<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        View::composer('*', function ($view) {

            $cartCount = 0;

            if (Auth::guard('website')->check()) {
                $cartCount = DB::table('cart_items')
                    ->where('user_id', Auth::guard('website')->id())
                    ->count();
            }

            $view->with('cartCount', $cartCount);
        });
    }
}

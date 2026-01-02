<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {

            $cartCount = 0;

            if (Auth::guard('website')->check()) {
                $cartCount = CartItem::where('is_deleted', 0)
                    ->where('is_active', 1)
                    ->where('cart_type', 'NORMAL')
                    ->where('status', 'ACTIVE')
                    ->where('user_id', Auth::guard('website')->id())
                    ->count();
            }

            // Share with ALL blade views
            $view->with('cartCount', $cartCount);
        });

        /**
         * 🔍 Search dropdown data (ONLY website.search view)
         */

        View::composer('website.search-form', function ($view) {

            $categories = DB::table('category')
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->orderBy('category_name')
                ->get();

            $states = DB::table('tbl_location')
                ->where('location_type', 1)
                ->where('name', 'Maharashtra')
                ->where('is_active', 1)
                ->get();

            $radiusList = DB::table('radius_master')
                ->where('is_active', 1)
                ->orderBy('radius')
                ->get();

            $view->with([
                'categories' => $categories,
                'states'     => $states,
                'radiusList' => $radiusList, // ✅ FIX
            ]);
        });
    }
}

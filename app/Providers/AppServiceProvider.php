<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use App\Models\CartItem;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrap();

        /* ========================================
            CART COUNT (AVAILABLE ON ALL VIEWS)
        ======================================== */
        View::composer('*', function ($view) {
            $cartCount = 0;

            if (Auth::guard('website')->check()) {
                $cartCount = CartItem::where([
                    ['is_deleted', 0],
                    ['is_active', 1],
                    ['cart_type', 'NORMAL'],
                    ['status', 'ACTIVE'],
                    ['user_id', Auth::guard('website')->id()],
                ])->count();
            }

            $view->with('cartCount', $cartCount);
        });

        /* ========================================
            WEBSITE SEARCH FORM VIEW
        ======================================== */
        View::composer('website.search-form', function ($view) {

            $categories = DB::table('category')
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->orderBy('id')
                ->get();

            // 🔥 NEW STATE TABLE
            $states = DB::table('states')
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->orderBy('state_name')
                ->get();

            $radiusList = DB::table('radius_master')
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->orderBy('radius')
                ->get();

            $view->with(compact('categories', 'states', 'radiusList'));
        });

        /* ========================================
            ADMIN BOOKING SEARCH FORM VIEW
        ======================================== */
        View::composer('superadm.admin-booking.search-form', function ($view) {

            $firstCategoryName = DB::table('category')
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->orderBy('id')
                ->value('category_name');

            $states = DB::table('states')
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->orderBy('state_name')
                ->get();

            $radiusList = DB::table('radius_master')
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->orderBy('radius')
                ->get();

            $view->with(compact('firstCategoryName', 'states', 'radiusList'));
        });
    }
}
